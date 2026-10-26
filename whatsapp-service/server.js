const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');
const pino = require('pino');
const QRCode = require('qrcode');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion
} = require('@whiskeysockets/baileys');

require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3001;
const API_KEY = process.env.API_KEY || 'eleven_med_secret_wa_key_2026';
const AUTH_DIR = path.resolve(__dirname, process.env.AUTH_DIR || 'auth_info_baileys');

app.use(cors());
app.use(express.json());

// In-memory state
let sock = null;
let connectionStatus = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
let qrCodeDataUrl = null;
let qrCodeRaw = null;
let connectedNumber = null;
let userName = null;
let qrGeneratedAt = null;

// Auth middleware (checks x-api-key header or api_key query param)
const checkApiKey = (req, res, next) => {
    // Allow status and health checks or enforce key
    const clientKey = req.headers['x-api-key'] || req.query.api_key;
    if (API_KEY && clientKey !== API_KEY) {
        return res.status(401).json({ error: 'Unauthorized: Invalid API Key' });
    }
    next();
};

/**
 * Initialize WhatsApp Baileys Socket
 */
async function startWhatsAppSocket(forceNew = false) {
    if (sock && connectionStatus === 'connected' && !forceNew) {
        return;
    }

    try {
        if (!fs.existsSync(AUTH_DIR)) {
            fs.mkdirSync(AUTH_DIR, { recursive: true });
        }

        connectionStatus = 'connecting';
        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
        const { version } = await fetchLatestBaileysVersion().catch(() => ({ version: [2, 3000, 1015901307] }));

        sock = makeWASocket({
            version,
            auth: state,
            printQRInTerminal: false,
            logger: pino({ level: 'silent' }),
            browser: ['Eleven Med', 'Chrome', '1.0.0'],
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 60000,
            keepAliveIntervalMs: 25000,
            emitOwnEvents: false,
        });

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCodeRaw = qr;
                try {
                    qrCodeDataUrl = await QRCode.toDataURL(qr, {
                        width: 320,
                        margin: 2,
                        color: {
                            dark: '#1e293b',
                            light: '#ffffff'
                        }
                    });
                    qrGeneratedAt = Date.now();
                    connectionStatus = 'qr_ready';
                    console.log('[WhatsApp] Nuevo código QR listo para escanear');
                } catch (qrErr) {
                    console.error('[WhatsApp] Error generando DataURL de QR:', qrErr);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                console.log(`[WhatsApp] Conexión cerrada (código: ${statusCode}). Reintentar: ${shouldReconnect}`);

                if (shouldReconnect) {
                    connectionStatus = 'connecting';
                    setTimeout(() => {
                        startWhatsAppSocket();
                    }, 3000);
                } else {
                    connectionStatus = 'disconnected';
                    qrCodeDataUrl = null;
                    qrCodeRaw = null;
                    connectedNumber = null;
                    userName = null;
                    // Borrar credenciales si se desvinculó la sesión
                    try {
                        if (fs.existsSync(AUTH_DIR)) {
                            fs.rmSync(AUTH_DIR, { recursive: true, force: true });
                        }
                    } catch (e) {
                        console.error('[WhatsApp] Error eliminando auth_dir:', e);
                    }
                    console.log('[WhatsApp] Sesión cerrada / Desvinculada');
                }
            } else if (connection === 'open') {
                connectionStatus = 'connected';
                qrCodeDataUrl = null;
                qrCodeRaw = null;

                const userJid = sock.user?.id || '';
                connectedNumber = userJid.split(':')[0].split('@')[0];
                userName = sock.user?.name || 'Eleven Med';
                console.log(`[WhatsApp] ¡Conectado exitosamente! Teléfono: +${connectedNumber} (${userName})`);
            }
        });

    } catch (err) {
        console.error('[WhatsApp] Error iniciando socket:', err);
        connectionStatus = 'disconnected';
    }
}

// Routes
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'Eleven Med WhatsApp Microservice',
        timestamp: new Date().toISOString()
    });
});

app.get('/status', checkApiKey, (req, res) => {
    // Si no está corriendo el socket ni hay QR y no hay credenciales, verificar
    res.json({
        status: connectionStatus,
        phone: connectedNumber ? `+${connectedNumber}` : null,
        name: userName,
        hasQr: !!qrCodeDataUrl,
        qrAgeSeconds: qrGeneratedAt ? Math.round((Date.now() - qrGeneratedAt) / 1000) : null
    });
});

app.get('/qr', checkApiKey, async (req, res) => {
    if (connectionStatus === 'connected') {
        return res.json({
            status: 'connected',
            phone: connectedNumber ? `+${connectedNumber}` : null,
            message: 'Ya se encuentra vinculado y conectado.'
        });
    }

    // Si aún no hemos iniciado el socket o está desconectado, iniciarlo
    if (!sock || connectionStatus === 'disconnected') {
        startWhatsAppSocket();
        // Esperar breve momento para generación
        await new Promise(r => setTimeout(r, 1500));
    }

    res.json({
        status: connectionStatus,
        qr: qrCodeDataUrl,
        phone: connectedNumber ? `+${connectedNumber}` : null
    });
});

app.post('/disconnect', checkApiKey, async (req, res) => {
    console.log('[WhatsApp] Petición de desconexión recibida...');
    try {
        if (sock) {
            try {
                await sock.logout();
            } catch (e) {
                // Ignore logout error if socket is already down
            }
            try {
                sock.end();
            } catch (e) {}
            sock = null;
        }

        connectionStatus = 'disconnected';
        qrCodeDataUrl = null;
        qrCodeRaw = null;
        connectedNumber = null;
        userName = null;

        if (fs.existsSync(AUTH_DIR)) {
            fs.rmSync(AUTH_DIR, { recursive: true, force: true });
        }

        return res.json({
            success: true,
            status: 'disconnected',
            message: 'Sesión desvinculada exitosamente.'
        });
    } catch (err) {
        console.error('[WhatsApp] Error al desconectar:', err);
        return res.status(500).json({ error: 'Error al desconectar', details: err.message });
    }
});

app.post('/send', checkApiKey, async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ error: 'Parámetros requeridos: phone y message' });
    }

    if (connectionStatus !== 'connected' || !sock) {
        return res.status(503).json({
            error: 'WhatsApp no está conectado.',
            status: connectionStatus
        });
    }

    try {
        // Limpiar número de teléfono
        let cleaned = phone.toString().replace(/[^0-9]/g, '');

        // Formato para WhatsApp JID
        // Si es número de Argentina (inicia con 549 o 54 y 15):
        // En WhatsApp internacional: código de país + número
        let jid = `${cleaned}@s.whatsapp.net`;

        console.log(`[WhatsApp] Enviando mensaje a ${jid}...`);
        const result = await sock.sendMessage(jid, { text: message });

        return res.json({
            success: true,
            messageId: result.key.id,
            timestamp: result.messageTimestamp
        });
    } catch (err) {
        console.error('[WhatsApp] Error al enviar mensaje:', err);
        return res.status(500).json({
            error: 'No se pudo enviar el mensaje por WhatsApp',
            details: err.message
        });
    }
});

// Auto-start on launch if auth session exists
if (fs.existsSync(AUTH_DIR) && fs.readdirSync(AUTH_DIR).length > 0) {
    console.log('[WhatsApp] Sesión existente detectada. Conectando automáticamente...');
    startWhatsAppSocket();
}

app.listen(PORT, () => {
    console.log(`=======================================================`);
    console.log(`  Eleven Med - WhatsApp Gateway Microservice activo`);
    console.log(`  Puerto: ${PORT}`);
    console.log(`  Directorio de sesión: ${AUTH_DIR}`);
    console.log(`=======================================================`);
});
