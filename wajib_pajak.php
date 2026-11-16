<?php
// wajib_pajak.php - CRUD Wajib Pajak
require_once __DIR__ . '/config/database.php';

$page_title = 'Kelola Wajib Pajak (Master Data)';
$active_nav = 'wajib_pajak';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch KPP & Petugas for dropdowns
$listKPP = $pdo->query("SELECT id, kode_kpp, nama_kpp FROM kpp ORDER BY kode_kpp ASC")->fetchAll();
$listAR = $pdo->query("SELECT p.id, p.nama_petugas, p.nip, k.kode_kpp FROM petugas_pajak p JOIN kpp k ON p.kpp_id = k.id WHERE p.status_aktif = 1 ORDER BY p.nama_petugas ASC")->fetchAll();

// HANDLE POST ACTIONS (CREATE / UPDATE / DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'create') {
        $npwp = trim($_POST['npwp'] ?? '');
        $nama_wp = trim($_POST['nama_wp'] ?? '');
        $jenis_wp = $_POST['jenis_wp'] ?? 'Orang Pribadi';
        $nik = !empty($_POST['nik']) ? trim($_POST['nik']) : null;
        $kpp_id = (int)($_POST['kpp_id'] ?? 0);
        $petugas_ar_id = !empty($_POST['petugas_ar_id']) ? (int)$_POST['petugas_ar_id'] : null;
        $alamat = trim($_POST['alamat'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status_efin = isset($_POST['status_efin']) ? 1 : 0;

        if (empty($npwp) || empty($nama_wp) || empty($kpp_id) || empty($alamat)) {
            setFlashMessage('danger', 'Mohon lengkapi seluruh field wajib (NPWP, Nama WP, KPP, Alamat)!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO wajib_pajak (npwp, nama_wp, jenis_wp, nik, kpp_id, petugas_ar_id, alamat, email, status_efin)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$npwp, $nama_wp, $jenis_wp, $nik, $kpp_id, $petugas_ar_id, $alamat, $email, $status_efin]);
                setFlashMessage('success', "Wajib Pajak '{$nama_wp}' berhasil ditambahkan ke database!");
                header("Location: wajib_pajak.php");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menyimpan Wajib Pajak: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'update') {
        $wp_id = (int)$_POST['id'];
        $npwp = trim($_POST['npwp'] ?? '');
        $nama_wp = trim($_POST['nama_wp'] ?? '');
        $jenis_wp = $_POST['jenis_wp'] ?? 'Orang Pribadi';
        $nik = !empty($_POST['nik']) ? trim($_POST['nik']) : null;
        $kpp_id = (int)($_POST['kpp_id'] ?? 0);
        $petugas_ar_id = !empty($_POST['petugas_ar_id']) ? (int)$_POST['petugas_ar_id'] : null;
        $alamat = trim($_POST['alamat'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status_efin = isset($_POST['status_efin']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("
                UPDATE wajib_pajak
                SET npwp = ?, nama_wp = ?, jenis_wp = ?, nik = ?, kpp_id = ?, petugas_ar_id = ?, alamat = ?, email = ?, status_efin = ?
                WHERE id = ?
            ");
            $stmt->execute([$npwp, $nama_wp, $jenis_wp, $nik, $kpp_id, $petugas_ar_id, $alamat, $email, $status_efin, $wp_id]);
            setFlashMessage('success', "Data Wajib Pajak '{$nama_wp}' berhasil diperbarui!");
            header("Location: wajib_pajak.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    } elseif ($postAction === 'delete') {
        $del_id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM wajib_pajak WHERE id = ?");
            $stmt->execute([$del_id]);
            setFlashMessage('success', 'Data Wajib Pajak dan relasi terkait berhasil dihapus!');
            header("Location: wajib_pajak.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}

// Data for Edit & View
$editData = null;
$viewData = null;
$wpSPT = [];
$wpBilling = [];

if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $pdo->prepare("
        SELECT w.*, k.kode_kpp, k.nama_kpp, p.nama_petugas, p.nip as nip_ar
        FROM wajib_pajak w
        JOIN kpp k ON w.kpp_id = k.id
        LEFT JOIN petugas_pajak p ON w.petugas_ar_id = p.id
        WHERE w.id = ?
    ");
    $stmt->execute([$id]);
    $wpData = $stmt->fetch();

    if ($action === 'edit') {
        $editData = $wpData;
    } else {
        $viewData = $wpData;
        // Fetch historical SPT
        $stmtS = $pdo->prepare("SELECT s.*, j.nama_jenis_pajak FROM spt s JOIN jenis_pajak j ON s.jenis_pajak_id = j.id WHERE s.wajib_pajak_id = ? ORDER BY s.id DESC");
        $stmtS->execute([$id]);
        $wpSPT = $stmtS->fetchAll();
        // Fetch historical Billing
        $stmtB = $pdo->prepare("SELECT b.*, j.nama_jenis_pajak FROM pembayaran_pajak b JOIN jenis_pajak j ON b.jenis_pajak_id = j.id WHERE b.wajib_pajak_id = ? ORDER BY b.id DESC");
        $stmtB->execute([$id]);
        $wpBilling = $stmtB->fetchAll();
    }
}

// Search & Filter for List
$search = trim($_GET['search'] ?? '');
$filterJenis = $_GET['filter_jenis'] ?? '';
$filterKPP = $_GET['filter_kpp'] ?? '';

$sql = "
    SELECT w.*, k.kode_kpp, k.nama_kpp, p.nama_petugas
    FROM wajib_pajak w
    JOIN kpp k ON w.kpp_id = k.id
    LEFT JOIN petugas_pajak p ON w.petugas_ar_id = p.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (w.nama_wp LIKE ? OR w.npwp LIKE ? OR w.nik LIKE ? OR w.email LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
if ($filterJenis !== '') {
    $sql .= " AND w.jenis_wp = ?";
    $params[] = $filterJenis;
}
if ($filterKPP !== '') {
    $sql .= " AND w.kpp_id = ?";
    $params[] = $filterKPP;
}

$sql .= " ORDER BY w.id ASC";
$stmtList = $pdo->prepare($sql);
$stmtList->execute($params);
$listWP = $stmtList->fetchAll();

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
                <span class="text-slate-800 font-semibold">Master Wajib Pajak</span>
            </div>
            <h1 class="text-2xl font-black text-tax-950 flex items-center gap-2">
                <i class="fa-solid fa-users text-amber-500"></i> Data Wajib Pajak (Master Data)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">Menyimpan identitas tunggal NPWP / NIK 16 digit sesuai spesifikasi dokumen bab 2.1 & 4.3.</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($action === 'list'): ?>
                <a href="wajib_pajak.php?action=create" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Tambah Wajib Pajak
                </a>
            <?php else: ?>
                <a href="wajib_pajak.php" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold text-xs transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FORM CREATE / EDIT -->
    <?php if ($action === 'create' || ($action === 'edit' && $editData)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fadeIn">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                <h2 class="text-lg font-black text-tax-950 flex items-center gap-2">
                    <i class="fa-solid <?= $action === 'create' ? 'fa-user-plus text-amber-500' : 'fa-user-pen text-tax-600' ?>"></i>
                    <?= $action === 'create' ? 'Pendaftaran Wajib Pajak Baru' : 'Edit Data Wajib Pajak' ?>
                </h2>
                <span class="text-xs text-slate-400">* Wajib diisi</span>
            </div>

            <form method="POST" action="wajib_pajak.php" class="space-y-6">
                <input type="hidden" name="post_action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- NPWP -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            NPWP (16 Digit / Format Resmi) *
                        </label>
                        <input type="text" name="npwp" required maxlength="25"
                               value="<?= htmlspecialchars($editData['npwp'] ?? '') ?>"
                               placeholder="Contoh: 01.234.567.8-012.000"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <span class="text-[11px] text-slate-400">Gunakan format NPWP 16 digit atau format NPWP 15 digit berformat titik.</span>
                    </div>

                    <!-- Jenis WP -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Kategori Wajib Pajak *
                        </label>
                        <select name="jenis_wp" id="jenis_wp_select" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="Orang Pribadi" <?= ($editData['jenis_wp'] ?? '') === 'Orang Pribadi' ? 'selected' : '' ?>>Orang Pribadi (Individu / Karyawan / Pekerja Bebas)</option>
                            <option value="Badan" <?= ($editData['jenis_wp'] ?? '') === 'Badan' ? 'selected' : '' ?>>Badan (PT, CV, Yayasan, BUMN)</option>
                        </select>
                    </div>

                    <!-- Nama Lengkap / Badan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Nama Terdaftar Wajib Pajak *
                        </label>
                        <input type="text" name="nama_wp" required maxlength="150"
                               value="<?= htmlspecialchars($editData['nama_wp'] ?? '') ?>"
                               placeholder="Contoh: PT Maju Sejahtera atau Budi Santoso"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <!-- NIK (Khusus OP) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            NIK (16 Digit - Khusus Orang Pribadi)
                        </label>
                        <input type="text" name="nik" maxlength="16"
                               value="<?= htmlspecialchars($editData['nik'] ?? '') ?>"
                               placeholder="Contoh: 3201011505880001"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <span class="text-[11px] text-slate-400">Kosongkan jika jenis Wajib Pajak adalah Badan.</span>
                    </div>

                    <!-- KPP Domisili -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Kantor Pelayanan Pajak (KPP) Terdaftar *
                        </label>
                        <select name="kpp_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">-- Pilih Kantor KPP --</option>
                            <?php foreach ($listKPP as $kpp): ?>
                                <option value="<?= $kpp['id'] ?>" <?= ($editData['kpp_id'] ?? '') == $kpp['id'] ? 'selected' : '' ?>>
                                    [<?= $kpp['kode_kpp'] ?>] <?= htmlspecialchars($kpp['nama_kpp']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Petugas AR -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Account Representative (AR) Pengampu
                        </label>
                        <select name="petugas_ar_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">-- Pilih Petugas AR (Opsional) --</option>
                            <?php foreach ($listAR as $ar): ?>
                                <option value="<?= $ar['id'] ?>" <?= ($editData['petugas_ar_id'] ?? '') == $ar['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ar['nama_petugas']) ?> (NIP: <?= $ar['nip'] ?> - KPP <?= $ar['kode_kpp'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Email Korespondensi *
                        </label>
                        <input type="email" name="email" required maxlength="100"
                               value="<?= htmlspecialchars($editData['email'] ?? '') ?>"
                               placeholder="finance@perusahaan.co.id"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <!-- Status EFIN -->
                    <div class="flex items-center pt-6">
                        <label class="relative flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="status_efin" value="1" <?= (!empty($editData['status_efin'])) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-bold text-slate-800">Status EFIN Aktif</span>
                                <p class="text-[11px] text-slate-500">Centang jika Wajib Pajak sudah mengaktifkan layanan DJP Online elektronik.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Alamat Domisili (Full width) -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Alamat Domisili / Tempat Kedudukan Terdaftar *
                        </label>
                        <textarea name="alamat" required rows="3" placeholder="Jl. Sudirman No. 45, RT/RW, Kelurahan, Kecamatan, Kota"
                                  class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"><?= htmlspecialchars($editData['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="wajib_pajak.php" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> <?= $action === 'create' ? 'Simpan Wajib Pajak' : 'Perbarui Data' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- VIEW DETAIL MODAL / SECTION -->
    <?php if ($action === 'view' && $viewData): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6 animate-fadeIn">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-tax-100 text-tax-800 flex items-center justify-center text-xl font-bold">
                        <i class="fa-solid <?= $viewData['jenis_wp'] === 'Badan' ? 'fa-building' : 'fa-user' ?>"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-tax-950"><?= htmlspecialchars($viewData['nama_wp']) ?></h2>
                        <span class="font-mono text-xs text-slate-500">NPWP: <?= htmlspecialchars($viewData['npwp']) ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="wajib_pajak.php?action=edit&id=<?= $viewData['id'] ?>" class="px-3 py-1.5 rounded-lg bg-tax-900 text-white font-semibold text-xs hover:bg-tax-800 transition">
                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                    </a>
                    <a href="pembayaran.php?action=create&wp_id=<?= $viewData['id'] ?>" class="px-3 py-1.5 rounded-lg bg-amber-500 text-tax-950 font-bold text-xs hover:bg-amber-600 transition">
                        <i class="fa-solid fa-receipt mr-1"></i> Buat Billing
                    </a>
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50 p-5 rounded-2xl border border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 block mb-1">Kategori WP:</span>
                    <span class="font-bold text-slate-900 px-2 py-0.5 rounded bg-white border border-slate-200"><?= $viewData['jenis_wp'] ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">NIK (Kependudukan):</span>
                    <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($viewData['nik'] ?? '-') ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Status EFIN:</span>
                    <?php if ($viewData['status_efin']): ?>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold"><i class="fa-solid fa-check-circle"></i> Terdaftar EFIN</span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700">Belum Aktif</span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">KPP Domisili:</span>
                    <span class="font-semibold text-slate-800">[<?= $viewData['kode_kpp'] ?>] <?= htmlspecialchars($viewData['nama_kpp']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Account Representative (AR):</span>
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($viewData['nama_petugas'] ?? 'Belum Ditugaskan') ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Email Korespondensi:</span>
                    <span class="font-semibold text-tax-700"><?= htmlspecialchars($viewData['email']) ?></span>
                </div>
                <div class="md:col-span-3">
                    <span class="text-slate-400 block mb-1">Alamat Terdaftar:</span>
                    <span class="text-slate-800 font-medium"><?= htmlspecialchars($viewData['alamat']) ?></span>
                </div>
            </div>

            <!-- Riwayat SPT & Riwayat Pembayaran -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
                <!-- SPT History -->
                <div class="border border-slate-200 rounded-xl p-4">
                    <h3 class="font-bold text-sm text-tax-950 mb-3 flex items-center justify-between">
                        <span><i class="fa-solid fa-file-invoice text-purple-600 mr-1.5"></i> Riwayat Pelaporan SPT</span>
                        <span class="text-xs font-mono text-slate-400"><?= count($wpSPT) ?> Laporan</span>
                    </h3>
                    <div class="overflow-x-auto text-xs">
                        <table class="w-full text-left">
                            <thead class="text-slate-400 border-b border-slate-100 text-[10px] uppercase">
                                <tr>
                                    <th class="pb-2">No. BPS</th>
                                    <th class="pb-2">Jenis / Periode</th>
                                    <th class="pb-2 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($wpSPT)): ?>
                                    <tr><td colspan="3" class="py-3 text-slate-400 text-center">Belum ada SPT.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($wpSPT as $s): ?>
                                        <tr>
                                            <td class="py-2 font-mono font-bold text-tax-900"><?= htmlspecialchars($s['nomor_spt']) ?></td>
                                            <td class="py-2"><?= htmlspecialchars($s['nama_jenis_pajak']) ?> (<?= $s['tahun_pajak'] ?>)</td>
                                            <td class="py-2 text-right font-mono font-bold"><?= formatRupiah($s['nominal_pajak']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Billing History -->
                <div class="border border-slate-200 rounded-xl p-4">
                    <h3 class="font-bold text-sm text-tax-950 mb-3 flex items-center justify-between">
                        <span><i class="fa-solid fa-receipt text-amber-500 mr-1.5"></i> Riwayat Transaksi Billing</span>
                        <span class="text-xs font-mono text-slate-400"><?= count($wpBilling) ?> Billing</span>
                    </h3>
                    <div class="overflow-x-auto text-xs">
                        <table class="w-full text-left">
                            <thead class="text-slate-400 border-b border-slate-100 text-[10px] uppercase">
                                <tr>
                                    <th class="pb-2">Kode Billing</th>
                                    <th class="pb-2 text-right">Jumlah</th>
                                    <th class="pb-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($wpBilling)): ?>
                                    <tr><td colspan="3" class="py-3 text-slate-400 text-center">Belum ada billing.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($wpBilling as $b): ?>
                                        <tr>
                                            <td class="py-2 font-mono font-bold text-tax-900"><?= htmlspecialchars($b['kode_billing']) ?></td>
                                            <td class="py-2 text-right font-mono font-bold"><?= formatRupiah($b['jumlah_bayar']) ?></td>
                                            <td class="py-2 text-center">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $b['status_pembayaran'] === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                                    <?= $b['status_pembayaran'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- SEARCH & FILTER TOOLBAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <form method="GET" action="wajib_pajak.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama WP, NPWP, NIK, atau Email..."
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="sm:col-span-3">
                <select name="filter_jenis" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">Semua Kategori WP</option>
                    <option value="Orang Pribadi" <?= $filterJenis === 'Orang Pribadi' ? 'selected' : '' ?>>Orang Pribadi</option>
                    <option value="Badan" <?= $filterJenis === 'Badan' ? 'selected' : '' ?>>Badan</option>
                </select>
            </div>

            <div class="sm:col-span-3">
                <select name="filter_kpp" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">Semua Kantor KPP</option>
                    <?php foreach ($listKPP as $kpp): ?>
                        <option value="<?= $kpp['id'] ?>" <?= $filterKPP == $kpp['id'] ? 'selected' : '' ?>>[<?= $kpp['kode_kpp'] ?>] <?= htmlspecialchars($kpp['nama_kpp']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

                    <div class="sm:col-span-1 flex gap-1">
                        <button type="submit" class="w-full min-h-[44px] bg-tax-900 hover:bg-tax-800 text-white rounded-xl py-2 px-3 text-xs font-semibold transition flex items-center justify-center gap-1 focus:ring-2 focus:ring-amber-500" title="Terapkan Filter">
                            <i class="fa-solid fa-filter"></i>
                            <span class="sm:hidden text-xs">Filter</span>
                        </button>
                        <?php if ($search || $filterJenis || $filterKPP): ?>
                            <a href="wajib_pajak.php" class="min-h-[44px] px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs transition flex items-center justify-center" title="Reset Filter">
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
                        Total Data: <strong class="text-tax-950 font-mono"><?= count($listWP) ?></strong> Wajib Pajak
                    </span>
                    <span class="text-xs text-slate-600 font-medium">Berdasarkan Nomor Pokok Wajib Pajak</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">ID</th>
                                <th class="py-3 px-4">NPWP & NIK</th>
                                <th class="py-3 px-4">Nama Wajib Pajak</th>
                                <th class="py-3 px-4">Kategori</th>
                                <th class="py-3 px-4">KPP Terdaftar</th>
                                <th class="py-3 px-4">Petugas AR</th>
                                <th class="py-3 px-4 text-center">EFIN</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($listWP)): ?>
                                <tr>
                                    <td colspan="8" class="py-12 text-center">
                                        <div class="max-w-xs mx-auto space-y-2">
                                            <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
                                            <p class="text-xs font-bold text-slate-800">Tidak ada data Wajib Pajak ditemukan</p>
                                            <p class="text-xs text-slate-500">Coba ubah kata kunci filter atau daftarkan Wajib Pajak baru ke dalam basis data.</p>
                                            <div class="pt-2">
                                                <a href="wajib_pajak.php?action=create" class="inline-flex items-center min-h-[44px] px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs transition">
                                                    + Daftarkan Wajib Pajak Baru
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listWP as $row): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3.5 px-4 font-mono font-bold text-slate-600"><?= $row['id'] ?></td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-mono font-bold text-tax-950"><?= htmlspecialchars($row['npwp']) ?></div>
                                            <?php if ($row['nik']): ?>
                                                <div class="text-xs font-mono text-slate-600">NIK: <?= htmlspecialchars($row['nik']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($row['nama_wp']) ?></div>
                                            <div class="text-xs text-slate-600 truncate max-w-xs"><?= htmlspecialchars($row['alamat']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold <?= $row['jenis_wp'] === 'Badan' ? 'bg-indigo-50 text-indigo-800 border border-indigo-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' ?>">
                                                <?= $row['jenis_wp'] ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="font-semibold text-slate-900">[<?= $row['kode_kpp'] ?>]</span>
                                            <span class="text-xs text-slate-600 block"><?= htmlspecialchars($row['nama_kpp']) ?></span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-800">
                                            <?= htmlspecialchars($row['nama_petugas'] ?? '-') ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <?php if ($row['status_efin']): ?>
                                                <span class="text-emerald-700 font-bold" title="EFIN Aktif"><i class="fa-solid fa-circle-check text-base"></i></span>
                                            <?php else: ?>
                                                <span class="text-slate-400" title="Belum Aktif"><i class="fa-regular fa-circle text-base"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="wajib_pajak.php?action=view&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition" title="Lihat Profil & Riwayat">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="wajib_pajak.php?action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit Data">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <form method="POST" action="wajib_pajak.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Wajib Pajak ini? Seluruh transaksi dan SPT terkait akan ikut terhapus!');" class="inline">
                                                    <input type="hidden" name="post_action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition" title="Hapus Data">
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

