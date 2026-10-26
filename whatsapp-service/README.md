# Eleven Med - WhatsApp Gateway Microservice (Baileys)

Microservicio ligero en Node.js que conecta WhatsApp Web mediante WebSockets con la aplicación Eleven Med sin necesidad de intermediarios ni APIs pagas.

---

## 🚀 Despliegue en Producción (Cloud / VPS)

Para que WhatsApp esté siempre conectado las 24 hs en producción sin depender de una PC encendida, se recomienda desplegar esta carpeta `whatsapp-service` en un servicio Cloud (como Render, Railway) o en un VPS.

### Opción A: Despliegue en Render (Gratis o Starter)
1. Crea una cuenta en [Render.com](https://render.com).
2. Haz clic en **New +** > **Web Service**.
3. Conecta tu repositorio de GitHub `elevensistemas/eleven_med`.
4. Configura:
   - **Root Directory**: `whatsapp-service`
   - **Runtime**: `Node`
   - **Build Command**: `npm install`
   - **Start Command**: `node server.js`
5. En **Environment Variables**, añade:
   - `PORT`: `3001`
   - `API_KEY`: `eleven_med_secret_wa_key_2026`
6. Una vez desplegado, Render te dará una URL (ej: `https://whatsapp-service-xxxx.onrender.com`).
7. En el archivo `.env` de Eleven Med en el servidor web (Hostinger), coloca:
   ```env
   WHATSAPP_SERVICE_URL=https://whatsapp-service-xxxx.onrender.com
   WHATSAPP_SERVICE_KEY=eleven_med_secret_wa_key_2026
   ```

---

### Opción B: Despliegue en un VPS con PM2 (Ubuntu / Debian)
1. Sube la carpeta `whatsapp-service` a tu VPS (ej: `/var/www/whatsapp-service`).
2. Instala dependencias:
   ```bash
   cd /var/www/whatsapp-service
   npm install --production
   ```
3. Inicia con PM2 para que reinicie automáticamente si se reinicia el servidor:
   ```bash
   npm install -g pm2
   pm2 start server.js --name "whatsapp-service"
   pm2 save
   pm2 startup
   ```

---

### Opción C: Ejecución Local en la Clínica
Si la computadora de la clínica está encendida durante el horario de atención:
1. Simplemente ejecuta el archivo `arrancar_servidores.bat` que ya incluye el inicio de `WhatsApp Gateway`.
2. O desde la terminal:
   ```bash
   cd whatsapp-service
   node server.js
   ```

---

## 📡 Endpoints del Microservicio

| Método | Endpoint | Descripción |
| :--- | :--- | :--- |
| `GET` | `/status` | Devuelve el estado actual (`disconnected`, `qr_ready`, `connected`) y el teléfono vinculado. |
| `GET` | `/qr` | Inicia o solicita el código QR en formato Base64 DataURL listo para renderizar. |
| `POST` | `/send` | Envía un mensaje a un teléfono: `{ "phone": "549221...", "message": "Texto..." }`. |
| `POST` | `/disconnect`| Cierra la sesión activa en WhatsApp y elimina las credenciales locales. |
| `GET` | `/health` | Healthcheck básico del servicio. |
