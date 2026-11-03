/* =============================================================================
   campus-space.js — Interaksi ringan form (FE-05)

   Dimuat di dua mode (sama seperti campus-space.css):
   - Mode Vite : di-import oleh resources/js/app.js.
   - Mode CDN  : disisipkan langsung oleh layouts/app.blade.php.
   Karena itu file ini berupa script biasa (tanpa import/export) dan aman bila
   dimuat dua kali.

   Prinsip: progressive enhancement. Tanpa JavaScript, form tetap terkirim dan
   divalidasi oleh atribut HTML5 + FormRequest di server. Script ini hanya:
   - menampilkan pesan validasi berbahasa Indonesia di bawah field (bukan bubble browser),
     lengkap dengan aria-invalid & aria-describedby;
   - memeriksa waktu selesai > waktu mulai          (atribut data-after="id_waktu_mulai");
   - memeriksa peserta <= kapasitas ruang terpilih   (atribut data-capacity-from="id_select_ruang",
                                                      option ruang membawa data-capacity);
   - menolak isian yang hanya berisi spasi            (atribut data-not-blank);
   - merapikan spasi di awal/akhir token QR           (atribut data-trim);
   - memfokuskan field pertama yang ditandai galat oleh server.
   FE-06:
   - konfirmasi sebelum hapus/batal                   (atribut data-confirm="pesan" pada <form>);
   - tombol cetak laporan → window.print()             (atribut data-print, tombol disembunyikan tanpa JS);
   - pembaruan otomatis dashboard yang bisa dijeda     (atribut data-auto-refresh="detik"); refresh ditunda
     saat pengguna sedang mengisi form, tab tidak terlihat, atau dialog cetak terbuka.
     Tanpa JS, <noscript><meta http-equiv="refresh"></noscript> di halaman tetap berlaku.
   Script ini TIDAK mengubah status booking apa pun; semua transaksi tetap lewat server.
   ============================================================================= */
(function () {
    'use strict';

    if (window.__campusSpaceInit) {
        return;
    }
    window.__campusSpaceInit = true;

    var MESSAGES = {
        valueMissing: function () { return 'Isian ini wajib diisi.'; },
        typeMismatch: function () { return 'Format isian tidak sesuai.'; },
        badInput: function () { return 'Masukkan nilai yang valid.'; },
        patternMismatch: function (el) { return el.dataset.patternMessage || 'Format isian tidak sesuai.'; },
        tooLong: function (el) { return 'Maksimal ' + el.maxLength + ' karakter.'; },
        tooShort: function (el) { return 'Minimal ' + el.minLength + ' karakter.'; },
        rangeUnderflow: function (el) { return 'Nilai minimal ' + el.min + '.'; },
        rangeOverflow: function (el) { return 'Nilai maksimal ' + el.max + '.'; },
        stepMismatch: function () { return 'Masukkan bilangan bulat.'; }
    };

    function messageFor(el) {
        if (el.validity.customError) {
            return el.validationMessage;
        }
        for (var key in MESSAGES) {
            if (el.validity[key]) {
                return MESSAGES[key](el);
            }
        }
        return el.validationMessage;
    }

    function setDescribedBy(el, id, add) {
        var ids = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        ids = ids.filter(function (item) { return item !== id; });
        if (add) {
            ids.push(id);
        }
        if (ids.length) {
            el.setAttribute('aria-describedby', ids.join(' '));
        } else {
            el.removeAttribute('aria-describedby');
        }
    }

    // Pesan galat dari server (<x-field-error>) disembunyikan begitu pengguna mengubah isian,
    // supaya tidak ada dua pesan untuk satu field.
    function hideServerError(el) {
        var serverError = el.id ? document.getElementById(el.id + '-error') : null;
        if (serverError && !serverError.hidden) {
            serverError.hidden = true;
            setDescribedBy(el, serverError.id, false);
            el.removeAttribute('aria-invalid');
        }
    }

    function showError(el) {
        if (!el.id) {
            return;
        }
        hideServerError(el);
        var id = el.id + '-client-error';
        var node = document.getElementById(id);
        if (!node) {
            node = document.createElement('p');
            node.id = id;
            node.className = 'cs-form-error';
            // Urutan konsisten: input → hint → pesan galat.
            var anchor = document.getElementById(el.id + '-error')
                || document.getElementById(el.id + '-hint')
                || document.getElementById(el.id + '-capacity')
                || el;
            anchor.insertAdjacentElement('afterend', node);
        }
        node.textContent = messageFor(el);
        node.hidden = false;
        el.setAttribute('aria-invalid', 'true');
        setDescribedBy(el, id, true);
    }

    function clearError(el) {
        if (!el.id) {
            return;
        }
        var node = document.getElementById(el.id + '-client-error');
        if (node) {
            node.hidden = true;
            node.textContent = '';
        }
        setDescribedBy(el, el.id + '-client-error', false);
        if (!document.getElementById(el.id + '-error') || document.getElementById(el.id + '-error').hidden) {
            el.removeAttribute('aria-invalid');
        }
    }

    function syncRules(form) {
        // Waktu selesai harus setelah waktu mulai (format datetime-local bisa dibandingkan sebagai teks).
        form.querySelectorAll('[data-after]').forEach(function (end) {
            var start = document.getElementById(end.dataset.after);
            if (!start) {
                return;
            }
            end.min = start.value || '';
            var invalid = start.value && end.value && end.value <= start.value;
            end.setCustomValidity(invalid ? 'Waktu selesai harus setelah waktu mulai.' : '');
        });

        // Jumlah peserta tidak boleh melebihi kapasitas ruang terpilih.
        form.querySelectorAll('[data-capacity-from]').forEach(function (input) {
            var select = document.getElementById(input.dataset.capacityFrom);
            var option = select && select.selectedOptions ? select.selectedOptions[0] : null;
            var capacity = option ? Number(option.dataset.capacity || 0) : 0;
            var info = document.getElementById(input.id + '-capacity');
            if (capacity > 0) {
                input.max = String(capacity);
                if (info) {
                    info.textContent = 'Kapasitas ruang terpilih: ' + capacity + ' orang.';
                }
            } else {
                input.removeAttribute('max');
                if (info) {
                    info.textContent = info.dataset.default || '';
                }
            }
            var over = capacity > 0 && input.value !== '' && Number(input.value) > capacity;
            input.setCustomValidity(over ? 'Jumlah peserta melebihi kapasitas ruang (' + capacity + ' orang).' : '');
        });

        // Isian tidak boleh hanya berisi spasi (textarea tidak mendukung atribut pattern).
        form.querySelectorAll('[data-not-blank]').forEach(function (el) {
            var blank = el.value !== '' && el.value.trim() === '';
            el.setCustomValidity(blank ? 'Isian tidak boleh hanya berisi spasi.' : '');
        });
    }

    function revalidate(form) {
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.willValidate || el.dataset.touched !== '1') {
                return;
            }
            if (el.validity.valid) {
                clearError(el);
            } else {
                showError(el);
            }
        });
    }

    function enhanceForm(form) {
        var focusPending = false;

        syncRules(form);

        form.addEventListener('input', function (event) {
            hideServerError(event.target);
            syncRules(form);
            revalidate(form);
        });

        form.addEventListener('change', function (event) {
            var el = event.target;
            if (el.matches('[data-trim]') && el.value !== el.value.trim()) {
                el.value = el.value.trim();
            }
            if (el.value !== '') {
                el.dataset.touched = '1';
            }
            syncRules(form);
            revalidate(form);
        });

        // Saat submit diblokir validasi, browser memicu event "invalid" (tidak bubble -> pakai capture).
        // Bubble bawaan browser diganti pesan di bawah field; field pertama yang salah difokuskan.
        form.addEventListener('invalid', function (event) {
            var el = event.target;
            event.preventDefault();
            el.dataset.touched = '1';
            showError(el);
            if (!focusPending) {
                focusPending = true;
                el.focus();
                window.setTimeout(function () { focusPending = false; }, 0);
            }
        }, true);
    }

    // Konfirmasi sebelum aksi destruktif. Server tetap wajib memvalidasi aksi ini.
    function initConfirm() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (form.matches('form[data-confirm]') && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    }

    function initPrint() {
        document.querySelectorAll('[data-print]').forEach(function (button) {
            button.hidden = false;
            button.addEventListener('click', function () { window.print(); });
        });
    }

    function initAutoRefresh() {
        var root = document.querySelector('[data-auto-refresh]');
        if (!root) {
            return;
        }
        var seconds = Number(root.dataset.autoRefresh) || 30;
        var paused = false;
        var printing = false;
        var dirty = false;
        var status = document.querySelector('[data-refresh-status]');
        var toggle = document.querySelector('[data-refresh-toggle]');

        document.addEventListener('input', function (event) {
            if (event.target.closest('form')) {
                dirty = true;
            }
        });
        window.addEventListener('beforeprint', function () { printing = true; });
        window.addEventListener('afterprint', function () { printing = false; });

        function render() {
            if (status) {
                status.textContent = paused
                    ? 'Pembaruan otomatis dijeda'
                    : 'Live · diperbarui otomatis tiap ' + seconds + ' detik';
            }
            if (toggle) {
                toggle.hidden = false;
                toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
                toggle.textContent = paused ? 'Lanjutkan pembaruan' : 'Jeda pembaruan';
            }
        }

        function busy() {
            var active = document.activeElement;
            var editing = active && active.closest && active.closest('form') && active.matches('input, select, textarea');
            return paused || printing || dirty || document.hidden || editing;
        }

        function tick() {
            if (busy()) {
                window.setTimeout(tick, seconds * 1000);
                return;
            }
            window.location.reload();
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                paused = !paused;
                render();
            });
        }
        render();
        window.setTimeout(tick, seconds * 1000);
    }

    function init() {
        initConfirm();
        initPrint();
        initAutoRefresh();
        document.querySelectorAll('form[data-cs-validate]').forEach(enhanceForm);

        // Fokus ke field pertama yang ditandai galat oleh server setelah redirect.
        var firstInvalid = document.querySelector('form [aria-invalid="true"]');
        if (firstInvalid) {
            firstInvalid.focus();
        }

        // <details data-focus-on-open>: fokus ke isian pertama saat panel dibuka (form Reject).
        document.querySelectorAll('details[data-focus-on-open]').forEach(function (details) {
            details.addEventListener('toggle', function () {
                if (details.open) {
                    // Lewati input hidden (_token dari @csrf) yang tidak bisa difokus.
                    var field = details.querySelector('textarea, select, input:not([type="hidden"])');
                    if (field) {
                        field.focus();
                    }
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
