<?php
// pembayaran.php - CRUD Pembayaran Pajak & Billing Transaksi
require_once __DIR__ . '/config/database.php';

$page_title = 'Transaksi Pembayaran & Billing Pajak';
$active_nav = 'pembayaran';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preSelectedWP = isset($_GET['wp_id']) ? (int)$_GET['wp_id'] : 0;
$preSelectedSPT = isset($_GET['spt_id']) ? (int)$_GET['spt_id'] : 0;

// Ambil List WP, Jenis Pajak, SPT untuk form
$listWP = $pdo->query("SELECT id, npwp, nama_wp, jenis_wp FROM wajib_pajak ORDER BY nama_wp ASC")->fetchAll();
$listJenis = $pdo->query("SELECT id, kode_akun_pajak, nama_jenis_pajak, tarif_standar FROM jenis_pajak ORDER BY id ASC")->fetchAll();
$listSPT = $pdo->query("SELECT s.id, s.nomor_spt, s.tahun_pajak, s.nominal_pajak, w.nama_wp FROM spt s JOIN wajib_pajak w ON s.wajib_pajak_id = w.id ORDER BY s.id DESC")->fetchAll();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'create') {
        $kode_billing = trim($_POST['kode_billing'] ?? '');
        $wajib_pajak_id = (int)($_POST['wajib_pajak_id'] ?? 0);
        $jenis_pajak_id = (int)($_POST['jenis_pajak_id'] ?? 0);
        $spt_id = !empty($_POST['spt_id']) ? (int)$_POST['spt_id'] : null;
        $dpp = (float)str_replace(['.', ','], ['', '.'], $_POST['dpp'] ?? '0');
        $jumlah_bayar = (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah_bayar'] ?? '0');
        $status_pembayaran = $_POST['status_pembayaran'] ?? 'Unpaid';
        $bank_persepsi = !empty($_POST['bank_persepsi']) ? trim($_POST['bank_persepsi']) : null;
        $ntpn = !empty($_POST['ntpn']) ? trim($_POST['ntpn']) : null;
        $tanggal_bayar = ($status_pembayaran === 'Paid') ? (empty($_POST['tanggal_bayar']) ? date('Y-m-d H:i:s') : $_POST['tanggal_bayar']) : null;

        if (empty($kode_billing)) {
            $kode_billing = generateKodeBilling($pdo);
        }

        if (empty($wajib_pajak_id) || empty($jenis_pajak_id) || $jumlah_bayar <= 0) {
            setFlashMessage('danger', 'Mohon pilih Wajib Pajak, Jenis Pajak, dan masukkan nominal pajak yang valid!');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO pembayaran_pajak (kode_billing, ntpn, spt_id, wajib_pajak_id, jenis_pajak_id, dpp, jumlah_bayar, bank_persepsi, status_pembayaran, tanggal_bayar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$kode_billing, $ntpn, $spt_id, $wajib_pajak_id, $jenis_pajak_id, $dpp, $jumlah_bayar, $bank_persepsi, $status_pembayaran, $tanggal_bayar]);
                setFlashMessage('success', "Kode Billing '{$kode_billing}' berhasil diterbitkan!");
                header("Location: pembayaran.php");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal membuat billing: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'pay') {
        // Konfirmasi Pembayaran
        $bayar_id = (int)$_POST['id'];
        $bank_persepsi = trim($_POST['bank_persepsi'] ?? 'Bank Mandiri');
        $ntpn = trim($_POST['ntpn'] ?? '');
        if (empty($ntpn)) {
            $ntpn = generateNTPN($pdo);
        }
        $tanggal_bayar = !empty($_POST['tanggal_bayar']) ? $_POST['tanggal_bayar'] : date('Y-m-d H:i:s');

        try {
            $stmt = $pdo->prepare("
                UPDATE pembayaran_pajak
                SET ntpn = ?, bank_persepsi = ?, status_pembayaran = 'Paid', tanggal_bayar = ?
                WHERE id = ?
            ");
            $stmt->execute([$ntpn, $bank_persepsi, $tanggal_bayar, $bayar_id]);
            setFlashMessage('success', "Pembayaran berhasil dikonfirmasi! NTPN resmi telah diterbitkan: {$ntpn}");
            header("Location: pembayaran.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    } elseif ($postAction === 'update') {
        $edit_id = (int)$_POST['id'];
        $kode_billing = trim($_POST['kode_billing']);
        $wajib_pajak_id = (int)$_POST['wajib_pajak_id'];
        $jenis_pajak_id = (int)$_POST['jenis_pajak_id'];
        $spt_id = !empty($_POST['spt_id']) ? (int)$_POST['spt_id'] : null;
        $dpp = (float)$_POST['dpp'];
        $jumlah_bayar = (float)$_POST['jumlah_bayar'];
        $bank_persepsi = !empty($_POST['bank_persepsi']) ? trim($_POST['bank_persepsi']) : null;
        $status_pembayaran = $_POST['status_pembayaran'];
        $ntpn = !empty($_POST['ntpn']) ? trim($_POST['ntpn']) : null;
        $tanggal_bayar = ($status_pembayaran === 'Paid' && !empty($_POST['tanggal_bayar'])) ? $_POST['tanggal_bayar'] : null;

        try {
            $stmt = $pdo->prepare("
                UPDATE pembayaran_pajak
                SET kode_billing = ?, ntpn = ?, spt_id = ?, wajib_pajak_id = ?, jenis_pajak_id = ?, dpp = ?, jumlah_bayar = ?, bank_persepsi = ?, status_pembayaran = ?, tanggal_bayar = ?
                WHERE id = ?
            ");
            $stmt->execute([$kode_billing, $ntpn, $spt_id, $wajib_pajak_id, $jenis_pajak_id, $dpp, $jumlah_bayar, $bank_persepsi, $status_pembayaran, $tanggal_bayar, $edit_id]);
            setFlashMessage('success', "Transaksi Billing #{$kode_billing} berhasil diperbarui!");
            header("Location: pembayaran.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    } elseif ($postAction === 'delete') {
        $del_id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM pembayaran_pajak WHERE id = ?");
            $stmt->execute([$del_id]);
            setFlashMessage('success', 'Transaksi pembayaran berhasil dihapus!');
            header("Location: pembayaran.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
}

// Fetch Data for Edit or Pay
$editData = null;
$payData = null;
if (($action === 'edit' || $action === 'pay') && $id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, w.nama_wp, w.npwp, j.nama_jenis_pajak, j.tarif_standar
        FROM pembayaran_pajak p
        JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
        JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $resData = $stmt->fetch();
    if ($action === 'edit') $editData = $resData;
    if ($action === 'pay') $payData = $resData;
}

// Search & Filter Query
$search = trim($_GET['search'] ?? '');
$filterStatus = $_GET['filter_status'] ?? '';
$filterJenis = $_GET['filter_jenis'] ?? '';

$sql = "
    SELECT p.*, w.nama_wp, w.npwp, j.nama_jenis_pajak, j.kode_akun_pajak, s.nomor_spt
    FROM pembayaran_pajak p
    JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
    LEFT JOIN spt s ON p.spt_id = s.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.kode_billing LIKE ? OR p.ntpn LIKE ? OR w.nama_wp LIKE ? OR w.npwp LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}
if ($filterStatus !== '') {
    $sql .= " AND p.status_pembayaran = ?";
    $params[] = $filterStatus;
}
if ($filterJenis !== '') {
    $sql .= " AND p.jenis_pajak_id = ?";
    $params[] = $filterJenis;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listPembayaran = $stmt->fetchAll();

// Auto generate new billing code for form
$newBillingCode = generateKodeBilling($pdo);
$newNTPN = generateNTPN($pdo);

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
                <span class="text-slate-800 font-semibold">Transaksi & Billing</span>
            </div>
            <h1 class="text-2xl font-black text-tax-950 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-amber-500"></i> Transaksi Pembayaran Pajak & e-Billing
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">Mencatat kode billing, DPP, perhitungan tarif, dan validasi NTPN kas negara (Bab 2.3 & 4.6).</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($action === 'list'): ?>
                <a href="pembayaran.php?action=create" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md shadow-amber-500/20 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle"></i> Buat Kode Billing Baru
                </a>
            <?php else: ?>
                <a href="pembayaran.php" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold text-xs transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Billing
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL / FORM KONFIRMASI BAYAR (PAY) -->
    <?php if ($action === 'pay' && $payData): ?>
        <div class="bg-gradient-to-br from-tax-950 to-tax-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-tax-700 max-w-2xl mx-auto animate-fadeIn">
            <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-bold border border-emerald-500/30">
                        <i class="fa-solid fa-money-check-dollar"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-white">Konfirmasi Pembayaran Setoran Pajak</h2>
                        <p class="text-xs text-slate-300">Penatausahaan Penerimaan Kas Negara (NTPN)</p>
                    </div>
                </div>
            </div>

            <!-- Tax Summary Box -->
            <div class="bg-tax-900/80 rounded-2xl p-4 border border-white/10 space-y-3 mb-6">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400">Kode Billing:</span>
                    <span class="font-mono font-bold text-amber-400 text-sm"><?= htmlspecialchars($payData['kode_billing']) ?></span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400">Wajib Pajak:</span>
                    <span class="font-bold text-white"><?= htmlspecialchars($payData['nama_wp']) ?> (<?= htmlspecialchars($payData['npwp']) ?>)</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400">Jenis Pajak:</span>
                    <span class="text-slate-200"><?= htmlspecialchars($payData['nama_jenis_pajak']) ?></span>
                </div>
                <div class="border-t border-white/10 pt-2 flex justify-between items-center">
                    <span class="text-xs text-slate-300">Total Nominal Setor:</span>
                    <span class="text-xl font-mono font-extrabold text-emerald-400"><?= formatRupiah($payData['jumlah_bayar']) ?></span>
                </div>
            </div>

            <!-- Pay Form -->
            <form method="POST" action="pembayaran.php" class="space-y-4">
                <input type="hidden" name="post_action" value="pay">
                <input type="hidden" name="id" value="<?= $payData['id'] ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Bank Persepsi / Pos Indonesia *
                    </label>
                    <select name="bank_persepsi" required class="w-full bg-tax-950 border border-tax-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <option value="Bank Mandiri">Bank Mandiri (Persero) Tbk</option>
                        <option value="Bank BCA">Bank Central Asia (BCA)</option>
                        <option value="Bank BNI">Bank Negara Indonesia (BNI)</option>
                        <option value="Bank BRI">Bank Rakyat Indonesia (BRI)</option>
                        <option value="Bank BTN">Bank Tabungan Negara (BTN)</option>
                        <option value="PT Pos Indonesia">PT Pos Indonesia</option>
                        <option value="Bank Syariah Indonesia">Bank Syariah Indonesia (BSI)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Nomor Transaksi Penerimaan Negara (NTPN 16 Digit) *
                    </label>
                    <div class="relative">
                        <input type="text" name="ntpn" maxlength="16" required value="<?= $newNTPN ?>"
                               class="w-full bg-tax-950 border border-tax-700 rounded-xl px-4 py-2.5 text-sm font-mono font-bold text-emerald-400 tracking-widest focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>
                    <span class="text-[11px] text-slate-400">NTPN otomatis digenerate 16 digit huruf kapital dan angka acak.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wide mb-1">
                        Waktu Penatausahaan Setoran Masuk
                    </label>
                    <input type="datetime-local" name="tanggal_bayar" value="<?= date('Y-m-d\TH:i') ?>"
                           class="w-full bg-tax-950 border border-tax-700 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                    <a href="pembayaran.php" class="px-5 py-2.5 rounded-xl bg-tax-800 hover:bg-tax-700 text-slate-300 font-semibold text-xs transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-extrabold text-xs shadow-lg shadow-emerald-500/20 transition flex items-center gap-2">
                        <i class="fa-solid fa-check-circle"></i> Konfirmasi Bayar & Set LUNAS
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- FORM CREATE / EDIT BILLING -->
    <?php if ($action === 'create' || ($action === 'edit' && $editData)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fadeIn">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                <h2 class="text-lg font-black text-tax-950 flex items-center gap-2">
                    <i class="fa-solid <?= $action === 'create' ? 'fa-plus-circle text-amber-500' : 'fa-pen-to-square text-tax-600' ?>"></i>
                    <?= $action === 'create' ? 'Penerbitan Kode Billing e-Billing Baru' : 'Edit Transaksi Pembayaran Pajak' ?>
                </h2>
                <span class="text-xs text-slate-400">* Wajib diisi</span>
            </div>

            <form method="POST" action="pembayaran.php" class="space-y-6">
                <input type="hidden" name="post_action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Kode Billing -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Kode Billing (15 Digit Numerik) *
                        </label>
                        <input type="text" name="kode_billing" maxlength="15" required
                               value="<?= htmlspecialchars($action === 'edit' ? $editData['kode_billing'] : $newBillingCode) ?>"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-bold tracking-widest text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        <span class="text-[11px] text-slate-400">Generated otomatis sesuai standar sistem perpajakan (15 angka).</span>
                    </div>

                    <!-- Status Pembayaran -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Status Pembayaran *
                        </label>
                        <select name="status_pembayaran" id="status_pembayaran_select" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="Unpaid" <?= ($editData['status_pembayaran'] ?? 'Unpaid') === 'Unpaid' ? 'selected' : '' ?>>PENDING (Unpaid / Menunggu Setoran)</option>
                            <option value="Paid" <?= ($editData['status_pembayaran'] ?? '') === 'Paid' ? 'selected' : '' ?>>LUNAS (Paid / Kas Negara Terverifikasi)</option>
                            <option value="Expired" <?= ($editData['status_pembayaran'] ?? '') === 'Expired' ? 'selected' : '' ?>>KADALUARSA (Expired)</option>
                        </select>
                    </div>

                    <!-- Wajib Pajak Dropdown -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Pilih Wajib Pajak Pembayar *
                        </label>
                        <select name="wajib_pajak_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">-- Pilih Wajib Pajak --</option>
                            <?php foreach ($listWP as $wp): ?>
                                <option value="<?= $wp['id'] ?>" <?= (($editData['wajib_pajak_id'] ?? $preSelectedWP) == $wp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($wp['nama_wp']) ?> (<?= $wp['npwp'] ?>) - <?= $wp['jenis_wp'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Jenis Pajak Dropdown -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Pilih Objek / Jenis Pajak *
                        </label>
                        <select name="jenis_pajak_id" id="jenis_pajak_input" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none" onchange="hitungPajakForm()">
                            <option value="">-- Pilih Jenis Pajak --</option>
                            <?php foreach ($listJenis as $jp): ?>
                                <option value="<?= $jp['id'] ?>" data-tarif="<?= $jp['tarif_standar'] ?>" <?= (($editData['jenis_pajak_id'] ?? '') == $jp['id']) ? 'selected' : '' ?>>
                                    [<?= $jp['kode_akun_pajak'] ?>] <?= htmlspecialchars($jp['nama_jenis_pajak']) ?> (Tarif: <?= number_format($jp['tarif_standar'], 2) ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- DPP (Dasar Pengenaan Pajak) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Dasar Pengenaan Pajak (DPP) Rp *
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-2.5 text-sm font-bold text-slate-400">Rp</span>
                            <input type="number" name="dpp" id="form_dpp" step="1000" min="0" required
                                   value="<?= $editData['dpp'] ?? '100000000' ?>"
                                   class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-12 pr-4 py-2.5 text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                   oninput="hitungPajakForm()">
                        </div>
                        <span class="text-[11px] text-slate-400">Nilai transaksi dasar sebelum pengenaan persentase tarif pajak.</span>
                    </div>

                    <!-- Jumlah Bayar (Pajak Terutang) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Jumlah Pajak Terutang (Rp) *
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-2.5 text-sm font-bold text-slate-400">Rp</span>
                            <input type="number" name="jumlah_bayar" id="form_jumlah_bayar" step="1" min="1" required
                                   value="<?= $editData['jumlah_bayar'] ?? '11000000' ?>"
                                   class="w-full bg-amber-50/50 border border-amber-300 rounded-xl pl-12 pr-4 py-2.5 text-sm font-mono font-black text-amber-700 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <span class="text-[11px] text-amber-600 font-semibold" id="form_keterangan_hitung">Dihitung otomatis: DPP &times; Tarif Pajak</span>
                    </div>

                    <!-- Terkait SPT (Opsional) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Terkait Pelaporan SPT (Opsional)
                        </label>
                        <select name="spt_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="">-- Tidak Terkait SPT Tertentu --</option>
                            <?php foreach ($listSPT as $spt): ?>
                                <option value="<?= $spt['id'] ?>" <?= (($editData['spt_id'] ?? $preSelectedSPT) == $spt['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($spt['nomor_spt']) ?> - <?= htmlspecialchars($spt['nama_wp']) ?> (Thn <?= $spt['tahun_pajak'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- NTPN (Jika sudah Paid) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            NTPN (Nomor Transaksi Kas Negara)
                        </label>
                        <input type="text" name="ntpn" maxlength="16"
                               value="<?= htmlspecialchars($editData['ntpn'] ?? '') ?>"
                               placeholder="Otomatis atau input 16 karakter jika Lunas"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <!-- Bank Persepsi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Bank Persepsi Setoran
                        </label>
                        <input type="text" name="bank_persepsi"
                               value="<?= htmlspecialchars($editData['bank_persepsi'] ?? '') ?>"
                               placeholder="Contoh: Bank Mandiri, Bank BCA, Pos Indonesia"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <!-- Tanggal Bayar -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Waktu Setoran / Bayar (Jika Lunas)
                        </label>
                        <input type="datetime-local" name="tanggal_bayar"
                               value="<?= !empty($editData['tanggal_bayar']) ? date('Y-m-d\TH:i', strtotime($editData['tanggal_bayar'])) : '' ?>"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="pembayaran.php" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> <?= $action === 'create' ? 'Terbitkan Kode Billing' : 'Simpan Perubahan' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- SEARCH & FILTER TOOLBAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <form method="GET" action="pembayaran.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-sm"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Kode Billing, NTPN, atau Wajib Pajak..."
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="sm:col-span-3">
                <select name="filter_status" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">Semua Status Pembayaran</option>
                    <option value="Paid" <?= $filterStatus === 'Paid' ? 'selected' : '' ?>>LUNAS (Paid)</option>
                    <option value="Unpaid" <?= $filterStatus === 'Unpaid' ? 'selected' : '' ?>>PENDING (Unpaid)</option>
                    <option value="Expired" <?= $filterStatus === 'Expired' ? 'selected' : '' ?>>EXPIRED</option>
                </select>
            </div>

            <div class="sm:col-span-3">
                <select name="filter_jenis" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">Semua Jenis Pajak</option>
                    <?php foreach ($listJenis as $jp): ?>
                        <option value="<?= $jp['id'] ?>" <?= $filterJenis == $jp['id'] ? 'selected' : '' ?>>[<?= $jp['kode_akun_pajak'] ?>] <?= htmlspecialchars($jp['nama_jenis_pajak']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

                    <div class="sm:col-span-1 flex gap-1">
                        <button type="submit" class="w-full min-h-[44px] bg-tax-900 hover:bg-tax-800 text-white rounded-xl py-2 px-3 text-xs font-semibold transition flex items-center justify-center gap-1 focus:ring-2 focus:ring-amber-500" title="Terapkan Filter">
                            <i class="fa-solid fa-filter"></i>
                            <span class="sm:hidden text-xs">Filter</span>
                        </button>
                        <?php if ($search || $filterStatus || $filterJenis): ?>
                            <a href="pembayaran.php" class="min-h-[44px] px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs transition flex items-center justify-center" title="Reset Filter">
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
                        Total Transaksi: <strong class="text-tax-950 font-mono"><?= count($listPembayaran) ?></strong> Billing
                    </span>
                    <span class="text-xs text-slate-600 font-medium">Berdasarkan data penerimaan kas negara</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">ID</th>
                                <th class="py-3 px-4">Kode Billing & NTPN</th>
                                <th class="py-3 px-4">Wajib Pajak</th>
                                <th class="py-3 px-4">Jenis Pajak</th>
                                <th class="py-3 px-4 text-right">DPP (Rp)</th>
                                <th class="py-3 px-4 text-right">Jumlah Bayar (Rp)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($listPembayaran)): ?>
                                <tr>
                                    <td colspan="8" class="py-12 text-center">
                                        <div class="max-w-xs mx-auto space-y-2">
                                            <i class="fa-solid fa-receipt text-3xl text-slate-300"></i>
                                            <p class="text-xs font-bold text-slate-800">Tidak ada transaksi pembayaran ditemukan</p>
                                            <p class="text-xs text-slate-500">Coba atur ulang filter atau terbitkan kode billing perpajakan baru.</p>
                                            <div class="pt-2">
                                                <a href="pembayaran.php?action=create" class="inline-flex items-center min-h-[44px] px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs transition">
                                                    + Terbitkan Kode Billing Baru
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listPembayaran as $row): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3.5 px-4 font-mono font-bold text-slate-600"><?= $row['id'] ?></td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-mono font-bold text-tax-950"><?= htmlspecialchars($row['kode_billing']) ?></div>
                                            <?php if ($row['ntpn']): ?>
                                                <div class="text-[11px] font-mono text-emerald-700 font-bold">NTPN: <?= htmlspecialchars($row['ntpn']) ?></div>
                                                <div class="text-xs text-slate-600"><?= htmlspecialchars($row['bank_persepsi'] ?? 'Bank Persepsi') ?></div>
                                            <?php else: ?>
                                                <div class="text-xs font-mono text-slate-500">Belum disetor (Unpaid)</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($row['nama_wp']) ?></div>
                                            <div class="text-xs font-mono text-slate-600"><?= htmlspecialchars($row['npwp']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($row['nama_jenis_pajak']) ?></div>
                                            <div class="text-xs font-mono text-slate-500">KAP: <?= htmlspecialchars($row['kode_akun_pajak']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-mono text-slate-700 font-semibold">
                                            <?= formatRupiah($row['dpp']) ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-mono font-black text-tax-950">
                                            <?= formatRupiah($row['jumlah_bayar']) ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <?php if ($row['status_pembayaran'] === 'Paid'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    <i class="fa-solid fa-check mr-1"></i> LUNAS
                                                </span>
                                                <?php if ($row['tanggal_bayar']): ?>
                                                    <span class="block text-[10px] text-slate-500 mt-0.5"><?= date('d/m/y H:i', strtotime($row['tanggal_bayar'])) ?></span>
                                                <?php endif; ?>
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
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="cetak_billing.php?id=<?= $row['id'] ?>" target="_blank" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition" title="Cetak Bukti Penerimaan Negara (BPN)">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                                <?php if ($row['status_pembayaran'] === 'Unpaid'): ?>
                                                    <a href="pembayaran.php?action=pay&id=<?= $row['id'] ?>" class="min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition" title="Konfirmasi Bayar NTPN">
                                                        Bayar
                                                    </a>
                                                <?php endif; ?>
                                                <a href="pembayaran.php?action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit Transaksi">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <form method="POST" action="pembayaran.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data billing ini?');" class="inline">
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

<!-- Auto Calc Script in Form -->
<script>
    function hitungPajakForm() {
        const select = document.getElementById('jenis_pajak_input');
        if (!select) return;
        const selectedOption = select.options[select.selectedIndex];
        const tarif = parseFloat(selectedOption?.getAttribute('data-tarif')) || 0;
        const dpp = parseFloat(document.getElementById('form_dpp')?.value) || 0;

        const calculated = Math.round((dpp * tarif) / 100);
        const jumlahInput = document.getElementById('form_jumlah_bayar');
        if (jumlahInput && !jumlahInput.dataset.manualEdited) {
            jumlahInput.value = calculated;
        }

        const ket = document.getElementById('form_keterangan_hitung');
        if (ket) {
            ket.innerText = 'Dihitung otomatis: ' + dpp.toLocaleString('id-ID') + ' × ' + tarif + '% = ' + calculated.toLocaleString('id-ID');
        }
    }
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

