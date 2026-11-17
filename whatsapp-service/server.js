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

require('dotenv').config({ path: path.resolve(__dirname, '.env') });

const app = express();
const PORT = process.env.PORT || 3001;
const API_KEY = process.env.API_KEY || 'eleven_med_secret_wa_key_2026';
const CLOUD_URL = (process.env.CLOUD_URL || 'https://elevenmed.elevensistemas.com').replace(/\/+$/, '');
const AUTH_DIR = path.resolve(__dirname, process.env.AUTH_DIR || 'auth_info_baileys');

app.use(cors());
app.use(express.json());

// In-memory state
let sock = null;
let isStartingSocket = false;
let connectionStatus = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
let qrCodeDataUrl = null;
let qrCodeRaw = null;
let connectedNumber = null;
let userName = null;
let qrGeneratedAt = null;
let lastCloudSyncOk = false;
let lastCloudSyncAt = null;
let pendingAcks = [];
let processingOutboxIds = new Set();

// Auth middleware (checks x-api-key header or api_key query param)
const checkApiKey = (req, res, next) => {
    const clientKey = req.headers['x-api-key'] || req.query.api_key;
    if (API_KEY && clientKey !== API_KEY) {
        return res.status(401).json({ error: 'Unauthorized: Invalid API Key' });
    }
    next();
};

/**
 * Normaliza números argentinos y resuelve el JID real en WhatsApp (con o sin 9)
 */
async function resolveWhatsAppJid(rawPhone) {
    let digits = (rawPhone || '').toString().replace(/[^0-9]/g, '');
    if (!digits) throw new Error('Número telefónico vacío');

    // Quitar 0 inicial nacional (ej: 011 -> 11, 0221 -> 221)
    if (digits.startsWith('0')) {
        digits = digits.replace(/^0+/, '');
    }

    // Quitar 15 intermedio si viene sin código de país (ej: 11 15 xxxx -> 11 xxxx)
    if (digits.startsWith('1115') && digits.length === 12) {
        digits = '11' + digits.substring(4);
    }

    // Si tiene 10 dígitos típicos de Argentina (ej: 1123456789 o 2214567890), anteponer 549
    const candidates = [];
    if (digits.length === 10 && !digits.startsWith('54')) {
        candidates.push('549' + digits);
        candidates.push('54' + digits);
    } else if (digits.startsWith('549') && digits.length >= 12) {
        candidates.push(digits);
        candidates.push('54' + digits.substring(3));
    } else if (digits.startsWith('54') && !digits.startsWith('549') && digits.length >= 11) {
        candidates.push('549' + digits.substring(2));
        candidates.push(digits);
    } else {
        candidates.push(digits);
    }

    if (sock && typeof sock.onWhatsApp === 'function') {
        for (const cand of candidates) {
            try {
                const res = await sock.onWhatsApp(cand);
                if (Array.isArray(res) && res.length > 0 && res[0].exists && res[0].jid) {
                    return res[0].jid;
                }
            } catch (_) {
                // Fallback al siguiente candidato
            }
        }
    }

    return `${candidates[0]}@s.whatsapp.net`;
}

/**
 * Initialize WhatsApp Baileys Socket
 */
async function startWhatsAppSocket(forceNew = false) {
    if (isStartingSocket) return;
    if (sock && (connectionStatus === 'connected' || connectionStatus === 'qr_ready') && !forceNew) {
        return;
    }

    isStartingSocket = true;
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
            browser: ['Cortalezzi Vision - Eleven Med', 'Chrome', '1.0.0'],
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
                    console.log('[WhatsApp] Código QR generado. Escanéelo desde la ventana del navegador o desde Eleven Med.');
                } catch (qrErr) {
                    console.error('[WhatsApp] Error generando DataURL de QR:', qrErr);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                console.log(`[WhatsApp] Conexión cerrada (código: ${statusCode}). Reintentar: ${shouldReconnect}`);

                sock = null;
                if (shouldReconnect) {
                    connectionStatus = 'connecting';
                    setTimeout(() => {
                        startWhatsAppSocket(true);
                    }, 3500);
                } else {
                    connectionStatus = 'disconnected';
                    qrCodeDataUrl = null;
                    qrCodeRaw = null;
                    connectedNumber = null;
                    userName = null;
                    try {
                        if (fs.existsSync(AUTH_DIR)) {
                            fs.rmSync(AUTH_DIR, { recursive: true, force: true });
                        }
                    } catch (e) {
                        console.error('[WhatsApp] Error eliminando auth_dir:', e);
                    }
                    console.log('[WhatsApp] Sesión desvinculada. Generando nuevo QR en 2 segundos...');
                    setTimeout(() => startWhatsAppSocket(true), 2000);
                }
            } else if (connection === 'open') {
                connectionStatus = 'connected';
                qrCodeDataUrl = null;
                qrCodeRaw = null;

                const userJid = sock.user?.id || '';
                connectedNumber = userJid.split(':')[0].split('@')[0];
                userName = sock.user?.name || 'Consultorio Cortalezzi';
                console.log(`[WhatsApp] ¡VINCULADO CON ÉXITO! Línea activa: +${connectedNumber} (${userName})`);
            }
        });
    } catch (err) {
        console.error('[WhatsApp] Error iniciando socket:', err);
        connectionStatus = 'disconnected';
    } finally {
        isStartingSocket = false;
    }
}

async function disconnectSession() {
    if (sock) {
        try { await sock.logout(); } catch (_) {}
        try { sock.end(); } catch (_) {}
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
}

async function sendTextMessage(phone, message) {
    if (connectionStatus !== 'connected' || !sock) {
        throw new Error('WhatsApp aún no está vinculado.');
    }
    const jid = await resolveWhatsAppJid(phone);
    console.log(`[WhatsApp] Enviando mensaje a ${jid}...`);
    const result = await sock.sendMessage(jid, { text: message });
    return {
        messageId: result?.key?.id || null,
        timestamp: result?.messageTimestamp || Date.now()
    };
}

/**
 * Puente Saliente Seguro con Eleven Med en la Nube (Hostinger)
 * No requiere abrir puertos en el router del consultorio ni instalar túneles externos.
 */
async function syncWithCloud() {
    if (!CLOUD_URL || typeof fetch !== 'function') return;

    const acksBatch = pendingAcks.splice(0, pendingAcks.length);

    try {
        const resp = await fetch(`${CLOUD_URL}/api/whatsapp-bridge/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'x-api-key': API_KEY
            },
            body: JSON.stringify({
                status: connectionStatus,
                phone: connectedNumber ? `+${connectedNumber}` : null,
                name: userName,
                qr: connectionStatus === 'qr_ready' ? qrCodeDataUrl : null,
                acks: acksBatch
            }),
            signal: AbortSignal.timeout ? AbortSignal.timeout(8000) : undefined
        });

        if (!resp.ok) {
            lastCloudSyncOk = false;
            return;
        }

        const data = await resp.json();
        lastCloudSyncOk = true;
        lastCloudSyncAt = new Date().toISOString();

        // Ejecutar comandos solicitados desde la web de Eleven Med
        if (data.command === 'start_qr' && connectionStatus === 'disconnected') {
            startWhatsAppSocket(true);
        } else if (data.command === 'disconnect') {
            await disconnectSession();
            setTimeout(() => startWhatsAppSocket(true), 1500);
        }

        // Procesar cola de mensajes salientes desde Eleven Med
        if (Array.isArray(data.outbox) && connectionStatus === 'connected') {
            for (const item of data.outbox) {
                if (!item || !item.id || processingOutboxIds.has(item.id)) continue;
                processingOutboxIds.add(item.id);

                try {
                    await sendTextMessage(item.phone, item.message);
                    pendingAcks.push({ id: item.id, success: true });
                    console.log(`[Bridge] Mensaje #${item.id} entregado a ${item.phone}`);
                } catch (sendErr) {
                    pendingAcks.push({ id: item.id, success: false, error: sendErr.message });
                    console.error(`[Bridge] Error enviando #${item.id}:`, sendErr.message);
                } finally {
                    setTimeout(() => processingOutboxIds.delete(item.id), 30000);
                }
            }
        }
    } catch (_) {
        lastCloudSyncOk = false;
    }
}

// Routes
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'Eleven Med WhatsApp Microservice',
        cloud_sync: lastCloudSyncOk,
        cloud_url: CLOUD_URL,
        timestamp: new Date().toISOString()
    });
});

// Panel visual local en la PC del consultorio para escanear el QR cómodamente
app.get(['/', '/vincular'], (req, res) => {
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.send(`<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vincular WhatsApp - Consultorio Cortalezzi | Eleven Med</title>
<style>
  * { box-sizing: border-box; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
  body { margin: 0; background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
  .card { background: #fff; max-width: 540px; width: 100%; border-radius: 24px; padding: 32px; box-shadow: 0 25px 60px rgba(0,0,0,0.35); text-align: center; }
  .brand { font-size: 0.8rem; font-weight: 800; letter-spacing: 1.5px; color: #4f46e5; text-transform: uppercase; margin-bottom: 4px; }
  h1 { font-size: 1.45rem; margin: 0 0 8px; color: #0f172a; }
  .sub { color: #64748b; font-size: 0.92rem; margin-bottom: 20px; line-height: 1.45; }
  .cloud-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; margin-bottom: 18px; }
  .cloud-ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
  .cloud-wait { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
  .qr-box { border: 2px dashed #cbd5e1; border-radius: 20px; padding: 20px; margin: 12px auto 20px; max-width: 310px; min-height: 290px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8fafc; }
  .qr-box img { width: 260px; height: 260px; border-radius: 12px; background: #fff; padding: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
  .connected-box { background: #f0fdf4; border: 2px solid #22c55e; border-radius: 20px; padding: 24px; margin: 12px 0 20px; }
  .phone-big { font-size: 1.5rem; font-weight: 800; color: #15803d; margin: 8px 0; }
  .steps { text-align: left; background: #f1f5f9; border-radius: 14px; padding: 14px 18px; font-size: 0.86rem; color: #334155; line-height: 1.6; margin-top: 12px; }
  .btn { border: none; border-radius: 999px; padding: 11px 22px; font-weight: 700; cursor: pointer; font-size: 0.9rem; text-decoration: none; display: inline-block; margin: 4px; }
  .btn-primary { background: #4f46e5; color: #fff; }
  .btn-danger { background: #fee2e2; color: #b91c1c; }
  .spinner { width: 42px; height: 42px; border: 4px solid #e2e8f0; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.9s linear infinite; margin-bottom: 12px; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">Cortalezzi Vision · Eleven Med</div>
    <h1>Vinculación de WhatsApp del Consultorio</h1>
    <div id="cloudPill" class="cloud-pill cloud-wait">Sincronizando con Eleven Med...</div>

    <div id="stateQr">
      <p class="sub">Escanee este código QR con el celular de WhatsApp del consultorio:</p>
      <div class="qr-box" id="qrContainer">
        <div class="spinner"></div>
        <div style="font-size:0.88rem;color:#64748b;">Generando código QR seguro...</div>
      </div>
      <div class="steps">
        <strong>Pasos en el celular:</strong><br>
        1. Abra <strong>WhatsApp</strong> en el teléfono de la clínica.<br>
        2. Toque <strong>Menú (⋮)</strong> o <strong>Configuración</strong> &rarr; <strong>Dispositivos vinculados</strong>.<br>
        3. Toque <strong>Vincular un dispositivo</strong> y apunte al código QR.
      </div>
    </div>

    <div id="stateConnected" style="display:none;">
      <div class="connected-box">
        <div style="font-size:2.4rem;">✅</div>
        <div style="font-weight:700;color:#166534;">¡Línea Vinculada y Activa!</div>
        <div class="phone-big" id="phoneLabel">+54 ...</div>
        <div style="font-size:0.84rem;color:#475569;">Ya puede minimizar la ventana negra del conector y operar normalmente desde <strong>Eleven Med</strong>.</div>
      </div>
      <a class="btn btn-primary" href="${CLOUD_URL}/configuracion/whatsapp" target="_blank">Abrir Panel en Eleven Med</a>
      <button class="btn btn-danger" onclick="unpair()">Desvincular Línea</button>
    </div>
  </div>

<script>
const KEY = ${JSON.stringify(API_KEY)};
async function poll() {
  try {
    const [stRes, hRes] = await Promise.all([
      fetch('/qr?api_key=' + encodeURIComponent(KEY)),
      fetch('/health')
    ]);
    const st = await stRes.json();
    const h = await hRes.json();

    const pill = document.getElementById('cloudPill');
    if (h.cloud_sync) {
      pill.className = 'cloud-pill cloud-ok';
      pill.innerText = '● Sincronizado en vivo con elevenmed.elevensistemas.com';
    } else {
      pill.className = 'cloud-pill cloud-wait';
      pill.innerText = '● Conectando con servidor Eleven Med...';
    }

    if (st.status === 'connected') {
      document.getElementById('stateQr').style.display = 'none';
      document.getElementById('stateConnected').style.display = 'block';
      document.getElementById('phoneLabel').innerText = st.phone || 'Conectado';
    } else {
      document.getElementById('stateQr').style.display = 'block';
      document.getElementById('stateConnected').style.display = 'none';
      const box = document.getElementById('qrContainer');
      if (st.qr) {
        box.innerHTML = '<img src="' + st.qr + '" alt="QR WhatsApp"><div style="font-size:0.8rem;color:#16a34a;font-weight:700;margin-top:8px;">Esperando escaneo...</div>';
      }
    }
  } catch (e) {}
}
async function unpair() {
  if (!confirm('¿Desvincular el WhatsApp actual y generar un nuevo QR?')) return;
  await fetch('/disconnect?api_key=' + encodeURIComponent(KEY), { method: 'POST' });
  poll();
}
poll();
setInterval(poll, 2500);
</script>
</body>
</html>`);
});

app.get('/status', checkApiKey, (req, res) => {
    res.json({
        status: connectionStatus,
        phone: connectedNumber ? `+${connectedNumber}` : null,
        name: userName,
        hasQr: !!qrCodeDataUrl,
        cloudSync: lastCloudSyncOk,
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

    if (!sock || connectionStatus === 'disconnected') {
        startWhatsAppSocket();
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
        await disconnectSession();
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
        const info = await sendTextMessage(phone, message);
        return res.json({
            success: true,
            messageId: info.messageId,
            timestamp: info.timestamp
        });
    } catch (err) {
        console.error('[WhatsApp] Error al enviar mensaje:', err);
        return res.status(500).json({
            error: 'No se pudo enviar el mensaje por WhatsApp',
            details: err.message
        });
    }
});

// Auto-iniciar socket siempre al arrancar (conecta sesión previa o prepara el QR al instante)
startWhatsAppSocket();

// Sincronización periódica con Eleven Med Cloud cada 2.5 segundos
setInterval(syncWithCloud, 2500);
setTimeout(syncWithCloud, 1000);

app.listen(PORT, () => {
    console.log(`=======================================================`);
    console.log(`  Cortalezzi Vision | Eleven Med - Conector WhatsApp`);
    console.log(`  Panel local QR: http://127.0.0.1:${PORT}/vincular`);
    console.log(`  Nube vinculada: ${CLOUD_URL}`);
    console.log(`=======================================================`);
});
