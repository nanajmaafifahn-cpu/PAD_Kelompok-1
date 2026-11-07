// Fitur QR ruangan, dimuat dinamis oleh app.js hanya bila halaman membutuhkannya:
//  - [data-qr-scanner]  pemindai kamera (html5-qrcode) di halaman scan.index
//  - canvas[data-qr-path] gambar QR (qrcode) di halaman Cetak QR Ruangan
// Script ini tidak mengubah status booking; check-in/out tetap lewat POST ke server.
import { Html5Qrcode } from 'html5-qrcode';
import QRCode from 'qrcode';

const SCAN_PATH = /\/scan\/[A-Za-z0-9]+\/?$/;

function initScanner(element) {
    const status = document.querySelector('[data-qr-scanner-status]');
    const say = (message) => { if (status) status.textContent = message; };

    if (!window.isSecureContext) {
        say('Kamera browser hanya berjalan di HTTPS atau localhost. Gunakan aplikasi kamera ponsel (QR berisi URL) atau input manual.');
        return;
    }

    const scanner = new Html5Qrcode(element.id);
    let handled = false;

    const onDecoded = (text) => {
        if (handled) return;

        let url;
        try { url = new URL(text, window.location.origin); } catch { url = null; }

        if (url && SCAN_PATH.test(url.pathname)) {
            handled = true;
            say('QR ruangan terbaca, membuka halaman konfirmasi…');
            // Buka di origin yang sedang dipakai, agar QR tetap jalan walau dicetak dengan alamat lain.
            scanner.stop().finally(() => { window.location.href = window.location.origin + url.pathname; });
            return;
        }

        const manual = document.getElementById('code');
        if (manual) manual.value = text;
        say('QR terbaca tetapi bukan QR ruangan Campus Space. Isinya disalin ke input manual.');
    };

    scanner
        .start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, onDecoded, () => {})
        .then(() => say('Arahkan kamera ke QR di pintu ruangan.'))
        .catch(() => say('Kamera tidak dapat dibuka (izin ditolak atau tidak tersedia). Gunakan input manual.'));
}

function initQrCards() {
    const base = document.querySelector('[data-qr-base]');
    const canvases = document.querySelectorAll('canvas[data-qr-path]');

    const render = () => {
        const root = (base?.value || window.location.origin).trim().replace(/\/+$/, '');

        canvases.forEach((canvas) => {
            const url = root + canvas.dataset.qrPath;
            QRCode.toCanvas(canvas, url, { width: 180, margin: 1 });

            const label = document.querySelector(`[data-qr-url-for="${canvas.dataset.qrPath}"]`);
            if (label) label.textContent = url;
        });
    };

    base?.addEventListener('input', render);
    render();
}

const scannerElement = document.querySelector('[data-qr-scanner]');
if (scannerElement) initScanner(scannerElement);

if (document.querySelector('canvas[data-qr-path]')) initQrCards();
