<?php
// index.php - Landing Page Sistem Informasi Perpajakan
require_once __DIR__ . '/config/database.php';

$page_title = 'Beranda - Sistem Manajemen Database Perpajakan';
$active_nav = 'landing';

// Ambil Ringkasan Statistik Riil dari Database (R-17 / R-38 Data Nyata)
$totalWP = $pdo->query("SELECT COUNT(*) FROM wajib_pajak")->fetchColumn();
$totalSPT = $pdo->query("SELECT COUNT(*) FROM spt")->fetchColumn();
$totalPenerimaan = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_pajak WHERE status_pembayaran = 'Paid'")->fetchColumn();
$totalBilling = $pdo->query("SELECT COUNT(*) FROM pembayaran_pajak")->fetchColumn();
$totalKPP = $pdo->query("SELECT COUNT(*) FROM kpp")->fetchColumn();

// Cek Billing jika ada pencarian
$cekBillingResult = null;
$searchedBilling = '';
if (!empty($_GET['cek_billing'])) {
    $searchedBilling = trim($_GET['cek_billing']);
    $stmtCek = $pdo->prepare("
        SELECT p.*, w.nama_wp, w.npwp, j.nama_jenis_pajak, j.kode_akun_pajak, j.tarif_standar
        FROM pembayaran_pajak p
        JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
        JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
        WHERE p.kode_billing = ?
    ");
    $stmtCek->execute([$searchedBilling]);
    $cekBillingResult = $stmtCek->fetch();
}

// Ambil Jenis Pajak untuk Kalkulator
$listJenisPajak = $pdo->query("SELECT id, kode_akun_pajak, nama_jenis_pajak, tarif_standar, kategori_pajak FROM jenis_pajak ORDER BY id ASC")->fetchAll();

// Ambil Sampel Data untuk Tampilan Sesuai Docx (Bab 2)
$sampleWP = $pdo->query("SELECT id, npwp, nama_wp, alamat, jenis_wp FROM wajib_pajak LIMIT 3")->fetchAll();
$sampleJenis = $pdo->query("SELECT id, kode_akun_pajak, nama_jenis_pajak, tarif_standar FROM jenis_pajak LIMIT 3")->fetchAll();
$sampleTransaksi = $pdo->query("
    SELECT p.id, w.nama_wp, j.nama_jenis_pajak, j.kode_akun_pajak, p.dpp, p.jumlah_bayar, p.status_pembayaran, p.kode_billing
    FROM pembayaran_pajak p
    JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
    ORDER BY p.id ASC LIMIT 3
")->fetchAll();

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/alerts.php';
?>

<!-- Hero Section (Minimalist Monochrome Editorial, Poppins) -->
<section class="bg-white text-black py-12 lg:py-16 border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <!-- Left Column: Copy & Clear CTAs -->
            <div class="lg:col-span-7 space-y-5 text-left">
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                    Sistem Basis Data Perpajakan Relasional
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-black leading-tight">
                    Administrasi &amp; Tata Kelola Perpajakan Terpadu
                </h1>
                
                <p class="text-zinc-600 text-sm sm:text-base leading-relaxed max-w-2xl font-normal">
                    Pengelolaan basis data perpajakan berbasis MySQL: pencatatan Wajib Pajak (NPWP/NIK), otomasi kalkulasi tarif (PPN/PPh), pelaporan SPT, serta penatausahaan kode billing penerimaan negara secara real-time.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="dashboard.php" class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 rounded bg-black hover:bg-zinc-800 text-white font-semibold text-xs tracking-wider transition">
                        Buka Dashboard CRUD
                    </a>
                    <a href="#kalkulator" class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded bg-transparent hover:bg-zinc-100 text-black font-semibold text-xs border border-black transition">
                        Hitung Pajak Cepat
                    </a>
                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-800 font-medium text-xs border border-zinc-300 transition">
                        phpMyAdmin
                    </a>
                </div>

                <!-- Info Badges -->
                <div class="pt-3 flex flex-wrap gap-4 text-xs text-zinc-500 font-medium">
                    <span class="flex items-center gap-1.5">&bull; 6 Tabel Relasional Normal</span>
                    <span class="flex items-center gap-1.5">&bull; Kode Billing 15 Digit</span>
                    <span class="flex items-center gap-1.5">&bull; NTPN Kas Negara</span>
                    <span class="flex items-center gap-1.5">&bull; Bukti Penerimaan Negara (BPN)</span>
                </div>
            </div>

            <!-- Right Column: Quick Billing Search Card (Crisp Black & White) -->
            <div class="lg:col-span-5">
                <div class="bg-white border border-black rounded p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-200 pb-3">
                        <div>
                            <h3 class="font-bold text-black text-base">Cek Status Billing</h3>
                            <p class="text-xs text-zinc-500">Verifikasi status transaksi e-Billing</p>
                        </div>
                        <span class="text-xs bg-zinc-100 text-black px-2 py-0.5 rounded font-mono border border-zinc-300">15 Digit</span>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="#cek-billing" id="cek-billing" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 mb-1">Masukkan Kode Billing 15 Digit:</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input type="text" name="cek_billing" maxlength="15" value="<?= htmlspecialchars($searchedBilling) ?>" placeholder="Contoh: 123456789012345" required
                                       class="flex-grow min-h-[44px] bg-white border border-zinc-300 text-black placeholder-zinc-400 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black font-mono">
                                <button type="submit" class="min-h-[44px] px-5 bg-black hover:bg-zinc-800 text-white font-semibold rounded text-xs transition">
                                    Cek Billing
                                </button>
                            </div>
                        </div>
                        <div class="text-xs text-zinc-500 flex flex-wrap items-center justify-between gap-1">
                            <span>Sampel kode billing:</span>
                            <div class="space-x-2 font-mono">
                                <a href="?cek_billing=123456789012345#cek-billing" class="text-black underline font-semibold">123456789012345</a> &bull;
                                <a href="?cek_billing=345678901234567#cek-billing" class="text-black underline font-semibold">345678901234567</a>
                            </div>
                        </div>
                    </form>

                    <!-- Search Result Box -->
                    <?php if ($searchedBilling): ?>
                        <div class="pt-1">
                            <?php if ($cekBillingResult): ?>
                                <div class="bg-zinc-50 rounded p-4 border border-zinc-300 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-zinc-600 font-medium">Status Pembayaran:</span>
                                        <?php if ($cekBillingResult['status_pembayaran'] === 'Paid'): ?>
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-black text-white">
                                                LUNAS (PAID)
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded text-xs font-bold border border-black text-black">
                                                PENDING (UNPAID)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-zinc-800">
                                        <p class="font-bold text-black text-sm"><?= htmlspecialchars($cekBillingResult['nama_wp']) ?></p>
                                        <p class="font-mono text-zinc-600 text-xs">NPWP: <?= htmlspecialchars($cekBillingResult['npwp']) ?></p>
                                    </div>
                                    <div class="border-t border-zinc-200 pt-2 text-xs flex justify-between items-center">
                                        <span class="text-zinc-600"><?= htmlspecialchars($cekBillingResult['nama_jenis_pajak']) ?></span>
                                        <span class="font-bold text-black font-mono text-sm"><?= formatRupiah($cekBillingResult['jumlah_bayar']) ?></span>
                                    </div>
                                    <?php if ($cekBillingResult['ntpn']): ?>
                                        <div class="text-xs text-zinc-600 pt-1 flex justify-between">
                                            <span>NTPN:</span>
                                            <span class="font-mono text-black font-bold"><?= htmlspecialchars($cekBillingResult['ntpn']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="pt-2 flex justify-end">
                                        <a href="cetak_billing.php?id=<?= $cekBillingResult['id'] ?>" target="_blank" class="text-xs text-black hover:underline font-semibold">
                                            Cetak Bukti Penerimaan Negara (BPN) &rarr;
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-black text-white rounded p-3 text-xs">
                                    <span>Kode billing <strong><?= htmlspecialchars($searchedBilling) ?></strong> tidak ditemukan dalam basis data.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Live Mini Stats -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="bg-zinc-50 rounded p-3 border border-zinc-200 text-center">
                            <span class="text-[11px] uppercase tracking-wider text-zinc-500 block mb-0.5">Penerimaan Realisasi</span>
                            <span class="text-sm font-bold text-black font-mono"><?= formatRupiah($totalPenerimaan) ?></span>
                        </div>
                        <div class="bg-zinc-50 rounded p-3 border border-zinc-200 text-center">
                            <span class="text-[11px] uppercase tracking-wider text-zinc-500 block mb-0.5">Total Billing</span>
                            <span class="text-sm font-bold text-black font-mono"><?= $totalBilling ?> Transaksi</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Stats Metrics Bar (Pure Monochrome) -->
<section class="bg-zinc-50 border-b border-zinc-200 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="bg-white border border-zinc-200 rounded p-5">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">Wajib Pajak</span>
                <span class="text-2xl font-bold text-black"><?= $totalWP ?></span>
                <span class="text-xs text-zinc-400 block mt-1">Terdaftar di sistem</span>
            </div>
            <div class="bg-white border border-zinc-200 rounded p-5">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">Penerimaan Negara</span>
                <span class="text-xl font-bold text-black font-mono"><?= formatRupiah($totalPenerimaan) ?></span>
                <span class="text-xs text-zinc-400 block mt-1">Status lunas terverifikasi</span>
            </div>
            <div class="bg-white border border-zinc-200 rounded p-5">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">SPT Terlapor</span>
                <span class="text-2xl font-bold text-black"><?= $totalSPT ?></span>
                <span class="text-xs text-zinc-400 block mt-1">SPT Masa &amp; Tahunan</span>
            </div>
            <div class="bg-white border border-zinc-200 rounded p-5">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider block mb-1">Kantor KPP</span>
                <span class="text-2xl font-bold text-black"><?= $totalKPP ?></span>
                <span class="text-xs text-zinc-400 block mt-1">Wilayah operasional</span>
            </div>
        </div>
    </div>
</section>

<!-- Section 1: Pengertian & 3 Fungsi Utama (Monochrome Cards) -->
<section class="py-14 bg-white border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-left max-w-3xl mb-10">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Arsitektur Fungsional</span>
            <h2 class="text-2xl sm:text-3xl font-bold text-black mt-1">
                Fungsi Utama Pengelolaan Perpajakan
            </h2>
            <p class="text-zinc-600 text-sm mt-1">
                Basis data relasional untuk mencatat, mengelola, dan memproses data pendaftaran, transaksi, serta pembayaran pajak negara secara otomatis dan konsisten.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1 -->
            <div class="bg-white rounded border border-zinc-200 p-6 flex flex-col justify-between hover:border-black transition">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-mono font-bold text-black">01</span>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 border border-zinc-200">Master Data</span>
                    </div>
                    <h3 class="text-lg font-bold text-black mb-2">Manajemen Wajib Pajak</h3>
                    <p class="text-zinc-600 text-sm leading-relaxed mb-4">
                        Menyimpan identitas tunggal Wajib Pajak (NPWP 16 digit &amp; NIK) untuk Orang Pribadi dan Badan hukum, lengkap dengan domisili KPP dan Account Representative (AR).
                    </p>
                </div>
                <div class="pt-3 border-t border-zinc-100">
                    <a href="wajib_pajak.php" class="text-xs font-bold text-black hover:underline inline-flex items-center gap-1">
                        Kelola Data Wajib Pajak &rarr;
                    </a>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-white rounded border border-zinc-200 p-6 flex flex-col justify-between hover:border-black transition">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-mono font-bold text-black">02</span>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 border border-zinc-200">Otomasi</span>
                    </div>
                    <h3 class="text-lg font-bold text-black mb-2">Perhitungan Tarif Otomatis</h3>
                    <p class="text-zinc-600 text-sm leading-relaxed mb-4">
                        Menghitung kewajiban pajak (PPN 11%, PPh 21, PPh 23, PBB) secara presisi dari Dasar Pengenaan Pajak (DPP) sesuai tarif standar resmi dalam basis data.
                    </p>
                </div>
                <div class="pt-3 border-t border-zinc-100">
                    <a href="#kalkulator" class="text-xs font-bold text-black hover:underline inline-flex items-center gap-1">
                        Buka Kalkulator Pajak &rarr;
                    </a>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-white rounded border border-zinc-200 p-6 flex flex-col justify-between hover:border-black transition">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-mono font-bold text-black">03</span>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 border border-zinc-200">Kepatuhan</span>
                    </div>
                    <h3 class="text-lg font-bold text-black mb-2">Pelaporan &amp; Kepatuhan SPT</h3>
                    <p class="text-zinc-600 text-sm leading-relaxed mb-4">
                        Menyediakan riwayat transaksi setoran negara, pelaporan SPT Masa/Tahunan dengan Bukti Penerimaan Surat (BPS), dan pencatatan status pelunasan.
                    </p>
                </div>
                <div class="pt-3 border-t border-zinc-100">
                    <a href="spt.php" class="text-xs font-bold text-black hover:underline inline-flex items-center gap-1">
                        Pelaporan SPT Pajak &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Sampel Data Sesuai Relasi Dokumen -->
<section class="py-14 bg-zinc-50 border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Implementasi Skema Basis Data</span>
                <h2 class="text-2xl font-bold text-black mt-1">Sampel Data Master &amp; Transaksional</h2>
                <p class="text-sm text-zinc-600">Representasi tabel relasional yang tersimpan di MySQL database.</p>
            </div>
            <div>
                <a href="dashboard.php" class="min-h-[40px] px-4 py-2 bg-black hover:bg-zinc-800 text-white rounded text-xs font-semibold inline-flex items-center gap-2">
                    Buka Dashboard CRUD
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Sub-table 1: Wajib Pajak -->
            <div class="bg-white rounded border border-zinc-200 p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-black text-sm">
                        2.1 Data Wajib Pajak (Master)
                    </h3>
                    <a href="wajib_pajak.php" class="text-xs font-semibold text-zinc-600 hover:text-black hover:underline">Kelola Data</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white border border-zinc-200">
                        <thead>
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">NPWP</th>
                                <th class="p-3">Nama Wajib Pajak</th>
                                <th class="p-3">Alamat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sampleWP as $wp): ?>
                                <tr>
                                    <td class="p-3 font-mono font-bold text-zinc-600"><?= $wp['id'] ?></td>
                                    <td class="p-3 font-mono text-black font-medium"><?= htmlspecialchars($wp['npwp']) ?></td>
                                    <td class="p-3 font-bold text-black"><?= htmlspecialchars($wp['nama_wp']) ?></td>
                                    <td class="p-3 text-zinc-600 truncate max-w-xs"><?= htmlspecialchars($wp['alamat']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sub-table 2: Jenis Pajak -->
            <div class="bg-white rounded border border-zinc-200 p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-black text-sm">
                        2.2 Jenis Pajak (Master)
                    </h3>
                    <a href="jenis_pajak.php" class="text-xs font-semibold text-zinc-600 hover:text-black hover:underline">Kelola Tarif</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white border border-zinc-200">
                        <thead>
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">Kode KAP</th>
                                <th class="p-3">Nama Jenis Pajak</th>
                                <th class="p-3 text-right">Tarif (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sampleJenis as $jp): ?>
                                <tr>
                                    <td class="p-3 font-mono font-bold text-zinc-600"><?= $jp['id'] ?></td>
                                    <td class="p-3 font-mono text-black font-semibold"><?= htmlspecialchars($jp['kode_akun_pajak']) ?></td>
                                    <td class="p-3 font-bold text-black"><?= htmlspecialchars($jp['nama_jenis_pajak']) ?></td>
                                    <td class="p-3 text-right font-mono font-bold text-black"><?= number_format($jp['tarif_standar'], 2) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sub-table 3: Transaksi Pajak -->
            <div class="lg:col-span-2 bg-white rounded border border-zinc-200 p-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-black text-sm">
                        2.3 Transaksi Sirkulasi Billing Pajak
                    </h3>
                    <a href="pembayaran.php" class="text-xs font-semibold text-zinc-600 hover:text-black hover:underline">Buka Seluruh Transaksi</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white border border-zinc-200">
                        <thead>
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">Wajib Pajak</th>
                                <th class="p-3">Jenis Pajak</th>
                                <th class="p-3 text-right">DPP (Rp)</th>
                                <th class="p-3 text-right">Jumlah Pajak</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sampleTransaksi as $tr): ?>
                                <tr>
                                    <td class="p-3 font-mono font-bold text-zinc-600"><?= $tr['id'] ?></td>
                                    <td class="p-3 font-bold text-black"><?= htmlspecialchars($tr['nama_wp']) ?></td>
                                    <td class="p-3 text-zinc-700"><?= htmlspecialchars($tr['nama_jenis_pajak']) ?></td>
                                    <td class="p-3 text-right font-mono text-zinc-800"><?= formatRupiah($tr['dpp']) ?></td>
                                    <td class="p-3 text-right font-mono font-bold text-black"><?= formatRupiah($tr['jumlah_bayar']) ?></td>
                                    <td class="p-3 text-center">
                                        <?php if ($tr['status_pembayaran'] === 'Paid'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-black text-white">
                                                LUNAS
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border border-black text-black">
                                                PENDING
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center space-x-1">
                                        <a href="cetak_billing.php?id=<?= $tr['id'] ?>" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded bg-zinc-100 hover:bg-zinc-200 text-black text-xs font-semibold transition" title="Cetak BPN">
                                            Cetak
                                        </a>
                                        <a href="pembayaran.php" class="inline-flex items-center px-2.5 py-1 rounded bg-black text-white text-xs font-semibold transition">
                                            Rincian
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Tax Calculator Section -->
<section id="kalkulator" class="py-14 bg-white border-b border-zinc-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-left max-w-2xl mb-8">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Kalkulasi Otomatis</span>
            <h2 class="text-2xl sm:text-3xl font-bold text-black mt-1">
                Kalkulator Pajak Interaktif
            </h2>
            <p class="text-zinc-600 text-sm mt-1">
                Hitung estimasi kewajiban pajak terutang berdasarkan Dasar Pengenaan Pajak (DPP) dan tarif standar yang berlaku.
            </p>
        </div>

        <div class="bg-white rounded p-6 sm:p-8 border border-zinc-300">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <!-- Inputs -->
                <div class="space-y-5">
                    <div>
                        <label for="calcJenisPajak" class="block text-xs font-bold text-black uppercase tracking-wider mb-2">
                            1. Pilih Objek / Jenis Pajak
                        </label>
                        <select id="calcJenisPajak" class="w-full min-h-[44px] bg-white border border-zinc-300 rounded px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-black" onchange="hitungPajakLive()">
                            <?php foreach ($listJenisPajak as $jp): ?>
                                <option value="<?= $jp['tarif_standar'] ?>" data-nama="<?= htmlspecialchars($jp['nama_jenis_pajak']) ?>" data-kap="<?= htmlspecialchars($jp['kode_akun_pajak']) ?>">
                                    <?= htmlspecialchars($jp['nama_jenis_pajak']) ?> (<?= number_format($jp['tarif_standar'], 2) ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="calcDPP" class="block text-xs font-bold text-black uppercase tracking-wider mb-2">
                            2. Masukkan Dasar Pengenaan Pajak (DPP)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-sm font-bold text-zinc-500">Rp</span>
                            <input type="number" id="calcDPP" value="100000000" min="0" step="10000" class="w-full min-h-[44px] bg-white border border-zinc-300 rounded pl-10 pr-3 py-2 text-sm font-mono font-bold text-black focus:outline-none focus:ring-2 focus:ring-black" oninput="hitungPajakLive()">
                        </div>
                    </div>

                    <!-- Preset DPP Buttons -->
                    <div>
                        <span class="text-zinc-600 font-semibold text-xs block mb-2">Pilihan Cepat Nilai DPP:</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="setDPP(15000000)" class="min-h-[36px] px-3 py-1.5 rounded bg-zinc-100 hover:bg-zinc-200 text-black font-mono font-bold text-xs border border-zinc-200 transition">15 Juta</button>
                            <button type="button" onclick="setDPP(50000000)" class="min-h-[36px] px-3 py-1.5 rounded bg-zinc-100 hover:bg-zinc-200 text-black font-mono font-bold text-xs border border-zinc-200 transition">50 Juta</button>
                            <button type="button" onclick="setDPP(100000000)" class="min-h-[36px] px-3 py-1.5 rounded bg-zinc-100 hover:bg-zinc-200 text-black font-mono font-bold text-xs border border-zinc-200 transition">100 Juta</button>
                            <button type="button" onclick="setDPP(500000000)" class="min-h-[36px] px-3 py-1.5 rounded bg-zinc-100 hover:bg-zinc-200 text-black font-mono font-bold text-xs border border-zinc-200 transition">500 Juta</button>
                        </div>
                    </div>
                </div>

                <!-- Live Results Card (Monochrome Solid Black) -->
                <div class="bg-black text-white rounded p-6 sm:p-7 space-y-4">
                    <div class="flex justify-between items-center border-b border-zinc-800 pb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-400">Hasil Perhitungan Pajak</span>
                        <span id="resTarif" class="text-xs font-bold bg-zinc-800 text-white px-2 py-0.5 rounded border border-zinc-700">Tarif 11.00%</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-zinc-400 block mb-0.5">Dasar Pengenaan Pajak (DPP):</span>
                            <span id="resDPP" class="text-base sm:text-lg font-mono font-bold text-zinc-200">Rp 100.000.000</span>
                        </div>
                        <div class="pt-2 border-t border-zinc-800">
                            <span class="text-xs text-zinc-400 block mb-0.5">Nilai Pajak Terutang:</span>
                            <span id="resPajak" class="text-2xl sm:text-3xl font-mono font-bold text-white">Rp 11.000.000</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-zinc-800 flex flex-col sm:flex-row gap-2">
                        <a href="pembayaran.php?action=create" class="flex-1 min-h-[40px] inline-flex items-center justify-center py-2 px-4 rounded bg-white hover:bg-zinc-200 text-black font-bold text-xs transition">
                            Terbitkan Kode Billing
                        </a>
                        <button type="button" onclick="salinHasil()" class="min-h-[40px] py-2 px-4 rounded bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-semibold border border-zinc-700 transition">
                            Salin Nilai
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section: Skema Relasi Database 6 Tabel -->
<section class="py-14 bg-zinc-50 border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-left max-w-3xl mb-8">
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Integritas Relasional</span>
            <h2 class="text-2xl sm:text-3xl font-bold text-black mt-1">
                Struktur 6 Tabel Terintegrasi (MySQL DDL)
            </h2>
            <p class="text-zinc-600 text-sm mt-1">
                Seluruh tabel terhubung menggunakan Foreign Key, Indexing Unik, dan Integritas Referensial ON DELETE / ON UPDATE.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Table 1: kpp -->
            <div class="bg-white border border-zinc-200 rounded p-5 hover:border-black transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-zinc-200">
                    <span class="font-mono font-bold text-xs text-black bg-zinc-100 px-2 py-0.5 rounded">4.1 kpp</span>
                    <span class="text-xs text-zinc-500 font-medium">Master KPP</span>
                </div>
                <ul class="text-xs space-y-1.5 text-zinc-700 font-mono">
                    <li><strong>PK:</strong> id (INT)</li>
                    <li><strong>UQ:</strong> kode_kpp (VARCHAR 3)</li>
                    <li>nama_kpp (VARCHAR 100)</li>
                    <li>alamat (TEXT) &bull; no_telepon</li>
                </ul>
            </div>

            <!-- Table 2: petugas_pajak -->
            <div class="bg-white border border-zinc-200 rounded p-5 hover:border-black transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-zinc-200">
                    <span class="font-mono font-bold text-xs text-black bg-zinc-100 px-2 py-0.5 rounded">4.2 petugas_pajak</span>
                    <span class="text-xs text-zinc-500 font-medium">Account Rep (AR)</span>
                </div>
                <ul class="text-xs space-y-1.5 text-zinc-700 font-mono">
                    <li><strong>PK:</strong> id (INT)</li>
                    <li><strong>UQ:</strong> nip (VARCHAR 18)</li>
                    <li><strong>FK:</strong> kpp_id &rarr; kpp.id</li>
                    <li>nama_petugas &bull; jabatan (ENUM)</li>
                </ul>
            </div>

            <!-- Table 3: wajib_pajak -->
            <div class="bg-white border border-zinc-200 rounded p-5 hover:border-black transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-zinc-200">
                    <span class="font-mono font-bold text-xs text-black bg-zinc-100 px-2 py-0.5 rounded">4.3 wajib_pajak</span>
                    <span class="text-xs text-zinc-500 font-medium">Master WP</span>
                </div>
                <ul class="text-xs space-y-1.5 text-zinc-700 font-mono">
                    <li><strong>PK:</strong> id (INT)</li>
                    <li><strong>UQ:</strong> npwp (VARCHAR 25)</li>
                    <li><strong>FK:</strong> kpp_id, petugas_ar_id</li>
                    <li>nama_wp &bull; jenis_wp &bull; status_efin</li>
                </ul>
            </div>

            <!-- Table 4: jenis_pajak -->
            <div class="bg-white border border-zinc-200 rounded p-5 hover:border-black transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-zinc-200">
                    <span class="font-mono font-bold text-xs text-black bg-zinc-100 px-2 py-0.5 rounded">4.4 jenis_pajak</span>
                    <span class="text-xs text-zinc-500 font-medium">Master Tarif</span>
                </div>
                <ul class="text-xs space-y-1.5 text-zinc-700 font-mono">
                    <li><strong>PK:</strong> id (INT)</li>
                    <li><strong>UQ:</strong> kode_akun_pajak (KAP 6)</li>
                    <li>nama_jenis_pajak (VARCHAR)</li>
                    <li>kategori_pajak &bull; tarif_standar (%)</li>
                </ul>
            </div>

            <!-- Table 5: spt -->
            <div class="bg-white border border-zinc-200 rounded p-5 hover:border-black transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-zinc-200">
                    <span class="font-mono font-bold text-xs text-black bg-zinc-100 px-2 py-0.5 rounded">4.5 spt</span>
                    <span class="text-xs text-zinc-500 font-medium">Pelaporan SPT</span>
                </div>
                <ul class="text-xs space-y-1.5 text-zinc-700 font-mono">
                    <li><strong>PK:</strong> id (INT)</li>
                    <li><strong>UQ:</strong> nomor_spt / BPS</li>
                    <li><strong>FK:</strong> wajib_pajak_id, jenis_pajak_id</li>
                    <li>tahun_pajak &bull; status_spt &bull; nominal</li>
                </ul>
            </div>

            <!-- Table 6: pembayaran_pajak -->
            <div class="bg-white border border-zinc-200 rounded p-5 hover:border-black transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-zinc-200">
                    <span class="font-mono font-bold text-xs text-black bg-zinc-100 px-2 py-0.5 rounded">4.6 pembayaran_pajak</span>
                    <span class="text-xs text-zinc-500 font-medium">Billing &amp; Kas</span>
                </div>
                <ul class="text-xs space-y-1.5 text-zinc-700 font-mono">
                    <li><strong>PK:</strong> id (INT)</li>
                    <li><strong>UQ:</strong> kode_billing (15), ntpn (16)</li>
                    <li><strong>FK:</strong> spt_id, wajib_pajak_id, jenis_id</li>
                    <li>dpp &bull; jumlah_bayar &bull; status</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner (Minimalist Monochrome) -->
<section class="py-12 bg-white text-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
        <h2 class="text-2xl sm:text-3xl font-bold">Mulai Kelola Basis Data Perpajakan</h2>
        <p class="text-zinc-600 text-sm max-w-xl mx-auto">
            Daftarkan Wajib Pajak baru, terbitkan kode billing 15 digit, dan catat pelaporan SPT dengan validasi integritas basis data MySQL.
        </p>
        <div class="flex flex-wrap justify-center gap-3 pt-2">
            <a href="dashboard.php" class="min-h-[44px] inline-flex items-center justify-center px-6 py-2.5 rounded bg-black hover:bg-zinc-800 text-white font-semibold text-xs tracking-wider transition">
                Buka Dashboard Utama
            </a>
            <a href="wajib_pajak.php?action=create" class="min-h-[44px] inline-flex items-center justify-center px-6 py-2.5 rounded bg-transparent hover:bg-zinc-100 text-black font-semibold text-xs border border-black transition">
                Daftarkan Wajib Pajak Baru
            </a>
        </div>
    </div>
</section>

<!-- Client Side Tax Calculator Script -->
<script>
    function formatRupiahJs(number) {
        return 'Rp ' + Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function setDPP(amount) {
        document.getElementById('calcDPP').value = amount;
        hitungPajakLive();
    }

    function hitungPajakLive() {
        const select = document.getElementById('calcJenisPajak');
        const tarif = parseFloat(select.value) || 0;
        const dpp = parseFloat(document.getElementById('calcDPP').value) || 0;
        
        const pajak = (dpp * tarif) / 100;

        document.getElementById('resTarif').innerText = 'Tarif ' + tarif.toFixed(2) + '%';
        document.getElementById('resDPP').innerText = formatRupiahJs(dpp);
        document.getElementById('resPajak').innerText = formatRupiahJs(pajak);
    }

    function salinHasil() {
        const text = document.getElementById('resPajak').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Nilai Pajak berhasil disalin: ' + text);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        hitungPajakLive();
    });
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
