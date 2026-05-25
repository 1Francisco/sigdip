const http = require('http');
const fs = require('fs');
const path = require('path');
const qr = require('qrcode-terminal');

const APK_PATH = 'C:\\Users\\franc\\Desktop\\SIGDIP-Mobile.apk';
const PORT = 9090;
const IP = '192.168.1.91';
const URL = `http://${IP}:${PORT}/SIGDIP-Mobile.apk`;

const server = http.createServer((req, res) => {
  if (req.url === '/SIGDIP-Mobile.apk') {
    const stat = fs.statSync(APK_PATH);
    res.writeHead(200, {
      'Content-Type': 'application/vnd.android.package-archive',
      'Content-Length': stat.size,
      'Content-Disposition': 'attachment; filename="SIGDIP-Mobile.apk"'
    });
    fs.createReadStream(APK_PATH).pipe(res);
  } else {
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(`
      <html><body style="text-align:center;font-family:sans-serif;padding:40px;">
        <h1>SIGDIP Mobile</h1>
        <p>Haz clic para descargar:</p>
        <a href="/SIGDIP-Mobile.apk" style="font-size:24px;padding:20px 40px;background:#1B5E20;color:#fff;border-radius:12px;text-decoration:none;">Descargar APK</a>
      </body></html>
    `);
  }
});

server.listen(PORT, '0.0.0.0', () => {
  console.log('\n========================================');
  console.log('  SIGDIP - Servidor de descarga APK');
  console.log('========================================');
  console.log(`\n  URL: ${URL}\n`);
  console.log('  Escanea este QR con tu celular:\n');
  qr.generate(URL, { small: true });
  console.log('\n  (Tu celular debe estar en la misma red WiFi)');
  console.log('  Presiona Ctrl+C para detener.\n');
});
