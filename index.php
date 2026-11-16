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

<!-- Hero Section with Crisp Tax Authority Theme (Free from AI Radial Orbs & Free from Eyebrow Badge Slop) -->
<section class="hero-pattern text-white py-14 lg:py-20 relative border-b border-tax-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            
            <!-- Left Column: Copy & Clear CTAs -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight text-white">
                    Sistem Administrasi & <br class="hidden sm:inline">
                    <span class="text-amber-300">Manajemen Basis Data Perpajakan</span>
                </h1>
                
                <p class="text-slate-200 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto lg:mx-0 font-normal">
                    Pengelolaan basis data perpajakan berbasis relasional MySQL: mencatat identitas Wajib Pajak (NPWP/NIK), otomasi perhitungan tarif (PPN/PPh), pelaporan SPT, serta penatausahaan billing penerimaan negara secara real-time.
                </p>

                <!-- CTA Buttons with min 44px tap targets (R-03 / antislop-layoutmobile) -->
                <div class="flex flex-wrap justify-center lg:justify-start gap-3.5 pt-2">
                    <a href="dashboard.php" class="inline-flex items-center justify-center min-h-[48px] px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-extrabold text-sm shadow-lg transition focus:ring-2 focus:ring-amber-400 gap-2">
                        <i class="fa-solid fa-table-columns text-base"></i> Buka Dashboard CRUD
                    </a>
                    <a href="#kalkulator" class="inline-flex items-center justify-center min-h-[48px] px-6 py-3 rounded-xl bg-tax-800 hover:bg-tax-700 text-slate-100 font-semibold text-sm border border-tax-700 transition focus:ring-2 focus:ring-amber-400 gap-2">
                        <i class="fa-solid fa-calculator text-amber-300"></i> Hitung Pajak Cepat
                    </a>
                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="inline-flex items-center justify-center min-h-[48px] px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white font-medium text-xs border border-slate-700 transition gap-2">
                        <i class="fa-solid fa-database text-amber-300"></i> phpMyAdmin
                    </a>
                </div>

                <!-- Fast Features Informational Tags (High Contrast) -->
                <div class="pt-2 flex flex-wrap justify-center lg:justify-start gap-4 text-xs text-slate-300">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-400 font-bold"></i> 6 Tabel Relasional Normal</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-400 font-bold"></i> Kode Billing 15 Digit</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-400 font-bold"></i> NTPN Kas Negara</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-emerald-400 font-bold"></i> Bukti Penerimaan Negara (BPN)</span>
                </div>
            </div>

            <!-- Right Column: Interactive Quick Billing Search (Solid High Contrast Card, No Glassmorphism Slop R-10) -->
            <div class="lg:col-span-5">
                <div class="bg-tax-900 border border-tax-700 rounded-2xl p-6 sm:p-7 shadow-xl space-y-5">
                    <div class="flex items-center justify-between border-b border-tax-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-tax-950 flex items-center justify-center font-bold">
                                <i class="fa-solid fa-qrcode text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-base">Cek Status Billing</h3>
                                <p class="text-xs text-slate-300">Verifikasi status transaksi e-Billing</p>
                            </div>
                        </div>
                        <span class="text-xs bg-tax-800 text-amber-300 px-2.5 py-1 rounded-md font-mono border border-tax-700">15 Digit</span>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="#cek-billing" id="cek-billing" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-200 mb-1.5">Masukkan Kode Billing 15 Digit:</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input type="text" name="cek_billing" maxlength="15" value="<?= htmlspecialchars($searchedBilling) ?>" placeholder="Contoh: 123456789012345" required
                                       class="flex-grow min-h-[44px] bg-tax-950 border border-tax-700 text-white placeholder-slate-400 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 font-mono tracking-widest">
                                <button type="submit" class="min-h-[44px] px-5 bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 focus:ring-2 focus:ring-amber-400">
                                    <i class="fa-solid fa-magnifying-glass"></i> Cek Billing
                                </button>
                            </div>
                        </div>
                        <div class="text-xs text-slate-300 flex flex-wrap items-center justify-between gap-1">
                            <span>Sampel kode billing:</span>
                            <div class="space-x-2 font-mono">
                                <a href="?cek_billing=123456789012345#cek-billing" class="text-amber-300 hover:underline">123456789012345</a> &bull;
                                <a href="?cek_billing=345678901234567#cek-billing" class="text-amber-300 hover:underline">345678901234567</a>
                            </div>
                        </div>
                    </form>

                    <!-- Search Result Box -->
                    <?php if ($searchedBilling): ?>
                        <div class="pt-1">
                            <?php if ($cekBillingResult): ?>
                                <div class="bg-tax-950 rounded-xl p-4 border <?= $cekBillingResult['status_pembayaran'] === 'Paid' ? 'border-emerald-500/50' : 'border-amber-500/50' ?> space-y-2.5">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-slate-300 font-medium">Status Pembayaran:</span>
                                        <?php if ($cekBillingResult['status_pembayaran'] === 'Paid'): ?>
                                            <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center gap-1.5">
                                                <i class="fa-solid fa-circle-check"></i> LUNAS (PAID)
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1.5">
                                                <i class="fa-solid fa-clock"></i> PENDING (UNPAID)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-200">
                                        <p class="font-bold text-white text-sm"><?= htmlspecialchars($cekBillingResult['nama_wp']) ?></p>
                                        <p class="font-mono text-slate-300 text-xs">NPWP: <?= htmlspecialchars($cekBillingResult['npwp']) ?></p>
                                    </div>
                                    <div class="border-t border-tax-800 pt-2 text-xs flex justify-between items-center">
                                        <span class="text-slate-300"><?= htmlspecialchars($cekBillingResult['nama_jenis_pajak']) ?></span>
                                        <span class="font-bold text-amber-300 font-mono text-sm"><?= formatRupiah($cekBillingResult['jumlah_bayar']) ?></span>
                                    </div>
                                    <?php if ($cekBillingResult['ntpn']): ?>
                                        <div class="text-xs text-slate-300 pt-1 flex justify-between">
                                            <span>NTPN:</span>
                                            <span class="font-mono text-emerald-300 font-bold"><?= htmlspecialchars($cekBillingResult['ntpn']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="pt-2 flex justify-end">
                                        <a href="cetak_billing.php?id=<?= $cekBillingResult['id'] ?>" target="_blank" class="min-h-[44px] inline-flex items-center text-xs text-amber-300 hover:text-amber-200 font-semibold gap-1.5 underline">
                                            <i class="fa-solid fa-print"></i> Cetak Bukti Penerimaan Negara (BPN)
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-rose-950/40 border border-rose-500/40 rounded-xl p-3.5 text-xs text-rose-200 flex items-center gap-2">
                                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-base flex-shrink-0"></i>
                                    <span>Kode billing <strong><?= htmlspecialchars($searchedBilling) ?></strong> tidak terdaftar dalam basis data.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Live Mini Stats -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div class="bg-tax-950 rounded-xl p-3 border border-tax-800 text-center">
                            <span class="text-xs text-slate-300 block mb-0.5">Penerimaan Realisasi</span>
                            <span class="text-sm sm:text-base font-bold text-emerald-300 font-mono"><?= formatRupiah($totalPenerimaan) ?></span>
                        </div>
                        <div class="bg-tax-950 rounded-xl p-3 border border-tax-800 text-center">
                            <span class="text-xs text-slate-300 block mb-0.5">Total Billing</span>
                            <span class="text-sm sm:text-base font-bold text-amber-300 font-mono"><?= $totalBilling ?> Transaksi</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Stats Metrics Bar (Real Data from MySQL, High Contrast) -->
<section class="bg-white border-b border-slate-200 py-6 relative z-20 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="flex items-center space-x-3.5 p-2">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-tax-800 flex items-center justify-center text-xl font-bold flex-shrink-0">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-900"><?= $totalWP ?></span>
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">Wajib Pajak</p>
                </div>
            </div>
            <div class="flex items-center space-x-3.5 p-2">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <div>
                    <span class="text-lg sm:text-xl font-black text-slate-900"><?= formatRupiah($totalPenerimaan) ?></span>
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">Penerimaan Negara</p>
                </div>
            </div>
            <div class="flex items-center space-x-3.5 p-2">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                    <i class="fa-solid fa-file-invoice text-xl"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-900"><?= $totalSPT ?></span>
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">SPT Terlapor</p>
                </div>
            </div>
            <div class="flex items-center space-x-3.5 p-2">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div>
                    <span class="text-2xl font-black text-slate-900"><?= $totalKPP ?></span>
                    <p class="text-xs font-bold text-slate-600 uppercase tracking-wide">Kantor KPP</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 1: Pengertian & 3 Fungsi Utama (Sesuai Bab 1 Docx - Purpose Driven Cards, R-14) -->
<section class="py-14 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-tax-950">
                Pengertian & Tiga Fungsi Utama Sistem Perpajakan
            </h2>
            <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                Basis data relasional untuk mencatat, mengelola, dan memproses data pendaftaran, transaksi, serta pembayaran pajak negara secara otomatis dan konsisten.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1: Wajib Pajak -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-tax-800 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-blue-800 border border-blue-200">Master Data</span>
                    </div>
                    <h3 class="text-lg font-bold text-tax-950 mb-2">1. Manajemen Wajib Pajak</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Menyimpan identitas tunggal Wajib Pajak (NPWP format 16 digit & NIK) untuk Orang Pribadi dan Badan hukum, lengkap dengan domisili KPP dan Account Representative (AR).
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="wajib_pajak.php" class="inline-flex items-center min-h-[44px] text-xs font-bold text-tax-800 hover:text-amber-600 transition gap-1.5 focus:ring-1 focus:ring-amber-400 rounded px-1">
                        Kelola Data Wajib Pajak <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Card 2: Otomasi Perhitungan -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200">Kalkulasi Otomatis</span>
                    </div>
                    <h3 class="text-lg font-bold text-tax-950 mb-2">2. Otomasi Perhitungan</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Menghitung kewajiban pajak (PPN 11%, PPh 21, PPh 23, PBB) secara presisi dari Dasar Pengenaan Pajak (DPP) sesuai tarif standar resmi dalam basis data.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="#kalkulator" class="inline-flex items-center min-h-[44px] text-xs font-bold text-amber-700 hover:text-amber-800 transition gap-1.5 focus:ring-1 focus:ring-amber-400 rounded px-1">
                        Buka Kalkulator Pajak <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Card 3: Pelaporan SPT -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200">Kepatuhan Pajak</span>
                    </div>
                    <h3 class="text-lg font-bold text-tax-950 mb-2">3. Pelaporan & Kepatuhan</h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4">
                        Menyediakan riwayat transaksi setoran negara, pelaporan SPT Masa/Tahunan dengan Bukti Penerimaan Surat (BPS), dan pencatatan status pelunasan.
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100">
                    <a href="spt.php" class="inline-flex items-center min-h-[44px] text-xs font-bold text-emerald-700 hover:text-emerald-800 transition gap-1.5 focus:ring-1 focus:ring-amber-400 rounded px-1">
                        Pelaporan SPT Pajak <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Tampilan Data Real Sesuai Dokumen Docx (Bab 2) -->
<section class="py-14 bg-white border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <span class="text-xs font-bold text-tax-700 uppercase tracking-wider">Implementasi Tabel Basis Data &bull; Bab 2 Docx</span>
                <h2 class="text-2xl font-black text-tax-950 mt-1">Sampel Data Master & Transaksional</h2>
                <p class="text-sm text-slate-600">Representasi tabel relasional sesuai yang tertera pada lembar dokumen acuan.</p>
            </div>
            <div>
                <a href="dashboard.php" class="min-h-[44px] px-5 py-2.5 bg-tax-900 hover:bg-tax-800 text-white rounded-xl text-xs font-semibold inline-flex items-center gap-2 focus:ring-2 focus:ring-amber-400">
                    <i class="fa-solid fa-table-columns"></i> Buka Dashboard CRUD
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Sub-table 1: Wajib Pajak 2.1 -->
            <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-tax-950 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-id-badge text-amber-600"></i> 2.1 Data Wajib Pajak (Master Data)
                    </h3>
                    <a href="wajib_pajak.php" class="min-h-[44px] inline-flex items-center text-xs font-semibold text-tax-800 hover:underline">Kelola Data</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white rounded-xl shadow-sm border border-slate-200">
                        <thead class="bg-tax-900 text-white uppercase text-[10px] tracking-wider">
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">NPWP</th>
                                <th class="p-3">Nama Wajib Pajak</th>
                                <th class="p-3">Alamat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($sampleWP as $wp): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 font-mono font-bold text-slate-600"><?= $wp['id'] ?></td>
                                    <td class="p-3 font-mono text-slate-900 font-medium"><?= htmlspecialchars($wp['npwp']) ?></td>
                                    <td class="p-3 font-bold text-tax-950"><?= htmlspecialchars($wp['nama_wp']) ?></td>
                                    <td class="p-3 text-slate-700 truncate max-w-xs"><?= htmlspecialchars($wp['alamat']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sub-table 2: Jenis Pajak 2.2 -->
            <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-tax-950 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-percent text-amber-600"></i> 2.2 Jenis Pajak (Master Data)
                    </h3>
                    <a href="jenis_pajak.php" class="min-h-[44px] inline-flex items-center text-xs font-semibold text-tax-800 hover:underline">Kelola Tarif</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white rounded-xl shadow-sm border border-slate-200">
                        <thead class="bg-tax-900 text-white uppercase text-[10px] tracking-wider">
                            <tr>
                                <th class="p-3">ID</th>
                                <th class="p-3">Kode KAP</th>
                                <th class="p-3">Nama Jenis Pajak</th>
                                <th class="p-3 text-right">Tarif (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($sampleJenis as $jp): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 font-mono font-bold text-slate-600"><?= $jp['id'] ?></td>
                                    <td class="p-3 font-mono text-slate-900 font-semibold"><?= htmlspecialchars($jp['kode_akun_pajak']) ?></td>
                                    <td class="p-3 font-bold text-tax-950"><?= htmlspecialchars($jp['nama_jenis_pajak']) ?></td>
                                    <td class="p-3 text-right font-mono font-bold text-amber-700"><?= number_format($jp['tarif_standar'], 2) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sub-table 3: Transaksi Pajak 2.3 -->
            <div class="lg:col-span-2 bg-slate-50 rounded-xl p-5 border border-slate-200">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-tax-950 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-amber-600"></i> 2.3 Transaksi Pajak (Transaksional Sesuai Docx)
                    </h3>
                    <a href="pembayaran.php" class="min-h-[44px] inline-flex items-center text-xs font-semibold text-tax-800 hover:underline">Buka Seluruh Transaksi</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white rounded-xl shadow-sm border border-slate-200">
                        <thead class="bg-tax-900 text-white uppercase text-[10px] tracking-wider">
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
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($sampleTransaksi as $tr): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-3 font-mono font-bold text-slate-600"><?= $tr['id'] ?></td>
                                    <td class="p-3 font-bold text-tax-950"><?= htmlspecialchars($tr['nama_wp']) ?></td>
                                    <td class="p-3 text-slate-800"><?= htmlspecialchars($tr['nama_jenis_pajak']) ?></td>
                                    <td class="p-3 text-right font-mono text-slate-800 font-semibold"><?= formatRupiah($tr['dpp']) ?></td>
                                    <td class="p-3 text-right font-mono font-extrabold text-tax-900"><?= formatRupiah($tr['jumlah_bayar']) ?></td>
                                    <td class="p-3 text-center">
                                        <?php if ($tr['status_pembayaran'] === 'Paid'): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                LUNAS
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                PENDING
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center space-x-1">
                                        <a href="cetak_billing.php?id=<?= $tr['id'] ?>" target="_blank" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold transition" title="Cetak BPN">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <a href="pembayaran.php" class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-950 text-xs font-semibold transition">
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

<!-- Interactive Tax Calculator Section (Accessible Tap Targets R-03) -->
<section id="kalkulator" class="py-14 bg-slate-100 border-b border-slate-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-tax-950">
                Kalkulator Pajak Interaktif
            </h2>
            <p class="text-slate-600 text-sm mt-1">
                Hitung estimasi kewajiban pajak terutang berdasarkan Dasar Pengenaan Pajak (DPP) dan tarif standar yang berlaku.
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-md p-6 sm:p-9 border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <!-- Inputs -->
                <div class="space-y-5">
                    <div>
                        <label for="calcJenisPajak" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-2">
                            1. Pilih Objek / Jenis Pajak
                        </label>
                        <select id="calcJenisPajak" class="w-full min-h-[44px] bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none" onchange="hitungPajakLive()">
                            <?php foreach ($listJenisPajak as $jp): ?>
                                <option value="<?= $jp['tarif_standar'] ?>" data-nama="<?= htmlspecialchars($jp['nama_jenis_pajak']) ?>" data-kap="<?= htmlspecialchars($jp['kode_akun_pajak']) ?>">
                                    <?= htmlspecialchars($jp['nama_jenis_pajak']) ?> (<?= number_format($jp['tarif_standar'], 2) ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="calcDPP" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-2">
                            2. Masukkan Dasar Pengenaan Pajak (DPP)
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-sm font-bold text-slate-600">Rp</span>
                            <input type="number" id="calcDPP" value="100000000" min="0" step="10000" class="w-full min-h-[44px] bg-slate-50 border border-slate-300 rounded-xl pl-12 pr-4 py-2.5 text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none" oninput="hitungPajakLive()">
                        </div>
                        <span class="text-xs text-slate-600 mt-1 block">Contoh: Rp 100.000.000 atau Rp 50.000.000</span>
                    </div>

                    <!-- Preset DPP Buttons with min 44x44px touch targets (R-03 / antislop-layoutmobile) -->
                    <div>
                        <span class="text-slate-700 font-semibold text-xs block mb-2">Pilihan Cepat Nilai DPP:</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="setDPP(15000000)" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-900 font-mono font-bold text-xs border border-slate-200 transition focus:ring-2 focus:ring-amber-500">15 Juta</button>
                            <button type="button" onclick="setDPP(50000000)" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-900 font-mono font-bold text-xs border border-slate-200 transition focus:ring-2 focus:ring-amber-500">50 Juta</button>
                            <button type="button" onclick="setDPP(100000000)" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-900 font-mono font-bold text-xs border border-slate-200 transition focus:ring-2 focus:ring-amber-500">100 Juta</button>
                            <button type="button" onclick="setDPP(500000000)" class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-900 font-mono font-bold text-xs border border-slate-200 transition focus:ring-2 focus:ring-amber-500">500 Juta</button>
                        </div>
                    </div>
                </div>

                <!-- Live Results Card (High Contrast Solid Dark Surface) -->
                <div class="bg-tax-950 text-white rounded-xl p-6 sm:p-7 space-y-4 border border-tax-800 shadow-md">
                    <div class="flex justify-between items-center border-b border-tax-800 pb-3">
                        <span class="text-xs font-bold text-amber-300 uppercase tracking-wider">Hasil Perhitungan Pajak</span>
                        <span id="resTarif" class="text-xs font-bold bg-amber-400/20 text-amber-300 px-2.5 py-1 rounded-md border border-amber-400/30">Tarif 11.00%</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <span class="text-xs text-slate-300 block mb-0.5">Dasar Pengenaan Pajak (DPP):</span>
                            <span id="resDPP" class="text-base sm:text-lg font-mono font-bold text-slate-100">Rp 100.000.000</span>
                        </div>
                        <div class="pt-2 border-t border-tax-800">
                            <span class="text-xs text-slate-300 block mb-0.5">Nilai Pajak Terutang:</span>
                            <span id="resPajak" class="text-2xl sm:text-3xl font-mono font-extrabold text-amber-300">Rp 11.000.000</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-tax-800 flex flex-col sm:flex-row gap-2.5">
                        <a href="pembayaran.php?action=create" class="flex-1 min-h-[44px] inline-flex items-center justify-center py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs transition focus:ring-2 focus:ring-amber-400">
                            <i class="fa-solid fa-plus-circle mr-1.5"></i> Terbitkan Kode Billing
                        </a>
                        <button type="button" onclick="salinHasil()" class="min-h-[44px] py-2.5 px-4 rounded-xl bg-tax-800 hover:bg-tax-700 text-slate-200 text-xs font-semibold border border-tax-700 transition focus:ring-2 focus:ring-amber-400">
                            <i class="fa-regular fa-copy mr-1.5"></i> Salin Nilai
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section: Skema Relasi Database 6 Tabel (Sesuai Bab 4 Docx) -->
<section class="py-14 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-tax-950">
                Struktur 6 Tabel Terintegrasi (MySQL DDL)
            </h2>
            <p class="text-slate-600 text-sm mt-1">
                Seluruh tabel terhubung menggunakan Foreign Key, Indexing Unik, dan Integritas Referensial ON DELETE / ON UPDATE.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Table 1: kpp -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 hover:border-tax-500 transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-200">
                    <span class="font-mono font-bold text-xs text-tax-950 bg-slate-200 px-2 py-1 rounded-md">4.1 kpp</span>
                    <span class="text-xs text-slate-600 font-medium">Master KPP</span>
                </div>
                <ul class="text-xs space-y-1.5 text-slate-700 font-mono">
                    <li><strong class="text-amber-700">PK:</strong> id (INT)</li>
                    <li><strong class="text-tax-800">UQ:</strong> kode_kpp (VARCHAR 3)</li>
                    <li>nama_kpp (VARCHAR 100)</li>
                    <li>alamat (TEXT) &bull; no_telepon</li>
                </ul>
            </div>

            <!-- Table 2: petugas_pajak -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 hover:border-tax-500 transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-200">
                    <span class="font-mono font-bold text-xs text-tax-950 bg-slate-200 px-2 py-1 rounded-md">4.2 petugas_pajak</span>
                    <span class="text-xs text-slate-600 font-medium">Account Rep (AR)</span>
                </div>
                <ul class="text-xs space-y-1.5 text-slate-700 font-mono">
                    <li><strong class="text-amber-700">PK:</strong> id (INT)</li>
                    <li><strong class="text-tax-800">UQ:</strong> nip (VARCHAR 18)</li>
                    <li><strong class="text-blue-700">FK:</strong> kpp_id &rarr; kpp.id</li>
                    <li>nama_petugas &bull; jabatan (ENUM)</li>
                </ul>
            </div>

            <!-- Table 3: wajib_pajak -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 hover:border-tax-500 transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-200">
                    <span class="font-mono font-bold text-xs text-tax-950 bg-slate-200 px-2 py-1 rounded-md">4.3 wajib_pajak</span>
                    <span class="text-xs text-slate-600 font-medium">Master WP</span>
                </div>
                <ul class="text-xs space-y-1.5 text-slate-700 font-mono">
                    <li><strong class="text-amber-700">PK:</strong> id (INT)</li>
                    <li><strong class="text-tax-800">UQ:</strong> npwp (VARCHAR 25)</li>
                    <li><strong class="text-blue-700">FK:</strong> kpp_id, petugas_ar_id</li>
                    <li>nama_wp &bull; jenis_wp &bull; status_efin</li>
                </ul>
            </div>

            <!-- Table 4: jenis_pajak -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 hover:border-tax-500 transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-200">
                    <span class="font-mono font-bold text-xs text-tax-950 bg-slate-200 px-2 py-1 rounded-md">4.4 jenis_pajak</span>
                    <span class="text-xs text-slate-600 font-medium">Master Tarif</span>
                </div>
                <ul class="text-xs space-y-1.5 text-slate-700 font-mono">
                    <li><strong class="text-amber-700">PK:</strong> id (INT)</li>
                    <li><strong class="text-tax-800">UQ:</strong> kode_akun_pajak (KAP 6)</li>
                    <li>nama_jenis_pajak (VARCHAR)</li>
                    <li>kategori_pajak &bull; tarif_standar (%)</li>
                </ul>
            </div>

            <!-- Table 5: spt -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 hover:border-tax-500 transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-200">
                    <span class="font-mono font-bold text-xs text-tax-950 bg-slate-200 px-2 py-1 rounded-md">4.5 spt</span>
                    <span class="text-xs text-slate-600 font-medium">Pelaporan SPT</span>
                </div>
                <ul class="text-xs space-y-1.5 text-slate-700 font-mono">
                    <li><strong class="text-amber-700">PK:</strong> id (INT)</li>
                    <li><strong class="text-tax-800">UQ:</strong> nomor_spt / BPS</li>
                    <li><strong class="text-blue-700">FK:</strong> wajib_pajak_id, jenis_pajak_id</li>
                    <li>tahun_pajak &bull; status_spt &bull; nominal</li>
                </ul>
            </div>

            <!-- Table 6: pembayaran_pajak -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 hover:border-tax-500 transition">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-200">
                    <span class="font-mono font-bold text-xs text-tax-950 bg-slate-200 px-2 py-1 rounded-md">4.6 pembayaran_pajak</span>
                    <span class="text-xs text-slate-600 font-medium">Billing & Kas</span>
                </div>
                <ul class="text-xs space-y-1.5 text-slate-700 font-mono">
                    <li><strong class="text-amber-700">PK:</strong> id (INT)</li>
                    <li><strong class="text-tax-800">UQ:</strong> kode_billing (15), ntpn (16)</li>
                    <li><strong class="text-blue-700">FK:</strong> spt_id, wajib_pajak_id, jenis_id</li>
                    <li>dpp &bull; jumlah_bayar &bull; status</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-12 bg-tax-950 text-white border-t border-tax-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
        <h2 class="text-2xl sm:text-3xl font-extrabold">Akses Pengelolaan Basis Data Perpajakan</h2>
        <p class="text-slate-300 text-sm max-w-xl mx-auto">
            Kelola Wajib Pajak baru, terbitkan kode billing 15 digit, dan catat pelaporan SPT dengan validasi integritas basis data MySQL.
        </p>
        <div class="flex flex-wrap justify-center gap-3.5 pt-2">
            <a href="dashboard.php" class="min-h-[44px] inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-sm shadow-md transition">
                <i class="fa-solid fa-table-columns mr-2"></i> Buka Dashboard Utama
            </a>
            <a href="wajib_pajak.php?action=create" class="min-h-[44px] inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-tax-800 hover:bg-tax-700 text-white font-semibold text-sm border border-tax-700 transition">
                <i class="fa-solid fa-user-plus mr-2"></i> Daftarkan Wajib Pajak Baru
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
