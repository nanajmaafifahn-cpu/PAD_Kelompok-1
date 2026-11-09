"""Generator wireframe low-fidelity Campus Space (PAD-23).
Satu HTML per halaman; layout responsif sehingga file yang sama dirender
pada lebar desktop (1440) dan ponsel (390)."""
import os, html

OUT = os.path.dirname(os.path.abspath(__file__))  # tulis HTML ke folder ini
os.makedirs(OUT, exist_ok=True)

CSS = r"""
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DejaVu Sans',Arial,sans-serif;background:#f4f4f2;color:#2b2b2b;font-size:13px;line-height:1.45}
.tag{position:absolute;top:-9px;right:8px;background:#fff;border:1px solid #2a78d6;color:#1c5cab;font:600 10px/1 'DejaVu Sans Mono',monospace;padding:3px 5px;border-radius:3px;z-index:3;white-space:nowrap}
.sem{position:relative;outline:1.5px dashed #86b6ef;outline-offset:3px}
.app{display:grid;grid-template-columns:232px 1fr;min-height:100vh}
aside.side{background:#d9d9d6;padding:18px 14px;display:flex;flex-direction:column;gap:14px;position:relative}
.logo{display:flex;gap:8px;align-items:center}
.logo .mark{width:34px;height:34px;border:2px solid #555;border-radius:8px;background:#fff;display:grid;place-items:center;font-weight:700}
.logo b{display:block;font-size:14px}.logo small{font-size:9px;letter-spacing:2px;color:#666}
.menu-h{font-size:9px;letter-spacing:2px;color:#666;margin-top:8px}
.menu a{display:flex;gap:8px;align-items:center;padding:8px 10px;border-radius:6px;color:#333;text-decoration:none;font-weight:600}
.menu a.on{background:#fff;border:1.5px solid #777}
.ico{width:14px;height:14px;border:1.5px solid #777;border-radius:3px;display:inline-block;flex:none}
.side .promo{margin-top:auto;border:1.5px solid #999;border-radius:8px;padding:10px;background:#e8e8e5;font-size:11px}
.col{display:flex;flex-direction:column;min-width:0}
header.top{height:58px;background:#fff;border-bottom:1.5px solid #bbb;display:flex;align-items:center;gap:12px;padding:0 24px;position:relative}
.burger{display:none;width:34px;height:34px;border:1.5px solid #777;border-radius:6px;place-items:center;font-size:16px}
.top .fac{color:#666}.top .sp{flex:1}
.avatar{width:32px;height:32px;border-radius:50%;border:1.5px solid #777;background:#e5e5e2;display:grid;place-items:center;font-size:11px;font-weight:700}
.who b{display:block;font-size:12px}.who small{color:#777;font-size:10px}
main.content{padding:26px 40px 30px;display:flex;flex-direction:column;gap:20px;position:relative;flex:1}
footer.foot{display:flex;justify-content:space-between;padding:14px 40px;color:#777;font-size:11px;border-top:1px solid #ddd;position:relative}
.crumb{color:#777;font-size:11px}
.ph{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap}
.eyebrow{font-size:10px;letter-spacing:2.5px;color:#666;font-weight:700}
h1{font-size:30px;line-height:1.15;margin:4px 0 4px}
h2{font-size:16px}
.sub{color:#666}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1.5px solid #555;border-radius:7px;padding:8px 14px;font-weight:700;font-size:12px;background:#fff;white-space:nowrap}
.btn.pri{background:#555;color:#fff}
.btn.sm{padding:4px 9px;font-size:11px}
.btn.ghost{border-style:dashed}
.card{background:#fff;border:1.5px solid #aaa;border-radius:10px;padding:16px;position:relative}
.grid{display:grid;gap:14px}
.g4{grid-template-columns:repeat(4,1fr)}.g3{grid-template-columns:repeat(3,1fr)}.g2{grid-template-columns:1fr 1fr}
.g21{grid-template-columns:2fr 1fr}
.stat .lbl{font-weight:600;font-size:12px}.stat .num{font-size:28px;font-weight:700;margin:4px 0}.stat .hint{color:#777;font-size:11px}
.stat .ico{position:absolute;right:14px;top:14px;width:26px;height:26px;border-radius:6px}
.line{height:8px;background:#ddd;border-radius:4px;margin:5px 0}
.badge{display:inline-block;border:1.5px solid #888;border-radius:10px;padding:1px 8px;font-size:10px;font-weight:700;background:#f0f0ee}
.badge.d{border-style:dashed}
.img{background:repeating-linear-gradient(135deg,#e6e6e3 0 10px,#ededeb 10px 20px);border:1.5px solid #aaa;border-radius:8px;display:grid;place-items:center;color:#777;font-size:11px;position:relative}
.img::before{content:'';position:absolute;inset:0;background:linear-gradient(to top right,transparent calc(50% - 1px),#c9c9c6 50%,transparent calc(50% + 1px)),linear-gradient(to top left,transparent calc(50% - 1px),#c9c9c6 50%,transparent calc(50% + 1px));border-radius:8px}
.img span{position:relative;background:#f4f4f2;padding:2px 6px;border-radius:4px}
table{width:100%;border-collapse:collapse;font-size:12px}
th{text-align:left;font-size:10px;letter-spacing:1px;color:#666;padding:8px 10px;background:#ececea;border-bottom:1.5px solid #bbb}
td{padding:10px;border-bottom:1px solid #ddd;vertical-align:top}
td small{display:block;color:#888;font-size:10px}
.tbl{padding:0}
.tbl table{border-radius:0 0 10px 10px;overflow:hidden}
.tag.in{top:4px}
.tag.hd{right:auto;left:8px;top:auto;bottom:-9px}
.filter4{display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:10px;align-items:end}
.list.block > div{display:block}
.tbl .th{display:flex;justify-content:space-between;align-items:center;padding:14px 16px}
.field{display:flex;flex-direction:column;gap:4px;position:relative}
.field label{font-weight:700;font-size:11px}
.req{color:#555}
.input{height:34px;border:1.5px solid #999;border-radius:6px;background:#fff;padding:0 9px;display:flex;align-items:center;color:#999;font-size:11px;justify-content:space-between}
.input.area{height:70px;align-items:flex-start;padding-top:8px}
.attr{font:10px 'DejaVu Sans Mono',monospace;color:#1c5cab}
.filter{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end}
.chips{display:flex;gap:6px;flex-wrap:wrap}
.pag{display:flex;justify-content:space-between;align-items:center;color:#777;font-size:11px}
.pag .p{display:flex;gap:6px}.pag .p span{border:1.5px solid #aaa;border-radius:6px;padding:3px 9px;background:#fff}.pag .p .on{background:#555;color:#fff;border-color:#555}
.cal{display:grid;grid-template-columns:repeat(7,1fr);gap:8px}
.day{background:#fff;border:1.5px solid #aaa;border-radius:8px;padding:8px;min-height:230px}
.day.today{border-width:2.5px;border-color:#555}
.day .d{font-size:10px;color:#777}.day .n{font-size:20px;font-weight:700;margin-bottom:6px}
.ev{border:1.5px solid #888;border-left-width:4px;border-radius:5px;padding:5px;margin-bottom:6px;font-size:10px;background:#f5f5f3}
.ev.b{border-style:dashed;border-left-style:solid}
.legend{display:flex;gap:14px;color:#666;font-size:11px;align-items:center}
.sw{display:inline-block;width:12px;height:10px;border:1.5px solid #777;margin-right:4px;vertical-align:-1px}
.sw.b{border-style:dashed}
.bar{display:grid;grid-template-columns:150px 1fr 60px;gap:10px;align-items:center;font-size:11px;margin:6px 0}
.track{height:14px;background:#f0f0ee;border:1px solid #ddd;border-radius:3px;position:relative}
.track i{position:absolute;left:0;height:6px;border-radius:2px}
.track i.s{top:1px;background:#888}.track i.a{top:7px;background:#c4c4c1;border:1px solid #888}
.dl{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.dl dt{font-size:10px;color:#777}.dl dd{font-weight:700}
.qr{width:120px;height:120px;margin:10px auto;border:1.5px solid #777;background:
 linear-gradient(90deg,#999 25%,transparent 25% 50%,#999 50% 75%,transparent 75%) 0 0/24px 24px,
 linear-gradient(#fff 50%,transparent 50%) 0 0/24px 24px;position:relative}
.qr::before,.qr::after{content:'';position:absolute;width:30px;height:30px;border:6px solid #555;background:#fff}
.qr::before{top:4px;left:4px}.qr::after{bottom:4px;right:4px}
.note{border:1.5px dashed #999;border-radius:8px;padding:10px 12px;color:#555;background:#fafaf8;font-size:11px}
.row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.list > div{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #e3e3e0;gap:8px}
.mobile-only{display:none}
/* ---------- MOBILE ---------- */
@media (max-width:700px){
 .app{grid-template-columns:1fr}
 aside.side{display:none}
 .burger{display:grid}
 header.top{padding:0 12px;height:54px}
 .who{display:none}
 main.content{padding:16px 12px 20px;gap:16px}
 footer.foot{padding:12px;flex-direction:column;gap:4px}
 h1{font-size:24px}
 .g4,.g3,.g2,.g21,.filter,.filter4{grid-template-columns:1fr}
 .ph{align-items:flex-start;flex-direction:column}
 .cal{grid-template-columns:1fr}
 .day{min-height:0;display:grid;grid-template-columns:54px 1fr;gap:8px}
 .day .evs{display:flex;flex-direction:column}
 table,tbody,tr,td{display:block}
 thead{display:none}
 tr{border:1.5px solid #aaa;border-radius:8px;margin:10px;background:#fff}
 td{border-bottom:1px solid #eee;padding:8px 12px}
 td[data-l]::before{content:attr(data-l);display:block;font-size:9px;letter-spacing:1px;color:#888;font-weight:700;text-transform:uppercase}
 .bar{grid-template-columns:90px 1fr 46px}
 .mobile-only{display:block}
 .desk-only{display:none}
 .auth{grid-template-columns:1fr!important}
 .auth .brand{min-height:160px!important;padding:20px!important}
}
"""

BORROWER_MENU = [('Dashboard', 'dash'), ('Jelajahi Ruangan', 'rooms'), ('Jadwal Ruangan', 'sched'),
                 ('Peminjaman Saya', 'mine'), ('Pindai QR', 'scan')]
MANAGER_MENU = [('Dashboard', 'dash'), ('Jelajahi Ruangan', 'rooms'), ('Jadwal Ruangan', 'sched'),
                ('Peminjaman', 'bookings'), ('Laporan Utilisasi', 'util')]
MANAGER_ADMIN = [('Ruangan & Foto', 'manage'), ('Fasilitas', 'fac'), ('Cetak QR Ruangan', 'qr')]
ADMIN_EXTRA = [('Pengguna', 'users'), ('Unit Akademik', 'units')]


def tag(t):
    cls = {'aside': 'tag in', 'header': 'tag in hd'}.get(t, 'tag')
    return f'<span class="{cls}">&lt;{t}&gt;</span>'


def lines(*w):
    return ''.join(f'<div class="line" style="width:{x}%"></div>' for x in w)


def shell(title, role, active, body):
    menu = BORROWER_MENU if role == 'b' else MANAGER_MENU
    items = ''.join(f'<a class="{"on" if k == active else ""}"><i class="ico"></i>{n}</a>' for n, k in menu)
    extra = ''
    if role in ('m', 'a'):
        extra = '<div class="menu-h">PENGELOLAAN</div><div class="menu">' + ''.join(
            f'<a class="{"on" if k == active else ""}"><i class="ico"></i>{n}</a>' for n, k in MANAGER_ADMIN + (ADMIN_EXTRA if role == 'a' else [])) + '</div>'
    user = {'b': ('AP', 'Andi Pratama (Mahasiswa)', 'Mahasiswa / Dosen'),
            'm': ('KL', 'Kepala Laboratorium FRI', 'Pengelola Ruang / Kepala Lab'),
            'a': ('AA', 'Admin Akademik FRI', 'Admin')}[role]
    return f"""<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{html.escape(title)}</title><style>{CSS}</style></head><body>
<div class="app">
 <aside class="side sem">{tag('aside')}
  <div class="logo"><div class="mark">C</div><div><b>Campus Space</b><small>LABORATORIUM FRI</small></div></div>
  <nav class="sem" style="outline-offset:1px">{tag('nav')}<div class="menu-h">MENU UTAMA</div><div class="menu">{items}</div>{extra}</nav>
  <div class="promo"><b>Ruang untuk setiap kegiatan.</b>{lines(90, 70)}</div>
 </aside>
 <div class="col">
  <header class="top sem">{tag('header')}<div class="burger">☰</div><span class="fac">Fakultas Rekayasa Industri</span><span class="sp"></span>
   <div class="avatar">{user[0]}</div><div class="who"><b>{user[1]}</b><small>{user[2]}</small></div><span class="btn sm pri">Keluar</span></header>
  <main class="content sem">{tag('main')}{body}</main>
  <footer class="foot sem">{tag('footer')}<span>Campus Space</span><span>Kelola ruang. Catat penggunaan. Temukan peluang.</span></footer>
 </div>
</div></body></html>"""


def head(crumb, eyebrow, h, sub, action=''):
    return f"""<section class="sem" style="outline-offset:6px">{tag('section')}<div class="crumb">Campus Space / {crumb}</div>
<div class="ph"><div><div class="eyebrow">{eyebrow}</div><h1>{h}</h1><div class="sub">{sub}</div></div>{action}</div></section>"""


def stats(items):
    return '<div class="grid g4">' + ''.join(
        f'<div class="card stat"><i class="ico"></i><div class="lbl">{a}</div><div class="num">{b}</div><div class="hint">{c}</div></div>'
        for a, b, c in items) + '</div>'


def table(headers, rows, title='', action='', semantic='section'):
    th = ''.join(f'<th>{h}</th>' for h in headers)
    trs = ''
    for r in rows:
        trs += '<tr>' + ''.join(f'<td data-l="{headers[i]}">{c}</td>' for i, c in enumerate(r)) + '</tr>'
    top = f'<div class="th"><h2>{title}</h2>{action}</div>' if title else ''
    return f'<{semantic} class="card tbl">{tag(semantic) if semantic != "div" else ""}{top}<table><thead><tr>{th}</tr></thead><tbody>{trs}</tbody></table></{semantic}>'


def field(label, ph, attrs, kind='', req=True):
    star = ' <span class="req">*</span>' if req else ''
    return f'<div class="field"><label>{label}{star}</label><div class="input {kind}">{ph}</div><span class="attr">{attrs}</span></div>'


def pag(txt, n=2):
    return f'<div class="pag"><span>{txt}</span><div class="p"><span>Sebelumnya</span><span class="on">1</span>' + ''.join(
        f'<span>{i}</span>' for i in range(2, n + 1)) + '<span>Berikutnya</span></div></div>'


def room_card(name, code, kind, status='Tersedia sekarang'):
    return f"""<article class="card" style="padding:0">{''}<div class="img" style="height:110px;border:0;border-bottom:1.5px solid #aaa;border-radius:10px 10px 0 0"><span>Foto ruangan</span></div>
<div style="padding:12px"><div class="row" style="justify-content:space-between"><b>{name}</b><span class="badge">{status}</span></div>
<small style="color:#888">{code} · {kind}</small><div class="row" style="color:#777;font-size:11px;margin:8px 0">◻ 48 orang &nbsp; ◻ 48 komputer</div>
<span class="btn" style="width:100%">Lihat Ruangan</span></div></article>"""


PAGES = {}

# 01 Login --------------------------------------------------------------------
PAGES['01-login'] = ('Login', f"""<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login</title><style>{CSS}</style></head><body>
<div class="auth" style="display:grid;grid-template-columns:1fr 1fr;min-height:100vh">
 <section class="sem brand" style="background:#d9d9d6;padding:48px;display:flex;flex-direction:column;justify-content:space-between;min-height:100vh"><span class="tag in">&lt;section&gt;</span>
  <div class="logo"><div class="mark">C</div><div><b>Campus Space</b><small>LABORATORIUM FRI</small></div></div>
  <div><div class="eyebrow">SISTEM PEMINJAMAN RUANG</div><h1 style="font-size:32px">Ruang untuk setiap kegiatan.</h1>{lines(80, 65)}</div>
  <div class="img desk-only" style="height:180px"><span>Ilustrasi / foto kampus</span></div>
 </section>
 <main class="sem" style="display:grid;place-items:center;padding:32px 20px">{tag('main')}
  <form class="card sem" style="width:100%;max-width:380px;display:flex;flex-direction:column;gap:14px;padding:26px">{tag('form')}
   <div><div class="eyebrow">MASUK</div><h1 style="font-size:24px">Selamat datang kembali</h1><div class="sub">Masuk dengan akun kampus Anda.</div></div>
   {field('Email', 'nama@campus.test', 'type=email required · &lt;label for="email"&gt;')}
   {field('Kata Sandi', '••••••••', 'type=password required · &lt;label for="password"&gt;')}
   <label class="row" style="font-size:12px"><i class="ico"></i>Ingat saya</label>
   <span class="btn pri">Masuk</span>
   <div class="sub" style="text-align:center;font-size:11px">Belum punya akun? <u>Daftar</u></div>
  </form>
 </main>
</div></body></html>""")

# 02 Dashboard mahasiswa ------------------------------------------------------
PAGES['02-mhs-dashboard'] = ('Dashboard Mahasiswa', shell('Dashboard', 'b', 'dash',
    head('Dashboard', 'DASHBOARD MAHASISWA', 'Selamat datang, Andi.', 'Semua informasi ruangan dan aktivitas peminjaman Anda.', '<span class="btn pri">Temukan Ruangan</span>')
    + stats([('Peminjaman saya', '13', 'Seluruh pengajuan'), ('Menunggu persetujuan', '1', 'Dalam pemeriksaan'),
             ('Peminjaman aktif', '1', 'Disetujui / sedang digunakan'), ('Kegiatan selesai', '6', 'Check-out tercatat')])
    + f"""<article class="card">{tag('article')}<div class="eyebrow">PEMINJAMAN BERIKUTNYA</div>
<div class="ph"><div><h2>Laboratorium Komputer 1 · LAB-KOM-01</h2><div class="sub">Sen, 9 Nov 2026 · 10.00–12.00 WIB</div>
<div class="row" style="margin-top:6px"><span class="badge">Disetujui</span><span class="sub">Kerja kelompok tugas besar PAD</span></div></div>
<div class="row"><span class="btn">Detail</span><span class="sub">Check-in dibuka 09.45 WIB</span></div></div></article>"""
    + table(['Ruangan', 'Waktu', 'Keperluan', 'Status'], [
        ['R9<small>TULT-1005</small>', 'Sel, 10 Nov · 08.00–10.00', 'Rapat himpunan', '<span class="badge d">Ditolak</span>'],
        ['Lab Komputer 1<small>LAB-KOM-01</small>', 'Sen, 9 Nov · 10.00–12.00', 'Kerja kelompok PAD', '<span class="badge">Disetujui</span>'],
        ['R9<small>TULT-1005</small>', 'Sen, 9 Nov · 09.00–11.00', 'Latihan presentasi', '<span class="badge">Menunggu</span>'],
        ['R2<small>TULT-0807</small>', 'Min, 8 Nov · 10.36–12.36', 'Kerja kelompok PAD', '<span class="badge d">Tidak hadir</span>'],
    ], 'Peminjaman terbaru', '<u>Lihat semua</u>')))

# 03 Jelajahi ruangan ---------------------------------------------------------
cards = ''.join(room_card(n, c, k, s) for n, c, k, s in [
    ('Lab Komputer 1', 'LAB-KOM-01', 'Computer Lab', 'Tersedia sekarang'), ('Lab Manufaktur', 'LAB-MAN-01', 'Computer Lab', 'Tersedia sekarang'),
    ('R1', 'TULT-0805', 'Computer Lab', 'Tersedia sekarang'), ('R10', 'TULT-1007', 'Computer Lab', 'Sedang dipakai'),
    ('R11', 'TULT-1019', 'Class Lab', 'Tersedia sekarang'), ('R15', 'TULT-1114', 'Class Lab', 'Perawatan')])
PAGES['03-mhs-jelajahi-ruangan'] = ('Jelajahi Ruangan', shell('Jelajahi Ruangan', 'b', 'rooms',
    head('Jelajahi Ruangan', 'RUANG UNTUK KEGIATAN ANDA', 'Jelajahi laboratorium.', 'Temukan fasilitas dan kapasitas yang sesuai, lalu periksa jadwalnya.', '<span class="btn">Lihat Jadwal</span>')
    + f"""<form class="card sem filter">{tag('form')}
<div class="field"><label>Cari ruangan</label><div class="input">Nama ruangan atau kode…</div></div>
<div class="field"><label>Jenis</label><div class="input">Semua jenis ▾</div></div>
<div class="field"><label>Peserta</label><div class="input">Jumlah</div><span class="attr">type=number min=1</span></div>
<div class="field"><label>Fasilitas</label><div class="input">Semua fasilitas ▾</div></div>
<span class="btn pri">Cari</span></form>
<div class="row" style="justify-content:space-between"><span class="sub">19 ruangan ditemukan</span><span class="badge">FRI · Telkom University</span></div>
<section class="grid g3 sem">{tag('section')}{cards}</section>""" + pag('Menampilkan 1–9 dari 19', 3)))

# 04 Detail ruangan -----------------------------------------------------------
PAGES['04-mhs-detail-ruangan'] = ('Detail Ruangan', shell('Detail Ruangan', 'b', 'rooms',
    head('Jelajahi Ruangan / R1', 'COMPUTER LAB', 'Ruangan R1', 'TULT-0805 · Fakultas Rekayasa Industri', '<span class="btn pri">Ajukan Peminjaman</span>')
    + f"""<div class="grid g21">
<article class="card" style="display:flex;flex-direction:column;gap:14px">{tag('article')}
 <div class="img" style="height:230px"><span>Foto ruangan</span></div>
 <h2>Tentang ruangan</h2><div class="sub">Laboratorium komputer untuk praktikum dan kerja kelompok.</div>
 <dl class="dl"><div><dt>Kapasitas</dt><dd>48 orang</dd></div><div><dt>Komputer</dt><dd>48</dd></div>
 <div><dt>Lokasi</dt><dd>TULT · Lantai 8</dd></div><div><dt>Status operasional</dt><dd><span class="badge">Tersedia sekarang</span></dd></div></dl>
 <h2>Fasilitas tercatat</h2><div class="chips"><span class="badge">PC Komputer · 48</span><span class="badge">Proyektor · 1</span><span class="badge">AC · 2</span></div>
</article>
<aside class="card sem" style="display:flex;flex-direction:column;gap:10px;align-self:start">{tag('aside')}
 <h2>Jadwal mendatang</h2><div class="sub" style="font-size:11px">Hanya jadwal kuliah dan peminjaman yang sudah disetujui.</div>
 <div class="list"><div><span>Sen 9 Nov · 10.00–12.00</span><span class="badge">Disetujui</span></div><div><span>Sel 10 Nov · 13.00–15.00</span><span class="badge">Kuliah</span></div></div>
 <span class="btn">Buka Kalender</span>
</aside></div>"""))

# 05 Ajukan peminjaman --------------------------------------------------------
PAGES['05-mhs-ajukan-peminjaman'] = ('Ajukan Peminjaman', shell('Ajukan Peminjaman', 'b', 'mine',
    head('Peminjaman Saya / Ajukan', 'PENGAJUAN BARU', 'Ajukan peminjaman.', 'Isi data kegiatan. Pengelola akan memeriksa pengajuan Anda.')
    + f"""<div class="grid g21">
<form class="card sem" style="display:flex;flex-direction:column;gap:14px">{tag('form')}
 <dl class="dl" style="background:#f4f4f2;border-radius:8px;padding:10px"><div><dt>Nama peminjam</dt><dd>Andi Pratama (Mahasiswa)</dd></div><div><dt>Peran</dt><dd>Mahasiswa / Dosen</dd></div></dl>
 <div class="grid g2">{field('Ruang / Lab', '— Pilih ruang — ▾', 'select required · &lt;label for="room_id"&gt;')}{field('Jumlah Peserta', '20', 'type=number required min=1 max=kapasitas')}</div>
 <div class="grid g2">{field('Waktu Mulai', 'dd/mm/yyyy --:--', 'type=datetime-local required')}{field('Waktu Selesai', 'dd/mm/yyyy --:--', 'type=datetime-local required · harus setelah mulai')}</div>
 {field('Keperluan Peminjaman', 'Contoh: kerja kelompok tugas besar', 'textarea required maxlength=255', 'area')}
 <div class="row" style="justify-content:flex-end"><span class="btn">Batal</span><span class="btn pri">Kirim Pengajuan</span></div>
</form>
<aside class="card sem" style="align-self:start;display:flex;flex-direction:column;gap:8px">{tag('aside')}
 <h2>Aturan peminjaman</h2>
 <div class="list block"><div>1. Jam operasional 07.00–17.00, Senin–Jumat</div><div>2. Check-in lewat QR paling lambat 15 menit setelah mulai</div><div>3. Tidak check-in = tercatat <b>Tidak hadir</b> (ghost booking)</div><div>4. Jadwal bentrok otomatis ditolak sistem</div></div>
</aside></div>"""))

# 06 Jadwal -------------------------------------------------------------------
days = [('Senin', '02', [('08.00–10.00', 'R3', 'Statistika Industri', 0), ('10.00–12.00', 'R1', 'Praktikum Basis Data', 0)]),
        ('Selasa', '03', [('07.30–09.30', 'R8', 'Sistem Produksi', 0), ('10.00–12.00', 'R1', 'Praktikum Basis Data', 0)]),
        ('Rabu', '04', [('08.00–10.00', 'R3', 'Statistika Industri', 0), ('15.00–17.00', 'R5', 'Praktikum Pemrograman', 0)]),
        ('Kamis', '05', [('13.00–15.00', 'R6', 'Perancangan Aplikasi', 0)]),
        ('Jumat', '06', [('10.00–12.00', 'R1', 'Praktikum Basis Data', 0), ('15.00–17.00', 'R5', 'Praktikum Pemrograman', 0)]),
        ('Sabtu', '07', [('09.00–11.00', 'R2', 'Pengolahan data', 1)]),
        ('Minggu', '08', [('09.56–11.56', 'R7', 'Asistensi praktikum', 1)])]
cal = ''.join(f'<article class="day {"today" if n == "08" else ""}"><div><div class="d">{d}</div><div class="n">{n}</div></div><div class="evs">' +
              ''.join(f'<div class="ev {"b" if b else ""}"><b>{t}</b><br>{r} · {x}</div>' for t, r, x, b in ev) + '</div></article>' for d, n, ev in days)
PAGES['06-mhs-jadwal'] = ('Jadwal Ruangan', shell('Jadwal Ruangan', 'b', 'sched',
    head('Jadwal Ruangan', 'KALENDER LABORATORIUM', 'Jadwal ruangan.', 'Jadwal akademik dan peminjaman yang telah disetujui.', '<span class="btn pri">Ajukan Peminjaman</span>')
    + f"""<div class="row" style="justify-content:space-between"><div class="row"><span class="btn sm">‹</span><h2>02 Nov – 08 Nov 2026</h2><span class="btn sm">›</span></div>
<div class="legend"><span><i class="sw"></i>Jadwal kuliah</span><span><i class="sw b"></i>Peminjaman disetujui</span><span>WIB · 17 kegiatan</span></div></div>
<section class="cal sem">{tag('section')}{cal}</section>
<div class="note">Slot kosong = belum ada jadwal kuliah atau peminjaman yang disetujui. Pengelola melihat panel tambahan <b>Kelola jadwal</b> (tabel + form Tambah Jadwal) di bawah kalender.</div>"""))

# 07 Peminjaman saya ----------------------------------------------------------
act = '<span class="btn sm">Detail</span>'
PAGES['07-mhs-peminjaman-saya'] = ('Peminjaman Saya', shell('Peminjaman Saya', 'b', 'mine',
    head('Peminjaman Saya', 'PENGAJUAN & AKTIVITAS', 'Peminjaman saya.', 'Pantau keputusan, akses ruangan, dan riwayat penggunaan.', '<span class="btn pri">Ajukan Peminjaman</span>')
    + '<div class="sub">13 pengajuan</div>'
    + table(['Ruang', 'Waktu', 'Keperluan', 'Status', 'Tindakan'], [
        ['R9<small>TULT-1005</small>', 'Sel, 10 Nov · 08.00–10.00', 'Rapat himpunan', '<span class="badge d">Ditolak</span>', act],
        ['Lab Komputer 1<small>LAB-KOM-01</small>', 'Sen, 9 Nov · 10.00–12.00', 'Kerja kelompok PAD', '<span class="badge">Disetujui</span>', act + ' <span class="btn sm ghost">Batalkan</span>'],
        ['R9<small>TULT-1005</small>', 'Sen, 9 Nov · 09.00–11.00', 'Latihan presentasi', '<span class="badge">Menunggu</span>', act + ' <span class="btn sm">Ubah</span> <span class="btn sm ghost">Batalkan</span>'],
        ['R2<small>TULT-0807</small>', 'Sab, 7 Nov · 09.00–11.00', 'Pengolahan data', '<span class="badge">Selesai</span>', act],
        ['Ruang Kelas 202<small>KLS-202</small>', 'Kam, 29 Okt · 09.00–11.00', 'Belajar kelompok', '<span class="badge d">Tidak hadir</span>', act],
    ], semantic='section')
    + pag('Menampilkan 1–10 dari 13')))

# 08 Pindai QR ----------------------------------------------------------------
PAGES['08-mhs-pindai-qr'] = ('Pindai QR', shell('Pindai QR', 'b', 'scan',
    head('Pindai QR', 'PENGGUNAAN AKTUAL', 'Check-in / Check-out.', 'Pindai QR yang ditempel di ruangan untuk mencatat penggunaan Anda.')
    + f"""<div class="grid g2">
<section class="card" style="display:flex;flex-direction:column;gap:12px">{tag('section')}<h2>Pindai QR ruangan</h2>
 <div class="img" style="height:190px"><span>Area kamera (preview)</span></div>
 <div class="row"><span class="btn pri">Izinkan Kamera</span><span class="btn">Pindai dari Foto</span></div>
 <div class="sub" style="font-size:11px">Kamera memerlukan HTTPS atau localhost.</div>
 <details open style="border-top:1px solid #ddd;padding-top:8px"><summary><b>Masukkan tautan QR secara manual</b></summary>
 <div class="row" style="margin-top:8px"><div class="input" style="flex:1">https://…/scan/kode-qr</div><span class="btn">Buka</span></div></details>
</section>
<section class="card" style="display:grid;place-items:center;text-align:center;gap:8px;min-height:260px">{tag('section')}
 <div><i class="ico" style="width:40px;height:40px;border-radius:10px"></i><h2 style="margin-top:8px">Siap memindai</h2>
 <div class="sub" style="max-width:260px">Setelah QR terbaca, peminjaman Anda di ruangan itu tampil untuk dikonfirmasi (Check-in / Check-out).</div></div>
</section></div>"""))

# 09 Dashboard pengelola ------------------------------------------------------
pend = ''.join(f"""<div style="display:block"><div class="row" style="justify-content:space-between;align-items:flex-start"><div><b>{n}</b><div class="sub" style="font-size:11px">{d}</div></div>
<div class="row"><span class="btn sm pri">✓ Setujui</span><span class="btn sm">✕ Tolak</span></div></div></div>""" for n, d in [
    ('Andi Pratama (Mahasiswa)', 'R9 · Sen, 9 Nov · 09.00–11.00 · 15 orang'), ('Dr. Sinta Rahmawati (Dosen)', 'Lab Manufaktur · Sel, 10 Nov · 13.00–15.00 · 20 orang')])
status = ''.join(f'<div><span>{r}</span><span class="badge {"d" if s != "Tersedia" else ""}">{s}</span></div>' for r, s in [
    ('Ruang Kelas 201', 'Tersedia'), ('Ruang Kelas 202', 'Tersedia'), ('Ruang Kelas 203', 'Perawatan'), ('Lab Komputer 1', 'Tersedia'),
    ('Lab Manufaktur', 'Tersedia'), ('R1', 'Tersedia'), ('R2', 'Sedang dipakai'), ('R3', 'Tersedia'), ('…', '')])
PAGES['09-pengelola-dashboard'] = ('Dashboard Pengelola', shell('Dashboard', 'm', 'dash',
    head('Dashboard', 'DASHBOARD PENGELOLA RUANG', 'Selamat datang, Kepala.', 'Status ruang saat ini dan pengajuan yang menunggu keputusan.', '<span class="btn pri">Lihat Laporan</span>')
    + stats([('Ruangan aktif', '17', 'Selain yang sedang perawatan'), ('Tersedia sekarang', '17', 'Tanpa peminjaman berjalan'),
             ('Sedang digunakan', '0', 'Check-in telah tercatat'), ('Menunggu persetujuan', '2', 'Pengajuan peminjaman')])
    + f"""<div class="grid g21">
<section class="card">{tag('section')}<div class="row" style="justify-content:space-between"><h2>Menunggu persetujuan</h2><u>Lihat semua</u></div><div class="list">{pend}</div></section>
<aside class="card sem" style="align-self:start">{tag('aside')}<div class="row" style="justify-content:space-between"><h2>Status ruang sekarang</h2><u>Jelajahi</u></div><div class="list">{status}</div></aside>
</div>"""))

# 10 Kelola peminjaman --------------------------------------------------------
ap = '<div class="row"><span class="btn sm">Detail</span><span class="btn sm pri">Setujui</span><span class="btn sm">Tolak</span></div>'
PAGES['10-pengelola-peminjaman'] = ('Kelola Peminjaman', shell('Peminjaman', 'm', 'bookings',
    head('Peminjaman', 'PENGAJUAN & AKTIVITAS', 'Kelola peminjaman.', 'Setujui, tolak, dan pantau check-in setiap peminjaman.', '<span class="btn pri">Ajukan Peminjaman</span>')
    + '<div class="row"><span class="badge">Semua</span><span class="badge d">Menunggu</span><span class="badge d">Disetujui</span><span class="badge d">Tidak hadir</span><span class="sub">21 pengajuan</span></div>'
    + table(['Peminjam', 'Ruang', 'Waktu', 'Peserta', 'Status', 'Tindakan'], [
        ['Dr. Sinta Rahmawati<small>Dosen</small>', 'Lab Manufaktur<small>LAB-MAN-01</small>', 'Sel, 10 Nov · 13.00–15.00<small>Asistensi praktikum PPIC</small>', '20', '<span class="badge">Menunggu</span>', ap],
        ['Andi Pratama<small>Mahasiswa</small>', 'R9<small>TULT-1005</small>', 'Sel, 10 Nov · 08.00–10.00<small>Rapat himpunan</small>', '45', '<span class="badge d">Ditolak</span>', '<span class="btn sm">Detail</span><small>Alasan: ruang dipakai ujian</small>'],
        ['Andi Pratama<small>Mahasiswa</small>', 'Lab Komputer 1<small>LAB-KOM-01</small>', 'Sen, 9 Nov · 10.00–12.00<small>Kerja kelompok PAD</small>', '8', '<span class="badge">Disetujui</span>', '<span class="btn sm">Detail</span><small>Menunggu check-in pemilik</small>'],
        ['Dr. Sinta Rahmawati<small>Dosen</small>', 'R7<small>TULT-0913</small>', 'Min, 8 Nov · 09.56–11.56', '35', '<span class="badge">Check-in</span>', '<span class="btn sm">Detail</span><small>Sedang digunakan</small>'],
        ['Andi Pratama<small>Mahasiswa</small>', 'R2<small>TULT-0807</small>', 'Min, 8 Nov · 10.36–12.36', '20', '<span class="badge d">Tidak hadir</span>', '<span class="btn sm">Detail</span>'],
    ]) + pag('Menampilkan 1–10 dari 21', 3)))

# 11 Laporan utilisasi --------------------------------------------------------
bars = ''.join(f'<div class="bar"><span>{r}</span><div class="track"><i class="s" style="width:{s}%"></i><i class="a" style="width:{a}%"></i></div><span>{s}% / {a}%</span></div>'
               for r, s, a in [('Lab Komputer 1', 28, 11), ('Lab Manufaktur', 26, 22), ('Ruang Kelas 201', 10, 0), ('Ruang Kelas 202', 13, 2), ('R1', 12, 0), ('R2', 0, 1), ('…', 0, 0)])
PAGES['11-pengelola-laporan-utilisasi'] = ('Laporan Utilisasi', shell('Laporan Utilisasi', 'm', 'util',
    head('Laporan Utilisasi', 'ANALITIK & EVALUASI', 'Bagaimana ruang digunakan?', 'Bandingkan jadwal dengan penggunaan yang tercatat lewat check-in dan check-out.', '<span class="btn">Cetak</span>')
    + f"""<form class="card sem filter4">{tag('form')}
<div class="field"><label>Dari tanggal</label><div class="input">26/10/2026 ▭</div><span class="attr">type=date</span></div>
<div class="field"><label>Sampai tanggal</label><div class="input">01/11/2026 ▭</div><span class="attr">type=date · ≥ dari</span></div>
<div class="sub" style="font-size:11px">Jam tersedia per ruang: 50 jam (07.00–17.00, Sen–Jum)</div><span class="btn pri">Tampilkan Laporan</span></form>"""
    + stats([('Jam terjadwal', '38,5', 'Dalam jam operasional'), ('Jam aktual', '17,1', 'Check-in sampai check-out'),
             ('Tidak digunakan', '3', 'Disetujui tanpa check-in (ghost)'), ('Sesi belum lengkap', '0', 'Sudah check-in, belum check-out')])
    + f"""<section class="card">{tag('section')}<div class="row" style="justify-content:space-between"><div><h2>Terjadwal vs aktual</h2><div class="sub" style="font-size:11px">Persentase dari jam tersedia per ruang</div></div>
<div class="legend"><span><i class="sw" style="background:#888"></i>Terjadwal</span><span><i class="sw" style="background:#c4c4c1"></i>Aktual</span></div></div>{bars}</section>
<div class="grid g2"><section class="card">{tag('section')}<h2>Ruang kurang dimanfaatkan</h2><div class="sub" style="font-size:11px">Utilisasi &lt; 30%</div>
<div class="list"><div>KLS-202 Ruang Kelas 202<span>aktual 2% · terjadwal 13%</span></div><div>LAB-KOM-01 Lab Komputer 1<span>aktual 11% · terjadwal 28%</span></div><div>…</div></div></section>
<section class="card">{tag('section')}<h2>Ruang sangat padat</h2><div class="sub" style="font-size:11px">Utilisasi ≥ 80%</div><div class="note" style="margin-top:8px">Tidak ada ruang sangat padat pada periode terpilih.</div></section></div>"""
    + table(['Ruang', 'Tipe', 'Terjadwal', 'Aktual', 'Utilisasi', 'Ghost', 'Kategori'], [
        ['Lab Komputer 1<small>LAB-KOM-01</small>', 'Lab', '14,0 j', '6,2 j', '11,4%', '2 (40%)', '<span class="badge d">Kurang dimanfaatkan</span>'],
        ['Lab Manufaktur<small>LAB-MAN-01</small>', 'Lab', '13,0 j', '9,7 j', '22,4%', '0', '<span class="badge d">Kurang dimanfaatkan</span>'],
        ['Ruang Kelas 202<small>KLS-202</small>', 'Kelas', '6,5 j', '1,2 j', '2,3%', '1 (50%)', '<span class="badge d">Kurang dimanfaatkan</span>'],
    ], 'Rincian utilisasi per ruang')))

# 12 Ruangan & foto -----------------------------------------------------------
ra = lambda dele: '<div class="row"><span class="btn sm">Edit</span><span class="btn sm">Fasilitas</span>' + ('<span class="btn sm pri">Hapus</span>' if dele else '<small>Tidak dapat dihapus</small>') + '</div>'
thumb = lambda n, c: f'<div class="row" style="flex-wrap:nowrap"><div class="img" style="width:46px;height:34px;flex:none"></div><div><b>{n}</b><small>{c}</small></div></div>'
PAGES['12-pengelola-kelola-ruangan'] = ('Ruangan & Foto', shell('Ruangan & Foto', 'm', 'manage',
    head('Ruangan & Foto', 'DATA MASTER', 'Ruangan &amp; Foto.', 'Kelola data ruang yang digunakan pada peminjaman dan laporan.', '<span class="btn pri">Tambah Ruang</span>')
    + table(['Ruangan', 'Informasi', 'Tindakan'], [
        [thumb('Ruang Kelas 201', 'KLS-201'), 'Class Lab · 60 orang · FRI<br><span class="badge">Tersedia</span> <small>Belum ada foto · 1 fasilitas · 1 peminjaman</small>', ra(False)],
        [thumb('Ruang Kelas 203', 'KLS-203'), 'Class Lab · 40 orang · FRI<br><span class="badge d">Perawatan</span> <small>Belum ada foto · 1 fasilitas · 0 peminjaman</small>', ra(True)],
        [thumb('Lab Komputer 1', 'LAB-KOM-01'), 'Computer Lab · 40 orang · FRI<br><span class="badge">Tersedia</span> <small>2 fasilitas · 6 peminjaman</small>', ra(False)],
        [thumb('R1', 'TULT-0805'), 'Computer Lab · 48 orang · FRI<br><span class="badge">Tersedia</span> <small>3 fasilitas · 0 peminjaman</small>', ra(True)],
        [thumb('R2', 'TULT-0807'), 'Computer Lab · 48 orang · FRI<br><span class="badge">Tersedia</span> <small>3 fasilitas · 2 peminjaman</small>', ra(False)],
    ]) + pag('Menampilkan 1–15 dari 19')
    + '<div class="note">Tombol <b>Tambah Ruang / Edit</b> membuka form: Unit Akademik*, Kode Ruang* (maxlength 30), Nama Ruang* (maxlength 150), Tipe Ruang*, Gedung*, Lantai, Kapasitas* (number min 1), Status*, Deskripsi, Foto (JPG/PNG/WebP ≤ 2 MB). Ruang yang punya riwayat peminjaman tidak dapat dihapus.</div>'))

# 13 Cetak QR -----------------------------------------------------------------
qrs = ''.join(f'<article class="card" style="text-align:center"><div class="eyebrow">CAMPUS SPACE · FRI</div><h2 style="font-size:20px">{n}</h2><div class="sub">{c}</div><div class="qr"></div><b style="font-size:11px">Pindai untuk check-in / check-out</b><div class="attr" style="margin:4px 0">http://…/scan/{c.lower()}</div><span class="btn sm ghost">Perbarui QR</span></article>'
              for n, c in [('Ruang Kelas 201', 'KLS-201'), ('Ruang Kelas 202', 'KLS-202'), ('Lab Komputer 1', 'LAB-KOM-01'), ('Lab Manufaktur', 'LAB-MAN-01'), ('R1', 'TULT-0805'), ('R2', 'TULT-0807')])
PAGES['13-pengelola-cetak-qr'] = ('Cetak QR Ruangan', shell('Cetak QR Ruangan', 'm', 'qr',
    head('Cetak QR Ruangan', 'CHECK-IN DI LOKASI', 'QR ruangan.', 'Cetak dan tempel QR pada pintu ruangan yang sesuai.', '<span class="btn pri">Cetak QR</span>')
    + f'<section class="card">{tag("section")}<div class="field"><label>Alamat Campus Space yang bisa dibuka ponsel</label><div class="input">http://localhost:8000</div></div><div class="sub" style="font-size:11px;margin-top:6px">Default dari APP_URL. Untuk dipindai lewat Wi-Fi, ganti localhost dengan IP komputer server.</div></section>'
    + f'<section class="grid g3 sem">{tag("section")}{qrs}</section>'))


# ======================= Halaman tambahan (14–21) =======================
def auth_page(title, eyebrow, h, sub, fields, btn, foot):
    return f"""<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{title}</title><style>{CSS}</style></head><body>
<div class="auth" style="display:grid;grid-template-columns:1fr 1fr;min-height:100vh">
 <section class="sem brand" style="background:#d9d9d6;padding:48px;display:flex;flex-direction:column;justify-content:space-between;min-height:100vh"><span class="tag in">&lt;section&gt;</span>
  <div class="logo"><div class="mark">C</div><div><b>Campus Space</b><small>LABORATORIUM FRI</small></div></div>
  <div><div class="eyebrow">SISTEM PEMINJAMAN RUANG</div><h1 style="font-size:32px">Ruang untuk setiap kegiatan.</h1>{lines(80, 65)}</div>
  <div class="img desk-only" style="height:180px"><span>Ilustrasi / foto kampus</span></div>
 </section>
 <main class="sem" style="display:grid;place-items:center;padding:32px 20px">{tag('main')}
  <form class="card sem" style="width:100%;max-width:380px;display:flex;flex-direction:column;gap:14px;padding:26px">{tag('form')}
   <div><div class="eyebrow">{eyebrow}</div><h1 style="font-size:24px">{h}</h1><div class="sub">{sub}</div></div>
   {fields}<span class="btn pri">{btn}</span><div class="sub" style="text-align:center;font-size:11px">{foot}</div>
  </form>
 </main>
</div></body></html>"""

PAGES['14-daftar-akun'] = ('Daftar Akun', auth_page('Daftar Akun', 'AKUN BARU', 'Daftar Akun', 'Akun baru otomatis berperan sebagai Peminjam.',
    field('Nama Lengkap', 'Nama sesuai KTM', 'type=text required maxlength · &lt;label for="name"&gt;')
    + field('Email', 'nama@campus.test', 'type=email required · &lt;label for="email"&gt;')
    + field('Kata Sandi', '••••••••', 'type=password required minlength')
    + field('Ulangi Kata Sandi', '••••••••', 'type=password required · harus sama'),
    'Daftar', 'Sudah punya akun? <u>Masuk</u>'))

timeline = ''.join(f'<div style="display:flex;gap:10px;align-items:flex-start;padding:6px 0"><i class="ico" style="border-radius:50%;{"background:#555" if d else ""}"></i><div><b>{t}</b><div class="sub" style="font-size:11px">{w}</div></div></div>'
                   for t, w, d in [('Diajukan', 'Jum, 6 Nov · 14.10', 1), ('Disetujui', 'Jum, 6 Nov · 16.02 oleh Kepala Lab', 1), ('Check-in', 'dibuka 09.45 – 10.15 WIB', 0), ('Check-out', '–', 0)])
PAGES['15-mhs-detail-peminjaman'] = ('Detail Peminjaman', shell('Detail Peminjaman', 'b', 'mine',
    head('Peminjaman Saya / Detail', 'PENGAJUAN & AKTIVITAS', 'Detail peminjaman.', 'Laboratorium Komputer 1 · Sen, 9 Nov 2026 · 10.00–12.00 WIB', '<span class="btn">Kembali ke daftar</span>')
    + f"""<div class="grid g21">
<div style="display:flex;flex-direction:column;gap:14px">
 <section class="card">{tag('section')}<div class="row" style="justify-content:space-between"><h2>Status peminjaman</h2><span class="badge">Disetujui</span></div>{timeline}
  <div class="note" style="margin-top:6px">Bila ditolak, kotak <b>Alasan penolakan</b> tampil di sini.</div></section>
 <section class="card" style="display:flex;flex-direction:column;gap:10px">{tag('section')}<h2>Check-in / Check-out</h2>
  <div class="sub">Pindai QR di pintu ruangan saat tiba (paling lambat 15 menit setelah mulai) dan saat selesai.</div>
  <div class="row"><span class="btn pri">Pindai QR</span><span class="btn ghost">Pakai kode cadangan</span></div>
  <span class="attr">kode cadangan: input pattern="[A-Za-z0-9]+" required</span></section>
</div>
<aside class="card sem" style="align-self:start;display:flex;flex-direction:column;gap:10px">{tag('aside')}<h2>Rincian</h2>
 <dl class="dl" style="grid-template-columns:1fr"><div><dt>Ruang</dt><dd>Laboratorium Komputer 1 · LAB-KOM-01</dd></div><div><dt>Waktu</dt><dd>Sen, 9 Nov 2026 · 10.00–12.00</dd></div>
 <div><dt>Pemohon</dt><dd>Andi Pratama (Mahasiswa)</dd></div><div><dt>Peserta</dt><dd>8 orang</dd></div><div><dt>Keperluan</dt><dd>Kerja kelompok tugas besar PAD</dd></div>
 <div><dt>Check-in</dt><dd>–</dd></div><div><dt>Check-out</dt><dd>–</dd></div></dl>
 <h2>Tindakan</h2><div class="row"><span class="btn sm">Ubah</span><span class="btn sm ghost">Batalkan</span></div>
</aside></div>"""))

PAGES['16-mhs-hasil-pindai-qr'] = ('Hasil Pindai QR', shell('Hasil Pindai QR', 'b', 'scan',
    head('Pindai QR / Hasil', 'PENGGUNAAN AKTUAL', 'Ruangan Laboratorium Komputer 1', 'LAB-KOM-01 · Gedung FRI', '<span class="btn">Pindai QR lain</span>')
    + f"""<div class="grid g2">
<article class="card" style="display:flex;flex-direction:column;gap:10px">{tag('article')}<div class="row" style="justify-content:space-between"><h2>Peminjaman Anda di ruang ini</h2><span class="badge">Disetujui</span></div>
 <dl class="dl"><div><dt>Keperluan</dt><dd>Kerja kelompok tugas besar PAD</dd></div><div><dt>Waktu</dt><dd>Sen, 9 Nov · 10.00–12.00</dd></div></dl>
 <div class="note">Check-in dibuka 09.45. Lewat 10.15 tanpa check-in → status <b>Tidak hadir</b>.</div>
 <form class="row sem" style="outline-offset:2px">{tag('form')}<span class="btn pri" style="flex:1">Check-in sekarang</span></form>
 <u style="font-size:12px">Lihat detail peminjaman</u></article>
<section class="card" style="display:flex;flex-direction:column;gap:10px">{tag('section')}<h2>Tidak ada peminjaman aktif?</h2>
 <div class="sub">Bila QR dipindai tanpa peminjaman yang cocok, halaman menampilkan pilihan berikut.</div>
 <span class="btn">Ajukan peminjaman ruang ini</span><span class="btn ghost">Peminjaman Saya</span></section>
</div>"""))

PAGES['17-pengelola-form-ruangan'] = ('Tambah / Ubah Ruang', shell('Tambah Ruang', 'm', 'manage',
    head('Ruangan & Foto / Tambah', 'DATA MASTER', 'Tambah ruang.', 'Form yang sama dipakai untuk mengubah ruang (Edit).')
    + f"""<form class="card sem" style="display:flex;flex-direction:column;gap:14px">{tag('form')}
 <div class="grid g2">{field('Unit Akademik', 'FRI — Fakultas Rekayasa Industri ▾', 'select required · &lt;label for="academic_unit_id"&gt;')}{field('Tipe Ruang', 'Laboratorium ▾', 'select required')}</div>
 <div class="grid g2">{field('Kode Ruang', 'LAB-KOM-02', 'type=text required maxlength=30')}{field('Nama Ruang', 'Laboratorium Komputer 2', 'type=text required maxlength=150')}</div>
 <div class="grid g4">{field('Gedung', 'Gedung FRI', 'required')}{field('Lantai', '2', 'type=text · opsional', req=False)}{field('Kapasitas', '40', 'type=number required min=1')}{field('Status Awal', 'Tersedia ▾', 'select required')}</div>
 <div class="field"><label>Deskripsi</label><div class="input area">Opsional</div></div>
 <fieldset class="sem" style="border:1.5px dashed #aaa;border-radius:8px;padding:12px;display:grid;grid-template-columns:140px 1fr;gap:12px;align-items:center">{tag('fieldset')}
  <div class="img" style="height:90px"><span>Pratinjau</span></div><div class="field"><label>Foto ruang</label><div class="input">Pilih file…</div><span class="attr">type=file accept=JPG/PNG/WebP · maks 2 MB</span>
  <label class="row" style="font-size:11px;margin-top:4px"><i class="ico"></i>Hapus foto saat ini (hanya saat Edit)</label></div></fieldset>
 <div class="row" style="justify-content:flex-end"><span class="btn">Batal</span><span class="btn pri">Simpan Ruang</span></div>
</form>"""))

fac_act = '<div class="row"><span class="btn sm">Ubah</span><span class="btn sm pri">Hapus</span></div>'
PAGES['18-pengelola-fasilitas'] = ('Fasilitas Ruang', shell('Fasilitas', 'm', 'fac',
    head('Ruangan & Foto / Fasilitas', 'DATA MASTER', 'Fasilitas Laboratorium Manufaktur.', 'LAB-MAN-01 · Gedung FRI. Catat jumlah dan kondisi setiap fasilitas ruang.')
    + '<div class="grid g21">'
    + table(['Nama', 'Jumlah', 'Kondisi', 'Tindakan'], [
        ['Mesin CNC mini', '2', '<span class="badge">Baik</span>', fac_act],
        ['Conveyor simulasi lini produksi', '1', '<span class="badge d">Rusak</span>', fac_act],
        ['Meja kerja', '10', '<span class="badge">Baik</span>', fac_act]], 'Daftar fasilitas')
    + f"""<aside class="card sem" style="align-self:start;display:flex;flex-direction:column;gap:12px">{tag('aside')}<h2>Tambah fasilitas</h2>
 <form class="sem" style="display:flex;flex-direction:column;gap:12px;outline-offset:6px">{tag('form')}{field('Nama', 'Proyektor', 'required maxlength')}{field('Jumlah', '1', 'type=number required min=1')}{field('Kondisi', 'Baik ▾', 'select required')}<span class="btn pri">Tambah</span></form>
</aside></div>"""))

ua = '<div class="row"><span class="btn sm">Ubah</span><span class="btn sm pri">Hapus</span></div>'
PAGES['19-admin-unit-akademik'] = ('Unit Akademik', shell('Unit Akademik', 'a', 'units',
    head('Administrasi / Unit Akademik', 'ADMINISTRASI', 'Unit akademik.', 'Fakultas atau jurusan pemilik ruang. Unit yang masih dipakai ruang tidak dapat dihapus.')
    + '<div class="grid g21">'
    + table(['Kode', 'Nama', 'Ruang', 'Tindakan'], [
        ['FRI', 'Fakultas Rekayasa Industri', '19', '<div class="row"><span class="btn sm">Ubah</span><small>Tidak dapat dihapus</small></div>'],
        ['FIF', 'Fakultas Informatika', '0', ua], ['FTE', 'Fakultas Teknik Elektro', '0', ua]], 'Daftar unit akademik')
    + f"""<aside class="card sem" style="align-self:start;display:flex;flex-direction:column;gap:12px">{tag('aside')}<h2>Tambah unit</h2>
 <form class="sem" style="display:flex;flex-direction:column;gap:12px;outline-offset:6px">{tag('form')}{field('Kode', 'FEB', 'required maxlength=30')}{field('Nama', 'Fakultas Ekonomi dan Bisnis', 'required maxlength=150')}<div class="field"><label>Deskripsi</label><div class="input area">Opsional</div></div><span class="btn pri">Tambah</span></form>
</aside></div>"""))

PAGES['20-admin-pengguna'] = ('Kelola Pengguna', shell('Pengguna', 'a', 'users',
    head('Administrasi / Pengguna', 'ADMINISTRASI', 'Pengguna.', 'Kelola akun dan role. Akun nonaktif tidak bisa login; riwayat peminjamannya tetap tersimpan.', '<span class="btn pri">Tambah Pengguna</span>')
    + table(['Nama', 'Email', 'Role', 'Status', 'Tindakan'], [
        ['Andi Pratama (Mahasiswa)', 'mahasiswa@campus.test', 'Peminjam', '<span class="badge">Aktif</span>', '<span class="btn sm">Ubah</span>'],
        ['Dr. Sinta Rahmawati (Dosen)', 'dosen@campus.test', 'Peminjam', '<span class="badge">Aktif</span>', '<span class="btn sm">Ubah</span>'],
        ['Kepala Laboratorium FRI', 'pengelola@campus.test', 'Kepala Lab', '<span class="badge">Aktif</span>', '<span class="btn sm">Ubah</span>'],
        ['Wakil Dekan II FRI', 'pimpinan@campus.test', 'Pimpinan', '<span class="badge">Aktif</span>', '<span class="btn sm">Ubah</span>'],
        ['Alumni Uji Coba', 'lama@campus.test', 'Peminjam', '<span class="badge d">Nonaktif</span>', '<span class="btn sm">Ubah</span>']], 'Daftar pengguna')
    + '<div class="note"><b>Tambah / Ubah Pengguna</b> membuka form: Nama* (<span class="attr">&lt;label for="name"&gt; required</span>), Email* (<span class="attr">type=email required</span>), Role* (<span class="attr">select required</span>: Peminjam / Kepala Lab / Admin / Pimpinan), kata sandi, dan status aktif.</div>'))

PAGES['21-halaman-error'] = ('Halaman Error', f"""<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404</title><style>{CSS}</style></head><body>
<main class="sem" style="min-height:100vh;display:grid;place-items:center;padding:24px"><span class="tag in">&lt;main&gt;</span>
 <section class="card" style="max-width:460px;width:100%;text-align:center;display:flex;flex-direction:column;gap:12px;padding:30px">{tag('section')}
  <div class="logo" style="justify-content:center"><div class="mark">C</div><div style="text-align:left"><b>Campus Space</b><small>LABORATORIUM FRI</small></div></div>
  <div class="img" style="height:120px"><span>Ilustrasi</span></div>
  <div class="eyebrow">GALAT 404</div><h1 style="font-size:26px">Halaman tidak ditemukan</h1>
  <div class="sub">Halaman yang Anda cari tidak ada atau sudah dihapus.</div>
  <div class="row" style="justify-content:center"><span class="btn pri">Kembali ke Dashboard</span><span class="btn">Halaman sebelumnya</span></div>
  <div class="note" style="text-align:left">Satu template untuk 401, 403, 404, 405, 419, 429, 500, 503. Hanya kode, judul, dan pesan yang berubah. Tamu melihat tombol <b>Masuk</b>, bukan Dashboard.</div>
 </section>
</main></body></html>""")

for slug, (title, doc) in PAGES.items():
    with open(os.path.join(OUT, slug + '.html'), 'w') as f:
        f.write(doc)
print('\n'.join(PAGES))
