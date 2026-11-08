// Interaksi ringan form Campus Space (validasi klien, kapasitas, rentang waktu).
// Lihat resources/js/campus-space.js; file yang sama disisipkan layout saat mode CDN.
import './campus-space.js';

// Fitur QR (pemindai kamera & gambar QR) dipisah jadi chunk sendiri,
// hanya diunduh di halaman yang memakainya (scan.index, rooms.qr.index).
if (document.querySelector('[data-qr-scanner], canvas[data-qr-path]')) {
    import('./qr.js');
}
