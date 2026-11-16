<?php
// jenis_pajak.php - Master Data Jenis Pajak & Tarif
require_once __DIR__ . '/config/database.php';

$page_title = 'Master Jenis Pajak & Tarif Standar';
$active_nav = 'jenis_pajak';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'create') {
        $kode_akun_pajak = trim($_POST['kode_akun_pajak'] ?? '');
        $nama_jenis_pajak = trim($_POST['nama_jenis_pajak'] ?? '');
        $kategori_pajak = $_POST['kategori_pajak'] ?? 'PPh';
        $tarif_standar = (float)$_POST['tarif_standar'];

        if (empty($kode_akun_pajak) || empty($nama_jenis_pajak)) {
            setFlashMessage('danger', 'Kode KAP dan Nama Jenis Pajak tidak boleh kosong!');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO jenis_pajak (kode_akun_pajak, nama_jenis_pajak, kategori_pajak, tarif_standar) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_akun_pajak, $nama_jenis_pajak, $kategori_pajak, $tarif_standar]);
                setFlashMessage('success', "Jenis Pajak '{$nama_jenis_pajak}' berhasil ditambahkan!");
                header("Location: jenis_pajak.php");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menambahkan jenis pajak: ' . $e->getMessage());
            }
        }
    } elseif ($postAction === 'update') {
        $edit_id = (int)$_POST['id'];
        $kode_akun_pajak = trim($_POST['kode_akun_pajak']);
        $nama_jenis_pajak = trim($_POST['nama_jenis_pajak']);
        $kategori_pajak = $_POST['kategori_pajak'];
        $tarif_standar = (float)$_POST['tarif_standar'];

        try {
            $stmt = $pdo->prepare("UPDATE jenis_pajak SET kode_akun_pajak = ?, nama_jenis_pajak = ?, kategori_pajak = ?, tarif_standar = ? WHERE id = ?");
            $stmt->execute([$kode_akun_pajak, $nama_jenis_pajak, $kategori_pajak, $tarif_standar, $edit_id]);
            setFlashMessage('success', "Jenis Pajak berhasil diperbarui!");
            header("Location: jenis_pajak.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Gagal memperbarui: ' . $e->getMessage());
        }
    } elseif ($postAction === 'delete') {
        $del_id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM jenis_pajak WHERE id = ?");
            $stmt->execute([$del_id]);
            setFlashMessage('success', 'Jenis Pajak berhasil dihapus!');
            header("Location: jenis_pajak.php");
            exit;
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Tidak dapat menghapus jenis pajak karena masih digunakan pada transaksi atau SPT!');
        }
    }
}

// Fetch edit data
$editData = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM jenis_pajak WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
}

// Fetch all
$listJenis = $pdo->query("
    SELECT j.*,
           (SELECT COUNT(*) FROM pembayaran_pajak p WHERE p.jenis_pajak_id = j.id) as total_trx,
           (SELECT COUNT(*) FROM spt s WHERE s.jenis_pajak_id = j.id) as total_spt
    FROM jenis_pajak j
    ORDER BY j.id ASC
")->fetchAll();

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
                <span class="text-slate-800 font-semibold">Master Data</span>
            </div>
            <h1 class="text-2xl font-black text-tax-950 flex items-center gap-2">
                <i class="fa-solid fa-percent text-amber-500"></i> Master Tarif & Objek Pajak
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">Mencatat Kode Akun Pajak (KAP 6 digit), kategori, dan tarif standar persentase (Bab 2.2 & 4.4).</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($action === 'list'): ?>
                <a href="jenis_pajak.php?action=create" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle"></i> Tambah Jenis Pajak
                </a>
            <?php else: ?>
                <a href="jenis_pajak.php" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold text-xs transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- FORM CREATE / EDIT -->
    <?php if ($action === 'create' || ($action === 'edit' && $editData)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fadeIn max-w-2xl mx-auto">
            <h2 class="text-lg font-black text-tax-950 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid <?= $action === 'create' ? 'fa-plus-circle text-amber-500' : 'fa-pen-to-square text-tax-600' ?>"></i>
                <?= $action === 'create' ? 'Tambah Jenis Pajak Baru' : 'Edit Jenis Pajak' ?>
            </h2>

            <form method="POST" action="jenis_pajak.php" class="space-y-4">
                <input type="hidden" name="post_action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                        Kode Akun Pajak (KAP / MAP 6 Digit) *
                    </label>
                    <input type="text" name="kode_akun_pajak" required maxlength="6"
                           value="<?= htmlspecialchars($editData['kode_akun_pajak'] ?? '') ?>"
                           placeholder="Contoh: 411211 atau 411121"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                        Nama Jenis Pajak *
                    </label>
                    <input type="text" name="nama_jenis_pajak" required maxlength="100"
                           value="<?= htmlspecialchars($editData['nama_jenis_pajak'] ?? '') ?>"
                           placeholder="Contoh: Pajak Pertambahan Nilai (PPN)"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Kategori Pajak *
                        </label>
                        <select name="kategori_pajak" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <?php foreach (['PPh', 'PPN', 'PPnBM', 'PBB', 'Bea Materai'] as $kat): ?>
                                <option value="<?= $kat ?>" <?= ($editData['kategori_pajak'] ?? 'PPh') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Tarif Standar (%) *
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" max="100" name="tarif_standar" required
                                   value="<?= $editData['tarif_standar'] ?? '11.00' ?>"
                                   class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-4 pr-8 py-2.5 text-sm font-mono font-bold text-amber-700 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <span class="absolute right-3.5 top-2.5 text-sm font-bold text-slate-400">%</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="jenis_pajak.php" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">Batal</a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition">
                        <i class="fa-solid fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- DATA TABLE -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Daftar Jenis Objek Pajak: <strong class="text-tax-950 font-mono"><?= count($listJenis) ?></strong> Jenis
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100/80 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Kode KAP</th>
                        <th class="py-3 px-4">Nama Jenis Pajak</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-right">Tarif Standar</th>
                        <th class="py-3 px-4 text-center">Penggunaan</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($listJenis as $row): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600"><?= $row['id'] ?></td>
                            <td class="py-3.5 px-4 font-mono font-bold text-tax-950 text-sm"><?= htmlspecialchars($row['kode_akun_pajak']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($row['nama_jenis_pajak']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                    <?= $row['kategori_pajak'] ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-amber-700 text-sm">
                                <?= number_format($row['tarif_standar'], 2) ?>%
                            </td>
                            <td class="py-3.5 px-4 text-center text-xs text-slate-600">
                                <span class="font-semibold text-slate-800"><?= $row['total_trx'] ?> Trx</span> &bull; <span><?= $row['total_spt'] ?> SPT</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="jenis_pajak.php?action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST" action="jenis_pajak.php" onsubmit="return confirm('Hapus jenis pajak ini?');" class="inline">
                                        <input type="hidden" name="post_action" value="delete">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

