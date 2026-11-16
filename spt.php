<?php
// spt.php - CRUD Surat Pemberitahuan Pajak (SPT)
require_once __DIR__ . '/config/database.php';

$page_title = 'Pelaporan Surat Pemberitahuan (SPT)';
$active_nav = 'spt';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Wajib Pajak & Jenis Pajak for Dropdowns
$listWP = $pdo->query("SELECT id, npwp, nama_wp FROM wajib_pajak ORDER BY nama_wp ASC")->fetchAll();
$listJenis = $pdo->query("SELECT id, kode_akun_pajak, nama_jenis_pajak, tarif_standar FROM jenis_pajak ORDER BY id ASC")->fetchAll();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'create') {
        $nomor_spt = trim($_POST['nomor_spt'] ?? '');
        $wajib_pajak_id = (int)$_POST['wajib_pajak_id'];
        $jenis_pajak_id = (int)$_POST['jenis_pajak_id'];
        $jenis_spt = $_POST['jenis_spt'] ?? 'Tahunan';
        $tahun_pajak = (int)$_POST['tahun_pajak'];
        $masa_pajak = !empty($_POST['masa_pajak']) ? trim($_POST['masa_pajak']) : null;
        $pembetulan_ke = (int)($_POST['pembetulan_ke'] ?? 0);
        $status_spt = $_POST['status_spt'] ?? 'Nihil';
        $nominal_pajak = (float)$_POST['nominal_pajak'];

        if (empty($nomor_spt)) {
            $nomor_spt = generateNomorBPS($pdo, 'SPT');
        }

        if (empty($wajib_pajak_id) || empty($jenis_pajak_id) || empty($tahun_pajak)) {
            setFlashMessage('danger', 'Mohon lengkapi Wajib Pajak, Jenis Pajak, dan Tahun Pajak!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO spt (nomor_spt, wajib_pajak_id, jenis_pajak_id, jenis_spt, tahun_pajak, masa_pajak, pembetulan_ke, status_spt, nominal_pajak)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomor_spt, $wajib_pajak_id, $jenis_pajak_id, $jenis_spt, $tahun_pajak, $masa_pajak, $pembetulan_ke, $status_spt, $nominal_pajak]);
                setFlashMessage('success', "Pelaporan SPT berhasil disimpan dengan Bukti Penerimaan Surat (BPS): {$nomor_spt}!");
                header("Location: spt.php");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menyimpan pelaporan SPT: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'update') {
        $edit_id = (int)$_POST['id'];
        $nomor_spt = trim($_POST['nomor_spt']);
        $wajib_pajak_id = (int)$_POST['wajib_pajak_id'];
        $jenis_pajak_id = (int)$_POST['jenis_pajak_id'];
        $jenis_spt = $_POST['jenis_spt'];
        $tahun_pajak = (int)$_POST['tahun_pajak'];
        $masa_pajak = !empty($_POST['masa_pajak']) ? trim($_POST['masa_pajak']) : null;
        $pembetulan_ke = (int)$_POST['pembetulan_ke'];
        $status_spt = $_POST['status_spt'];
        $nominal_pajak = (float)$_POST['nominal_pajak'];

        try {
            $stmt = $pdo->prepare("
                UPDATE spt
                SET nomor_spt = ?, wajib_pajak_id = ?, jenis_pajak_id = ?, jenis_spt = ?, tahun_pajak = ?, masa_pajak = ?, pembetulan_ke = ?, status_spt = ?, nominal_pajak = ?
                WHERE id = ?
            ");
            $stmt->execute([$nomor_spt, $wajib_pajak_id, $jenis_pajak_id, $jenis_spt, $tahun_pajak, $masa_pajak, $pembetulan_ke, $status_spt, $nominal_pajak, $edit_id]);
            setFlashMessage('success', "Data SPT {$nomor_spt} berhasil diperbarui!");
            header("Location: spt.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal memperbarui data SPT: ' . $e->getMessage());
        }
    } elseif ($postAction === 'delete') {
        $del_id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM spt WHERE id = ?");
            $stmt->execute([$del_id]);
            setFlashMessage('success', 'Data pelaporan SPT berhasil dihapus!');
            header("Location: spt.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal menghapus SPT: ' . $e->getMessage());
        }
    }
}

// Fetch Edit Data
$editData = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM spt WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

// Filter & Search List
$search = trim($_GET['search'] ?? '');
$filterJenisSPT = $_GET['filter_jenis_spt'] ?? '';
$filterStatusSPT = $_GET['filter_status_spt'] ?? '';

$sql = "
    SELECT s.*, w.nama_wp, w.npwp, j.nama_jenis_pajak, j.kode_akun_pajak,
           (SELECT COUNT(*) FROM pembayaran_pajak p WHERE p.spt_id = s.id AND p.status_pembayaran = 'Paid') as sudah_dibayar
    FROM spt s
    JOIN wajib_pajak w ON s.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON s.jenis_pajak_id = j.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (s.nomor_spt LIKE ? OR w.nama_wp LIKE ? OR w.npwp LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}
if ($filterJenisSPT !== '') {
    $sql .= " AND s.jenis_spt = ?";
    $params[] = $filterJenisSPT;
}
if ($filterStatusSPT !== '') {
    $sql .= " AND s.status_spt = ?";
    $params[] = $filterStatusSPT;
}

$sql .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listSPT = $stmt->fetchAll();

$newBPS = generateNomorBPS($pdo, 'SPT');

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/alerts.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-tax-700">Dashboard</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Pelaporan SPT</span>
            </div>
            <h1 class="text-2xl font-black text-tax-950 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-purple-600"></i> Pelaporan Surat Pemberitahuan (SPT)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">Mencatat bukti penerimaan surat (BPS), masa/tahun pajak, dan status perhitungan akhir (Bab 4.5).</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($action === 'list'): ?>
                <a href="spt.php?action=create" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md shadow-purple-600/20 transition flex items-center gap-2">
                    <i class="fa-solid fa-file-circle-plus"></i> Lapor SPT Baru
                </a>
            <?php else: ?>
                <a href="spt.php" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold text-xs transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar SPT
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FORM CREATE / EDIT SPT -->
    <?php if ($action === 'create' || ($action === 'edit' && $editData)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fadeIn">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                <h2 class="text-lg font-black text-tax-950 flex items-center gap-2">
                    <i class="fa-solid <?= $action === 'create' ? 'fa-file-circle-plus text-purple-600' : 'fa-pen-to-square text-tax-600' ?>"></i>
                    <?= $action === 'create' ? 'Pelaporan SPT Baru (Penerbitan BPS)' : 'Edit Data Pelaporan SPT' ?>
                </h2>
                <span class="text-xs text-slate-400">* Wajib diisi</span>
            </div>

            <form method="POST" action="spt.php" class="space-y-6">
                <input type="hidden" name="post_action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Nomor BPS / SPT -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Nomor Bukti Penerimaan Surat (BPS / No. SPT) *
                        </label>
                        <input type="text" name="nomor_spt" required maxlength="30"
                               value="<?= htmlspecialchars($action === 'edit' ? $editData['nomor_spt'] : $newBPS) ?>"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-bold tracking-wider text-slate-900 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>

                    <!-- Wajib Pajak -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Wajib Pajak Pelapor *
                        </label>
                        <select name="wajib_pajak_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">-- Pilih Wajib Pajak --</option>
                            <?php foreach ($listWP as $wp): ?>
                                <option value="<?= $wp['id'] ?>" <?= (($editData['wajib_pajak_id'] ?? '') == $wp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($wp['nama_wp']) ?> (<?= $wp['npwp'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Jenis Pajak -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Jenis Objek Pajak Dilaporkan *
                        </label>
                        <select name="jenis_pajak_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="">-- Pilih Jenis Pajak --</option>
                            <?php foreach ($listJenis as $jp): ?>
                                <option value="<?= $jp['id'] ?>" <?= (($editData['jenis_pajak_id'] ?? '') == $jp['id']) ? 'selected' : '' ?>>
                                    [<?= $jp['kode_akun_pajak'] ?>] <?= htmlspecialchars($jp['nama_jenis_pajak']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Jenis SPT (Masa / Tahunan) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Jenis Periode SPT *
                        </label>
                        <select name="jenis_spt" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="Tahunan" <?= ($editData['jenis_spt'] ?? 'Tahunan') === 'Tahunan' ? 'selected' : '' ?>>SPT Tahunan (1770 / 1771)</option>
                            <option value="Masa" <?= ($editData['jenis_spt'] ?? '') === 'Masa' ? 'selected' : '' ?>>SPT Masa (Bulanan)</option>
                        </select>
                    </div>

                    <!-- Tahun Pajak -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Tahun Pajak (YYYY) *
                        </label>
                        <input type="number" name="tahun_pajak" min="2000" max="2099" required
                               value="<?= $editData['tahun_pajak'] ?? date('Y') ?>"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-bold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>

                    <!-- Masa Pajak -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Masa Pajak (Format MM-MM, Khusus SPT Masa)
                        </label>
                        <input type="text" name="masa_pajak" maxlength="5"
                               value="<?= htmlspecialchars($editData['masa_pajak'] ?? '') ?>"
                               placeholder="Contoh: 01-01 atau 12-12"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    </div>

                    <!-- Pembetulan Ke -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Status Revisi (Pembetulan Ke) *
                        </label>
                        <input type="number" name="pembetulan_ke" min="0" max="99" required
                               value="<?= $editData['pembetulan_ke'] ?? 0 ?>"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        <span class="text-[11px] text-slate-400">0 = SPT Normal. 1, 2, dst = SPT Pembetulan.</span>
                    </div>

                    <!-- Status SPT -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Hasil Perhitungan Akhir SPT *
                        </label>
                        <select name="status_spt" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-purple-500 focus:outline-none">
                            <option value="Kurang Bayar" <?= ($editData['status_spt'] ?? 'Kurang Bayar') === 'Kurang Bayar' ? 'selected' : '' ?>>Kurang Bayar (Wajib disetor billing)</option>
                            <option value="Nihil" <?= ($editData['status_spt'] ?? '') === 'Nihil' ? 'selected' : '' ?>>Nihil (Tidak ada kewajiban setor)</option>
                            <option value="Lebih Bayar" <?= ($editData['status_spt'] ?? '') === 'Lebih Bayar' ? 'selected' : '' ?>>Lebih Bayar (Restitusi / Kompensasi)</option>
                        </select>
                    </div>

                    <!-- Nominal Pajak -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Nominal Nilai Pajak Terutang / Lebih / Kurang (Rp) *
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-2.5 text-sm font-bold text-slate-400">Rp</span>
                            <input type="number" name="nominal_pajak" step="1000" min="0" required
                                   value="<?= $editData['nominal_pajak'] ?? '11000000' ?>"
                                   class="w-full bg-purple-50/40 border border-purple-300 rounded-xl pl-12 pr-4 py-2.5 text-sm font-mono font-bold text-purple-950 focus:ring-2 focus:ring-purple-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="spt.php" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> <?= $action === 'create' ? 'Kirim & Simpan Pelaporan SPT' : 'Perbarui SPT' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- SEARCH & FILTER TOOLBAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <form method="GET" action="spt.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-6 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nomor BPS, Wajib Pajak, atau NPWP..."
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none">
            </div>

            <div class="sm:col-span-3">
                <select name="filter_jenis_spt" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    <option value="">Semua Periode (Masa & Tahunan)</option>
                    <option value="Tahunan" <?= $filterJenisSPT === 'Tahunan' ? 'selected' : '' ?>>Tahunan</option>
                    <option value="Masa" <?= $filterJenisSPT === 'Masa' ? 'selected' : '' ?>>Masa</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="filter_status_spt" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="Kurang Bayar" <?= $filterStatusSPT === 'Kurang Bayar' ? 'selected' : '' ?>>Kurang Bayar</option>
                    <option value="Nihil" <?= $filterStatusSPT === 'Nihil' ? 'selected' : '' ?>>Nihil</option>
                    <option value="Lebih Bayar" <?= $filterStatusSPT === 'Lebih Bayar' ? 'selected' : '' ?>>Lebih Bayar</option>
                </select>
            </div>

            <div class="sm:col-span-1 flex gap-1">
                <button type="submit" class="w-full min-h-[44px] bg-purple-700 hover:bg-purple-800 text-white rounded-xl py-2 px-3 text-xs font-semibold transition flex items-center justify-center gap-1 focus:ring-2 focus:ring-purple-500" title="Terapkan Filter">
                    <i class="fa-solid fa-filter"></i>
                    <span class="sm:hidden text-xs">Filter</span>
                </button>
                <?php if ($search || $filterJenisSPT || $filterStatusSPT): ?>
                    <a href="spt.php" class="min-h-[44px] px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs transition flex items-center justify-center" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Total Pelaporan SPT: <strong class="text-tax-950 font-mono"><?= count($listSPT) ?></strong> Dokumen
            </span>
            <span class="text-xs text-slate-600 font-medium">Bukti Penerimaan Surat Resmi (BPS)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">No. BPS SPT</th>
                        <th class="py-3 px-4">Wajib Pajak</th>
                        <th class="py-3 px-4">Objek Pajak</th>
                        <th class="py-3 px-4">Periode & Revisi</th>
                        <th class="py-3 px-4">Status Hasil</th>
                        <th class="py-3 px-4 text-right">Nominal Pajak</th>
                        <th class="py-3 px-4 text-center">Waktu Lapor</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($listSPT)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <i class="fa-solid fa-file-circle-xmark text-3xl text-slate-300"></i>
                                    <p class="text-xs font-bold text-slate-800">Tidak ada pelaporan SPT ditemukan</p>
                                    <p class="text-xs text-slate-500">Coba atur ulang filter pencarian atau rekam pelaporan SPT pajak baru.</p>
                                    <div class="pt-2">
                                        <a href="spt.php?action=create" class="inline-flex items-center min-h-[44px] px-4 py-2 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs transition">
                                            + Lapor SPT Baru Sekarang
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listSPT as $row): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-purple-900"><?= htmlspecialchars($row['nomor_spt']) ?></div>
                                    <span class="text-xs text-slate-600 font-medium">SPT <?= $row['jenis_spt'] ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($row['nama_wp']) ?></div>
                                    <div class="text-xs font-mono text-slate-600"><?= htmlspecialchars($row['npwp']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-800">
                                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($row['nama_jenis_pajak']) ?></span>
                                    <span class="block text-xs font-mono text-slate-500">KAP: <?= htmlspecialchars($row['kode_akun_pajak']) ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900">Tahun <?= $row['tahun_pajak'] ?> <?= $row['masa_pajak'] ? '('.$row['masa_pajak'].')' : '' ?></div>
                                    <div class="text-xs text-slate-500">Revisi ke-<?= $row['pembetulan_ke'] ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($row['status_spt'] === 'Kurang Bayar'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                            Kurang Bayar
                                        </span>
                                    <?php elseif ($row['status_spt'] === 'Lebih Bayar'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                            Lebih Bayar
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            Nihil
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-tax-950">
                                    <?= formatRupiah($row['nominal_pajak']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-600">
                                    <?= date('d/m/Y H:i', strtotime($row['tanggal_lapor'])) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <?php if ($row['status_spt'] === 'Kurang Bayar'): ?>
                                            <a href="pembayaran.php?action=create&spt_id=<?= $row['id'] ?>&wp_id=<?= $row['wajib_pajak_id'] ?>" class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs transition" title="Buat Billing Pembayaran">
                                                <i class="fa-solid fa-receipt mr-1"></i> Billing
                                            </a>
                                        <?php endif; ?>
                                        <a href="spt.php?action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit SPT">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form method="POST" action="spt.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data SPT ini?');" class="inline">
                                            <input type="hidden" name="post_action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition" title="Hapus SPT">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

