<?php
// dashboard.php - Dashboard CRUD Perpajakan
require_once __DIR__ . '/config/database.php';

$page_title = 'Dashboard Operasional Perpajakan';
$active_nav = 'dashboard';

// Ambil Statistik Ringkasan Nyata dari Basis Data
$countWP = $pdo->query("SELECT COUNT(*) FROM wajib_pajak")->fetchColumn();
$countWPBadan = $pdo->query("SELECT COUNT(*) FROM wajib_pajak WHERE jenis_wp = 'Badan'")->fetchColumn();
$countWPOP = $pdo->query("SELECT COUNT(*) FROM wajib_pajak WHERE jenis_wp = 'Orang Pribadi'")->fetchColumn();

$countSPT = $pdo->query("SELECT COUNT(*) FROM spt")->fetchColumn();
$sumPaid = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_pajak WHERE status_pembayaran = 'Paid'")->fetchColumn();
$sumUnpaid = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_pajak WHERE status_pembayaran = 'Unpaid'")->fetchColumn();

$countBillingPaid = $pdo->query("SELECT COUNT(*) FROM pembayaran_pajak WHERE status_pembayaran = 'Paid'")->fetchColumn();
$countBillingUnpaid = $pdo->query("SELECT COUNT(*) FROM pembayaran_pajak WHERE status_pembayaran = 'Unpaid'")->fetchColumn();

// Transaksi Terbaru
$stmtTransaksi = $pdo->query("
    SELECT p.*, w.nama_wp, w.npwp, j.nama_jenis_pajak, j.kode_akun_pajak
    FROM pembayaran_pajak p
    JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
    ORDER BY p.id DESC
    LIMIT 10
");
$transaksiList = $stmtTransaksi->fetchAll();

// SPT Terbaru
$stmtSPT = $pdo->query("
    SELECT s.*, w.nama_wp, w.npwp, j.nama_jenis_pajak
    FROM spt s
    JOIN wajib_pajak w ON s.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON s.jenis_pajak_id = j.id
    ORDER BY s.id DESC
    LIMIT 5
");
$sptList = $stmtSPT->fetchAll();

// Breakdown Jenis Pajak
$stmtBreakdown = $pdo->query("
    SELECT j.nama_jenis_pajak, j.kode_akun_pajak, j.tarif_standar,
           COUNT(p.id) as total_trx,
           COALESCE(SUM(CASE WHEN p.status_pembayaran = 'Paid' THEN p.jumlah_bayar ELSE 0 END), 0) as total_nominal_paid
    FROM jenis_pajak j
    LEFT JOIN pembayaran_pajak p ON j.id = p.jenis_pajak_id
    GROUP BY j.id
    ORDER BY total_nominal_paid DESC
");
$breakdownList = $stmtBreakdown->fetchAll();

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/alerts.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    
    <!-- Top Welcome & Quick Actions (Accessible Tap Targets R-03) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-black text-tax-950">Dashboard Administrasi Pajak</h1>
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">MySQL Terhubung</span>
            </div>
            <p class="text-sm text-slate-600 mt-1">
                Pencatatan penerimaan negara, kepatuhan Wajib Pajak, dan kontrol transaksi billing perpajakan.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="pembayaran.php?action=create" class="min-h-[44px] px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-sm transition flex items-center gap-1.5 focus:ring-2 focus:ring-amber-400">
                <i class="fa-solid fa-receipt"></i> + Buat Billing
            </a>
            <a href="wajib_pajak.php?action=create" class="min-h-[44px] px-4 py-2.5 rounded-xl bg-tax-900 hover:bg-tax-800 text-white font-semibold text-xs shadow-sm transition flex items-center gap-1.5 focus:ring-2 focus:ring-amber-400">
                <i class="fa-solid fa-user-plus"></i> + Wajib Pajak
            </a>
            <a href="spt.php?action=create" class="min-h-[44px] px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs border border-slate-200 transition flex items-center gap-1.5 focus:ring-2 focus:ring-amber-400">
                <i class="fa-solid fa-file-circle-plus"></i> + Lapor SPT
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid (High Contrast WCAG AA) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Penerimaan Pajak (Paid) -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Penerimaan Realisasi</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-700 font-mono"><?= formatRupiah($sumPaid) ?></div>
            <div class="mt-2 text-xs text-slate-600 flex items-center gap-1">
                <span class="font-bold text-emerald-800"><?= $countBillingPaid ?> Billing Lunas</span> &bull; Terverifikasi NTPN
            </div>
        </div>

        <!-- Card 2: Piutang / Pending (Unpaid) -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Billing Pending</span>
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-amber-700 font-mono"><?= formatRupiah($sumUnpaid) ?></div>
            <div class="mt-2 text-xs text-slate-600 flex items-center gap-1">
                <span class="font-bold text-amber-800"><?= $countBillingUnpaid ?> Billing Belum Setor</span>
            </div>
        </div>

        <!-- Card 3: Wajib Pajak Terdaftar -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Wajib Pajak (WP)</span>
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-tax-950 font-mono"><?= $countWP ?> WP</div>
            <div class="mt-2 text-xs text-slate-600 flex items-center justify-between">
                <span>Badan: <strong class="text-slate-900"><?= $countWPBadan ?></strong></span>
                <span>Orang Pribadi: <strong class="text-slate-900"><?= $countWPOP ?></strong></span>
            </div>
        </div>

        <!-- Card 4: SPT Terlaporkan -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">SPT Masuk</span>
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-tax-950 font-mono"><?= $countSPT ?> Laporan</div>
            <div class="mt-2 text-xs text-slate-600 flex items-center gap-1">
                <span class="text-purple-800 font-bold">BPS Elektronik</span> &bull; Masa & Tahunan
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left 8 Cols: Transaksi Pembayaran Pajak -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-lg font-black text-tax-950 flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-amber-600"></i> Transaksi Pembayaran Pajak Terbaru
                        </h2>
                        <p class="text-xs text-slate-600">Kode billing, DPP, jumlah setoran kas negara, dan status validasi NTPN.</p>
                    </div>
                    <a href="pembayaran.php" class="min-h-[44px] inline-flex items-center text-xs font-bold text-tax-800 hover:text-amber-700 transition">
                        Buka Seluruh Transaksi
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-3">Kode Billing</th>
                                <th class="py-3 px-3">Wajib Pajak</th>
                                <th class="py-3 px-3">Jenis Pajak</th>
                                <th class="py-3 px-3 text-right">Jumlah Pajak</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($transaksiList)): ?>
                                <!-- Informative Actionable Empty State (R-27) -->
                                <tr>
                                    <td colspan="6" class="py-10 text-center">
                                        <div class="max-w-xs mx-auto space-y-2">
                                            <i class="fa-solid fa-receipt text-3xl text-slate-300"></i>
                                            <p class="text-xs font-bold text-slate-800">Belum ada transaksi pembayaran pajak</p>
                                            <p class="text-xs text-slate-500">Terbitkan kode billing 15 digit baru untuk mencatat setoran pajak pertama.</p>
                                            <div class="pt-2">
                                                <a href="pembayaran.php?action=create" class="inline-flex items-center min-h-[44px] px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs transition">
                                                    + Terbitkan Billing Baru
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksiList as $row): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3.5 px-3">
                                            <div class="font-mono font-bold text-tax-950"><?= htmlspecialchars($row['kode_billing']) ?></div>
                                            <?php if ($row['ntpn']): ?>
                                                <div class="text-[11px] font-mono text-emerald-700 font-semibold">NTPN: <?= htmlspecialchars($row['ntpn']) ?></div>
                                            <?php else: ?>
                                                <div class="text-[11px] font-mono text-slate-500">Menunggu pembayaran</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-3">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($row['nama_wp']) ?></div>
                                            <div class="text-xs font-mono text-slate-500"><?= htmlspecialchars($row['npwp']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-3">
                                            <span class="text-slate-800 font-medium"><?= htmlspecialchars($row['nama_jenis_pajak']) ?></span>
                                            <span class="block text-xs font-mono text-slate-500">KAP: <?= htmlspecialchars($row['kode_akun_pajak']) ?></span>
                                        </td>
                                        <td class="py-3.5 px-3 text-right font-mono font-black text-tax-950">
                                            <?= formatRupiah($row['jumlah_bayar']) ?>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <?php if ($row['status_pembayaran'] === 'Paid'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    <i class="fa-solid fa-check mr-1"></i> LUNAS
                                                </span>
                                            <?php elseif ($row['status_pembayaran'] === 'Unpaid'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                    <i class="fa-solid fa-clock mr-1"></i> PENDING
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                    EXPIRED
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="cetak_billing.php?id=<?= $row['id'] ?>" target="_blank" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition" title="Cetak Surat Setoran Pajak (SSP/BPN)">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                                <?php if ($row['status_pembayaran'] === 'Unpaid'): ?>
                                                    <a href="pembayaran.php?action=pay&id=<?= $row['id'] ?>" class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition" title="Konfirmasi Pembayaran">
                                                        Bayar
                                                    </a>
                                                <?php endif; ?>
                                                <a href="pembayaran.php?action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit Transaksi">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SPT Terkini -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <h2 class="text-base font-black text-tax-950 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-purple-700"></i> Pelaporan SPT Terkini
                    </h2>
                    <a href="spt.php" class="min-h-[44px] inline-flex items-center text-xs font-bold text-tax-800 hover:text-amber-700">Buka Modul SPT</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">No. BPS</th>
                                <th class="py-2.5 px-3">Wajib Pajak</th>
                                <th class="py-2.5 px-3">Periode</th>
                                <th class="py-2.5 px-3">Status SPT</th>
                                <th class="py-2.5 px-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($sptList)): ?>
                                <!-- Informative Actionable Empty State (R-27) -->
                                <tr>
                                    <td colspan="5" class="py-8 text-center">
                                        <p class="text-xs font-semibold text-slate-700 mb-2">Belum ada pelaporan SPT terdaftar</p>
                                        <a href="spt.php?action=create" class="inline-flex items-center min-h-[44px] px-3.5 py-2 rounded-lg bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs">
                                            + Lapor SPT Baru Sekarang
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sptList as $spt): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-2.5 px-3 font-mono font-bold text-tax-950"><?= htmlspecialchars($spt['nomor_spt']) ?></td>
                                        <td class="py-2.5 px-3 font-medium text-slate-900"><?= htmlspecialchars($spt['nama_wp']) ?></td>
                                        <td class="py-2.5 px-3 text-slate-700"><?= $spt['jenis_spt'] ?> <?= $spt['tahun_pajak'] ?> <?= $spt['masa_pajak'] ? '('.$spt['masa_pajak'].')' : '' ?></td>
                                        <td class="py-2.5 px-3">
                                            <span class="px-2.5 py-1 rounded-md text-xs font-bold <?= $spt['status_spt'] === 'Kurang Bayar' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                                <?= $spt['status_spt'] ?>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-tax-950"><?= formatRupiah($spt['nominal_pajak']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right 4 Cols: Breakdown Jenis Pajak & System Status -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Breakdown Jenis Pajak -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-tax-950 text-sm">Penerimaan per Jenis Pajak</h3>
                    <a href="jenis_pajak.php" class="min-h-[44px] inline-flex items-center text-xs font-semibold text-tax-800 hover:underline">Kelola Tarif</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($breakdownList as $item): ?>
                        <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold text-tax-950"><?= htmlspecialchars($item['nama_jenis_pajak']) ?></span>
                                <span class="font-mono text-amber-700 font-bold"><?= number_format($item['tarif_standar'], 2) ?>%</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-slate-600 mt-1.5">
                                <span><?= $item['total_trx'] ?> Transaksi</span>
                                <span class="font-mono font-bold text-emerald-700"><?= formatRupiah($item['total_nominal_paid']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Database & Environment Card -->
            <div class="bg-tax-950 text-white rounded-2xl p-6 shadow-md space-y-4 border border-tax-900">
                <div class="flex items-center gap-3 border-b border-tax-800 pb-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-tax-950 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-white">Laragon Server Lokal</h4>
                        <p class="text-xs text-slate-300">Status Server Aktif</p>
                    </div>
                </div>

                <div class="text-xs space-y-2.5 text-slate-300">
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-400">Database:</span>
                        <span class="font-mono text-amber-300 font-bold">db_perpajakan</span>
                    </div>
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-400">Versi PHP:</span>
                        <span class="font-mono text-slate-100"><?= phpversion() ?></span>
                    </div>
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-400">Folder Proyek:</span>
                        <span class="font-mono text-xs text-slate-200 truncate max-w-[180px]">laragon\www\perpajakan</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">URL Akses:</span>
                        <span class="font-mono text-emerald-400">localhost/perpajakan</span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="w-full min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-tax-800 hover:bg-tax-700 text-amber-300 font-bold text-xs border border-tax-700 transition gap-2">
                        <i class="fa-solid fa-database"></i> Buka Struktur di phpMyAdmin
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
