// Fitur QR ruangan, dimuat dinamis oleh app.js hanya bila halaman membutuhkannya:
//  - [data-qr-scanner]  pemindai (html5-qrcode) di halaman scan.index: tombol "Izinkan Kamera" & "Pindai dari Foto"
//  - canvas[data-qr-path] gambar QR (qrcode) di halaman Cetak QR Ruangan
// Antarmuka bawaan html5-qrcode (berbahasa Inggris) tidak dipakai; semua teks dibuat sendiri dalam Bahasa Indonesia.
// Script ini tidak mengubah status booking; check-in/out tetap lewat POST ke server.
import { Html5Qrcode } from 'html5-qrcode';
import QRCode from 'qrcode';

const SCAN_PATH = /\/scan\/[A-Za-z0-9]+\/?$/;

function initScanner(element) {
    const title = document.querySelector('[data-qr-scanner-title]');
    const status = document.querySelector('[data-qr-scanner-status]');
    const startButton = document.querySelector('[data-qr-start]');
    const fileLabel = document.querySelector('[data-qr-file-label]');
    const fileInput = document.querySelector('[data-qr-file]');
    const placeholder = document.querySelector('[data-qr-placeholder]');

    const say = (heading, message) => {
        if (title) title.textContent = heading;
        if (status) status.textContent = message;
    };

    const scanner = new Html5Qrcode(element.id);
    let handled = false;

    const onDecoded = (text) => {
        if (handled) return;

        let url;
        try { url = new URL(text, window.location.origin); } catch { url = null; }

        if (url && SCAN_PATH.test(url.pathname)) {
            handled = true;
            say('QR terbaca', 'Membuka peminjaman Anda di ruangan ini…');
            const go = () => { window.location.href = window.location.origin + url.pathname; };
            // Buka di origin yang sedang dipakai, agar QR tetap jalan walau dicetak dengan alamat lain.
            scanner.isScanning ? scanner.stop().finally(go) : go();
            return;
        }

        const manual = document.getElementById('code');
        if (manual) {
            manual.value = text;
            manual.closest('details')?.setAttribute('open', '');
        }
        say('Bukan QR ruangan', 'QR terbaca tetapi bukan QR ruangan Campus Space. Isinya disalin ke input manual.');
    };

    // Pindai dari foto selalu tersedia (tidak butuh HTTPS).
    if (fileLabel && fileInput) {
        fileLabel.hidden = false;
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (!file) return;
            say('Membaca foto…', 'Mencari QR pada foto yang dipilih.');
            scanner.scanFile(file, false)
                .then(onDecoded)
                .catch(() => say('QR tidak ditemukan', 'Foto tidak berisi QR yang terbaca. Coba foto yang lebih jelas atau masukkan tautan secara manual.'))
                .finally(() => { fileInput.value = ''; });
        });
    }

    if (!window.isSecureContext) {
        say('Siap memindai', 'Kamera browser hanya berjalan di HTTPS atau localhost. Gunakan kamera bawaan ponsel, pindai dari foto, atau masukkan tautan manual.');
        return;
    }

    if (startButton) {
        startButton.hidden = false;
        startButton.addEventListener('click', () => {
            startButton.disabled = true;
            say('Membuka kamera…', 'Izinkan akses kamera bila browser meminta.');
            scanner
                .start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, onDecoded, () => {})
                .then(() => {
                    startButton.hidden = true;
                    if (placeholder) placeholder.hidden = true;
                    say('Kamera aktif', 'Arahkan kamera ke QR di pintu ruangan.');
                })
                .catch(() => {
                    startButton.disabled = false;
                    say('Kamera tidak dapat dibuka', 'Izin ditolak atau kamera tidak tersedia. Pindai dari foto atau masukkan tautan manual.');
                });
        });
    }
}

function initQrCards() {
    const base = document.querySelector('[data-qr-base]');
    const canvases = document.querySelectorAll('canvas[data-qr-path]');

    const render = () => {
        const root = (base?.value || window.location.origin).trim().replace(/\/+$/, '');

        canvases.forEach((canvas) => {
            const url = root + canvas.dataset.qrPath;
            QRCode.toCanvas(canvas, url, { width: Number(canvas.getAttribute('width')) || 180, margin: 1 }, () => {
                // qrcode menulis ukuran inline; hapus agar ukuran tampil/cetak diatur CSS (.cs-qr-canvas).
                canvas.style.removeProperty('width');
                canvas.style.removeProperty('height');
            });

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
