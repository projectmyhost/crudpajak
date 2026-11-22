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
    
    <!-- Top Welcome & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded border border-zinc-200">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-2xl font-bold text-black">Dashboard Administrasi Pajak</h1>
                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-zinc-100 text-black border border-zinc-300">MySQL Terhubung</span>
            </div>
            <p class="text-sm text-zinc-600 mt-1">
                Pencatatan penerimaan negara, kepatuhan Wajib Pajak, dan kontrol transaksi billing perpajakan.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="pembayaran.php?action=create" class="min-h-[40px] px-4 py-2 rounded bg-black hover:bg-zinc-800 text-white font-semibold text-xs transition flex items-center gap-1.5">
                + Buat Billing
            </a>
            <a href="wajib_pajak.php?action=create" class="min-h-[40px] px-4 py-2 rounded bg-transparent hover:bg-zinc-100 text-black font-semibold text-xs border border-black transition flex items-center gap-1.5">
                + Wajib Pajak
            </a>
            <a href="spt.php?action=create" class="min-h-[40px] px-4 py-2 rounded bg-zinc-100 hover:bg-zinc-200 text-black font-semibold text-xs border border-zinc-300 transition flex items-center gap-1.5">
                + Lapor SPT
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid (Monochrome) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Penerimaan Pajak (Paid) -->
        <div class="bg-white rounded border border-zinc-200 p-5">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider block mb-1">Penerimaan Realisasi</span>
            <div class="text-2xl font-bold text-black font-mono"><?= formatRupiah($sumPaid) ?></div>
            <div class="mt-2 text-xs text-zinc-500">
                <span class="font-bold text-black"><?= $countBillingPaid ?> Billing Lunas</span> &bull; Terverifikasi NTPN
            </div>
        </div>

        <!-- Card 2: Piutang / Pending (Unpaid) -->
        <div class="bg-white rounded border border-zinc-200 p-5">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider block mb-1">Billing Pending</span>
            <div class="text-2xl font-bold text-black font-mono"><?= formatRupiah($sumUnpaid) ?></div>
            <div class="mt-2 text-xs text-zinc-500">
                <span class="font-bold text-black"><?= $countBillingUnpaid ?> Belum Setor</span>
            </div>
        </div>

        <!-- Card 3: Wajib Pajak Terdaftar -->
        <div class="bg-white rounded border border-zinc-200 p-5">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider block mb-1">Wajib Pajak (WP)</span>
            <div class="text-2xl font-bold text-black font-mono"><?= $countWP ?> WP</div>
            <div class="mt-2 text-xs text-zinc-500 flex items-center justify-between">
                <span>Badan: <strong class="text-black"><?= $countWPBadan ?></strong></span>
                <span>Orang Pribadi: <strong class="text-black"><?= $countWPOP ?></strong></span>
            </div>
        </div>

        <!-- Card 4: SPT Terlaporkan -->
        <div class="bg-white rounded border border-zinc-200 p-5">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-wider block mb-1">SPT Masuk</span>
            <div class="text-2xl font-bold text-black font-mono"><?= $countSPT ?> Laporan</div>
            <div class="mt-2 text-xs text-zinc-500">
                <span class="text-black font-bold">BPS Elektronik</span> &bull; Masa &amp; Tahunan
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left 8 Cols: Transaksi Pembayaran Pajak -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white rounded border border-zinc-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-zinc-200">
                    <div>
                        <h2 class="text-base font-bold text-black">
                            Transaksi Pembayaran Pajak Terbaru
                        </h2>
                        <p class="text-xs text-zinc-500">Kode billing, DPP, jumlah setoran kas negara, dan status validasi NTPN.</p>
                    </div>
                    <a href="pembayaran.php" class="text-xs font-bold text-black hover:underline">
                        Buka Seluruh Transaksi &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white border border-zinc-200">
                        <thead>
                            <tr>
                                <th class="py-3 px-3">Kode Billing</th>
                                <th class="py-3 px-3">Wajib Pajak</th>
                                <th class="py-3 px-3">Jenis Pajak</th>
                                <th class="py-3 px-3 text-right">Jumlah Pajak</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksiList)): ?>
                                <tr>
                                    <td colspan="6" class="py-10 text-center">
                                        <p class="text-xs font-bold text-black">Belum ada transaksi pembayaran pajak</p>
                                        <p class="text-xs text-zinc-500 mt-1">Terbitkan kode billing 15 digit baru untuk mencatat setoran pajak pertama.</p>
                                        <div class="pt-3">
                                            <a href="pembayaran.php?action=create" class="inline-flex items-center px-4 py-2 rounded bg-black text-white font-bold text-xs">
                                                + Terbitkan Billing Baru
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksiList as $row): ?>
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="font-mono font-bold text-black"><?= htmlspecialchars($row['kode_billing']) ?></div>
                                            <?php if ($row['ntpn']): ?>
                                                <div class="text-[11px] font-mono text-zinc-600 font-semibold">NTPN: <?= htmlspecialchars($row['ntpn']) ?></div>
                                            <?php else: ?>
                                                <div class="text-[11px] font-mono text-zinc-400">Menunggu pembayaran</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="font-bold text-black"><?= htmlspecialchars($row['nama_wp']) ?></div>
                                            <div class="text-xs font-mono text-zinc-500"><?= htmlspecialchars($row['npwp']) ?></div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="text-zinc-800 font-medium"><?= htmlspecialchars($row['nama_jenis_pajak']) ?></span>
                                            <span class="block text-xs font-mono text-zinc-500">KAP: <?= htmlspecialchars($row['kode_akun_pajak']) ?></span>
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-black">
                                            <?= formatRupiah($row['jumlah_bayar']) ?>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <?php if ($row['status_pembayaran'] === 'Paid'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-black text-white">
                                                    LUNAS
                                                </span>
                                            <?php elseif ($row['status_pembayaran'] === 'Unpaid'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border border-black text-black">
                                                    PENDING
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-zinc-200 text-zinc-700">
                                                    EXPIRED
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="cetak_billing.php?id=<?= $row['id'] ?>" target="_blank" class="px-2 py-1 rounded bg-zinc-100 hover:bg-zinc-200 text-black text-xs font-semibold" title="Cetak Surat Setoran Pajak (SSP/BPN)">
                                                    Cetak
                                                </a>
                                                <?php if ($row['status_pembayaran'] === 'Unpaid'): ?>
                                                    <a href="pembayaran.php?action=pay&id=<?= $row['id'] ?>" class="px-2 py-1 rounded bg-black text-white font-bold text-xs" title="Konfirmasi Pembayaran">
                                                        Bayar
                                                    </a>
                                                <?php endif; ?>
                                                <a href="pembayaran.php?action=edit&id=<?= $row['id'] ?>" class="px-2 py-1 rounded bg-zinc-100 hover:bg-zinc-200 text-black text-xs" title="Edit Transaksi">
                                                    Ubah
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
            <div class="bg-white rounded border border-zinc-200 p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-zinc-200">
                    <h2 class="text-base font-bold text-black">
                        Pelaporan SPT Terkini
                    </h2>
                    <a href="spt.php" class="text-xs font-bold text-black hover:underline">Buka Modul SPT &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs bg-white border border-zinc-200">
                        <thead>
                            <tr>
                                <th class="py-2.5 px-3">No. BPS</th>
                                <th class="py-2.5 px-3">Wajib Pajak</th>
                                <th class="py-2.5 px-3">Periode</th>
                                <th class="py-2.5 px-3">Status SPT</th>
                                <th class="py-2.5 px-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sptList)): ?>
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-zinc-500">
                                        Belum ada pelaporan SPT terdaftar.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sptList as $spt): ?>
                                    <tr>
                                        <td class="py-2.5 px-3 font-mono font-bold text-black"><?= htmlspecialchars($spt['nomor_spt']) ?></td>
                                        <td class="py-2.5 px-3 font-medium text-black"><?= htmlspecialchars($spt['nama_wp']) ?></td>
                                        <td class="py-2.5 px-3 text-zinc-600"><?= $spt['jenis_spt'] ?> <?= $spt['tahun_pajak'] ?> <?= $spt['masa_pajak'] ? '('.$spt['masa_pajak'].')' : '' ?></td>
                                        <td class="py-2.5 px-3">
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-zinc-100 text-black border border-zinc-200">
                                                <?= $spt['status_spt'] ?>
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-mono font-bold text-black"><?= formatRupiah($spt['nominal_pajak']) ?></td>
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
            <div class="bg-white rounded border border-zinc-200 p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-zinc-200 pb-3">
                    <h3 class="font-bold text-black text-sm">Penerimaan per Jenis Pajak</h3>
                    <a href="jenis_pajak.php" class="text-xs font-semibold text-zinc-600 hover:text-black hover:underline">Kelola Tarif</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($breakdownList as $item): ?>
                        <div class="bg-zinc-50 rounded p-3 border border-zinc-200">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold text-black"><?= htmlspecialchars($item['nama_jenis_pajak']) ?></span>
                                <span class="font-mono text-black font-bold"><?= number_format($item['tarif_standar'], 2) ?>%</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-zinc-500 mt-1">
                                <span><?= $item['total_trx'] ?> Transaksi</span>
                                <span class="font-mono font-bold text-black"><?= formatRupiah($item['total_nominal_paid']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Database & Environment Card -->
            <div class="bg-white text-black rounded p-6 space-y-4 border border-black">
                <div class="border-b border-zinc-200 pb-3">
                    <h4 class="font-bold text-sm text-black">Status Basis Data</h4>
                    <p class="text-xs text-zinc-500">Laragon MySQL Server</p>
                </div>

                <div class="text-xs space-y-2 text-zinc-600">
                    <div class="flex justify-between border-b border-zinc-100 pb-1">
                        <span>Database:</span>
                        <span class="font-mono text-black font-bold">db_perpajakan</span>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 pb-1">
                        <span>Versi PHP:</span>
                        <span class="font-mono text-black"><?= phpversion() ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>URL Akses:</span>
                        <span class="font-mono text-black font-semibold">localhost/perpajakan</span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="w-full min-h-[40px] inline-flex items-center justify-center px-4 py-2 rounded bg-black hover:bg-zinc-800 text-white font-semibold text-xs transition">
                        Buka di phpMyAdmin
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
