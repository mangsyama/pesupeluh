const express = require('express');
const cors = require('cors');
const QRCode = require('qrcode');
const pino = require('pino');
const fs = require('fs');
const path = require('path');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    Browsers
} = require('@whiskeysockets/baileys');

const app = express();
const PORT = process.env.WA_GATEWAY_PORT || 3000;
const SESSION_DIR = path.join(__dirname, 'auth_sessions');

// Prevent crashes from uncaught exceptions and unhandled promise rejections
process.on('uncaughtException', (err) => {
    console.error('💥 [WA-Gateway] Uncaught Exception:', err);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('💥 [WA-Gateway] Unhandled Rejection at:', promise, 'reason:', reason);
});


const SECRET_KEY = process.env.WA_GATEWAY_SECRET_KEY || '';

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// API Key Authentication Middleware
app.use((req, res, next) => {
    if (!SECRET_KEY) {
        return next();
    }
    const apiKey = req.headers['x-api-key'] || req.headers['authorization']?.replace('Bearer ', '');
    if (!apiKey || apiKey !== SECRET_KEY) {
        return res.status(401).json({
            status: false,
            message: 'Unauthorized: Invalid or missing WA Gateway Secret Key.'
        });
    }
    next();
});

let sock = null;
let qrCodeData = null;
let connectionStatus = 'disconnected'; // 'disconnected' | 'connecting' | 'connected'
let connectedUser = null;
let isInitializing = false;

const logger = pino({ level: 'silent' });

async function initWhatsApp() {
    if (isInitializing) return;
    isInitializing = true;
    connectionStatus = 'connecting';

    try {
        if (sock) {
            try {
                sock.ev.removeAllListeners();
                sock.end(undefined);
            } catch (e) {}
            sock = null;
        }

        if (!fs.existsSync(SESSION_DIR)) {
            fs.mkdirSync(SESSION_DIR, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);
        const { version } = await fetchLatestBaileysVersion();

        sock = makeWASocket({
            version,
            auth: state,
            logger,
            browser: Browsers.ubuntu('Desktop'),
            markOnlineOnConnect: false,
            syncFullHistory: false
        });

        sock.ev.on('creds.update', async () => {
            try {
                if (connectionStatus !== 'disconnected') {
                    await saveCreds();
                }
            } catch (err) {
                console.error('Error saving credentials:', err);
            }
        });

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                try {
                    qrCodeData = await QRCode.toDataURL(qr);
                    connectionStatus = 'connecting';
                    console.log('📌 [WA-Gateway] New QR Code generated. Scan with WhatsApp!');
                } catch (err) {
                    console.error('Failed to generate QR Data URL', err);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                connectionStatus = 'disconnected';
                qrCodeData = null;
                connectedUser = null;
                isInitializing = false;

                console.log(`⚠️ [WA-Gateway] Connection closed. Reason code: ${statusCode}. Reconnecting: ${shouldReconnect}`);

                if (statusCode === DisconnectReason.loggedOut) {
                    clearSession();
                    console.log('📌 [WA-Gateway] Logged out. Reinitializing in 3 seconds to get a new QR code...');
                    setTimeout(() => initWhatsApp(), 3000);
                } else if (shouldReconnect) {
                    setTimeout(() => initWhatsApp(), 3000);
                }
            } else if (connection === 'open') {
                connectionStatus = 'connected';
                qrCodeData = null;
                isInitializing = false;

                const userJid = sock.user?.id || '';
                const userName = sock.user?.name || sock.user?.notify || 'WhatsApp User';
                connectedUser = {
                    id: userJid.split(':')[0] || userJid,
                    name: userName
                };

                console.log(`✅ [WA-Gateway] Connected successfully as ${userName} (${connectedUser.id})`);
            }
        });

    } catch (error) {
        console.error('❌ [WA-Gateway] Initialization error:', error);
        connectionStatus = 'disconnected';
        isInitializing = false;
    }
}

function clearSession() {
    try {
        if (fs.existsSync(SESSION_DIR)) {
            const files = fs.readdirSync(SESSION_DIR);
            for (const file of files) {
                try {
                    fs.rmSync(path.join(SESSION_DIR, file), { recursive: true, force: true });
                } catch (fileErr) {
                    console.error(`Error deleting session file ${file}:`, fileErr);
                }
            }
        }
        connectionStatus = 'disconnected';
        qrCodeData = null;
        connectedUser = null;
        sock = null;
        console.log('🗑️ [WA-Gateway] Auth Session cleared.');
    } catch (e) {
        console.error('Error clearing session:', e);
    }
}

function formatPhoneJid(phone) {
    if (!phone) return null;
    let cleaned = phone.toString().replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    }
    if (!cleaned.endsWith('@s.whatsapp.net')) {
        cleaned = cleaned + '@s.whatsapp.net';
    }
    return cleaned;
}

// REST API Endpoints

// 1. Get Status & QR Code
app.get('/status', (req, res) => {
    res.json({
        status: connectionStatus,
        qr: qrCodeData,
        user: connectedUser
    });
});

// 2. Send Message (Supports both /send and /send-message)
const handleSendMessage = async (req, res) => {
    const target = req.body.target || req.body.phone || req.body.receiver;
    const message = req.body.message || req.body.msg || req.body.text;

    if (!target || !message) {
        return res.status(400).json({
            status: false,
            message: 'Target (phone number) and message parameters are required.'
        });
    }

    if (connectionStatus !== 'connected' || !sock) {
        return res.status(503).json({
            status: false,
            message: 'WhatsApp Gateway is not connected. Please scan QR Code first.'
        });
    }

    const jid = formatPhoneJid(target);
    if (!jid) {
        return res.status(400).json({
            status: false,
            message: 'Invalid phone number format.'
        });
    }

    try {
        // Simulate human typing presence to prevent WhatsApp anti-spam detection
        try {
            await sock.sendPresenceUpdate('composing', jid);
        } catch (presenceErr) {
            // Ignore presence errors if jid is not active
        }

        // Calculate dynamic human typing delay based on message length (1.2s - 2.5s)
        const delayMs = Math.min(2500, Math.max(1200, Math.floor(message.length * 15)));
        await new Promise(resolve => setTimeout(resolve, delayMs));

        try {
            await sock.sendPresenceUpdate('paused', jid);
        } catch (e) {
            // Ignore
        }

        const result = await sock.sendMessage(jid, { text: message });
        return res.json({
            status: true,
            message: 'WhatsApp message sent successfully with typing simulation',
            target: jid,
            id: result.key?.id
        });
    } catch (err) {
        console.error('❌ [WA-Gateway] Error sending message to', target, err);
        return res.status(500).json({
            status: false,
            message: 'Failed to send WhatsApp message: ' + err.message
        });
    }
};

app.post('/send', handleSendMessage);
app.post('/send-message', handleSendMessage);

// 3. Logout / Disconnect Device
app.post('/logout', async (req, res) => {
    try {
        if (sock) {
            await sock.logout();
        }
    } catch (e) {
        // ignore
    }
    clearSession();
    setTimeout(() => initWhatsApp(), 1000);
    return res.json({
        status: true,
        message: 'WhatsApp Gateway logged out successfully.'
    });
});

// Start Express & WA Init
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 [WA-Gateway] Server running on http://127.0.0.1:${PORT}`);
    initWhatsApp();
});
