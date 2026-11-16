<?php
// kpp.php - Master Kantor Pelayanan Pajak (KPP) & Petugas Pajak (AR)
require_once __DIR__ . '/config/database.php';

$page_title = 'Master KPP & Petugas Pajak';
$active_nav = 'kpp';

$tab = $_GET['tab'] ?? 'kpp'; // 'kpp' or 'petugas'
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'] ?? 'kpp';
    $postAction = $_POST['post_action'] ?? '';

    if ($entity === 'kpp') {
        if ($postAction === 'create') {
            $kode_kpp = trim($_POST['kode_kpp'] ?? '');
            $nama_kpp = trim($_POST['nama_kpp'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            $no_telepon = trim($_POST['no_telepon'] ?? '');

            try {
                $stmt = $pdo->prepare("INSERT INTO kpp (kode_kpp, nama_kpp, alamat, no_telepon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_kpp, $nama_kpp, $alamat, $no_telepon]);
                setFlashMessage('success', "Kantor KPP '{$nama_kpp}' berhasil ditambahkan!");
                header("Location: kpp.php?tab=kpp");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menambah KPP: ' . $e->getMessage());
            }
        } elseif ($postAction === 'update') {
            $edit_id = (int)$_POST['id'];
            $kode_kpp = trim($_POST['kode_kpp']);
            $nama_kpp = trim($_POST['nama_kpp']);
            $alamat = trim($_POST['alamat']);
            $no_telepon = trim($_POST['no_telepon']);

            try {
                $stmt = $pdo->prepare("UPDATE kpp SET kode_kpp = ?, nama_kpp = ?, alamat = ?, no_telepon = ? WHERE id = ?");
                $stmt->execute([$kode_kpp, $nama_kpp, $alamat, $no_telepon, $edit_id]);
                setFlashMessage('success', "Data KPP berhasil diperbarui!");
                header("Location: kpp.php?tab=kpp");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal update KPP: ' . $e->getMessage());
            }
        } elseif ($postAction === 'delete') {
            $del_id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM kpp WHERE id = ?");
                $stmt->execute([$del_id]);
                setFlashMessage('success', 'Data KPP berhasil dihapus!');
                header("Location: kpp.php?tab=kpp");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Tidak dapat menghapus KPP karena masih memiliki Wajib Pajak atau Petugas terdaftar!');
            }
        }
    } elseif ($entity === 'petugas') {
        if ($postAction === 'create') {
            $nip = trim($_POST['nip'] ?? '');
            $kpp_id = (int)$_POST['kpp_id'];
            $nama_petugas = trim($_POST['nama_petugas'] ?? '');
            $jabatan = $_POST['jabatan'] ?? 'AR';
            $email = trim($_POST['email'] ?? '');
            $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

            try {
                $stmt = $pdo->prepare("INSERT INTO petugas_pajak (nip, kpp_id, nama_petugas, jabatan, email, status_aktif) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nip, $kpp_id, $nama_petugas, $jabatan, $email, $status_aktif]);
                setFlashMessage('success', "Petugas Pajak '{$nama_petugas}' berhasil didaftarkan!");
                header("Location: kpp.php?tab=petugas");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal mendaftarkan petugas: ' . $e->getMessage());
            }
        } elseif ($postAction === 'update') {
            $edit_id = (int)$_POST['id'];
            $nip = trim($_POST['nip']);
            $kpp_id = (int)$_POST['kpp_id'];
            $nama_petugas = trim($_POST['nama_petugas']);
            $jabatan = $_POST['jabatan'];
            $email = trim($_POST['email']);
            $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;

            try {
                $stmt = $pdo->prepare("UPDATE petugas_pajak SET nip = ?, kpp_id = ?, nama_petugas = ?, jabatan = ?, email = ?, status_aktif = ? WHERE id = ?");
                $stmt->execute([$nip, $kpp_id, $nama_petugas, $jabatan, $email, $status_aktif, $edit_id]);
                setFlashMessage('success', "Data petugas pajak berhasil diperbarui!");
                header("Location: kpp.php?tab=petugas");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal update petugas: ' . $e->getMessage());
            }
        } elseif ($postAction === 'delete') {
            $del_id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM petugas_pajak WHERE id = ?");
                $stmt->execute([$del_id]);
                setFlashMessage('success', 'Petugas pajak berhasil dihapus!');
                header("Location: kpp.php?tab=petugas");
                exit;
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Tidak dapat menghapus petugas: ' . $e->getMessage());
            }
        }
    }
}

// Fetch KPP list
$listKPP = $pdo->query("
    SELECT k.*,
           (SELECT COUNT(*) FROM wajib_pajak w WHERE w.kpp_id = k.id) as total_wp,
           (SELECT COUNT(*) FROM petugas_pajak p WHERE p.kpp_id = k.id) as total_petugas
    FROM kpp k
    ORDER BY k.id ASC
")->fetchAll();

// Fetch Petugas list
$listPetugas = $pdo->query("
    SELECT p.*, k.kode_kpp, k.nama_kpp,
           (SELECT COUNT(*) FROM wajib_pajak w WHERE w.petugas_ar_id = p.id) as total_wp_diampu
    FROM petugas_pajak p
    JOIN kpp k ON p.kpp_id = k.id
    ORDER BY p.id ASC
")->fetchAll();

// Fetch edit data if requested
$editKPP = null;
$editPetugas = null;
if ($action === 'edit' && $id > 0) {
    if ($tab === 'kpp') {
        $stmt = $pdo->prepare("SELECT * FROM kpp WHERE id = ?");
        $stmt->execute([$id]);
        $editKPP = $stmt->fetch();
    } elseif ($tab === 'petugas') {
        $stmt = $pdo->prepare("SELECT * FROM petugas_pajak WHERE id = ?");
        $stmt->execute([$id]);
        $editPetugas = $stmt->fetch();
    }
}

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
                <span class="text-slate-800 font-semibold">Master Kelembagaan</span>
            </div>
            <h1 class="text-2xl font-black text-tax-950 flex items-center gap-2">
                <i class="fa-solid fa-building-columns text-tax-600"></i> Kantor KPP & Petugas Pajak (AR)
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">Struktur unit kantor pelayanan dan penugasan Account Representative (Bab 4.1 & 4.2).</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($tab === 'kpp'): ?>
                <a href="kpp.php?tab=kpp&action=create" class="px-4 py-2.5 rounded-xl bg-tax-900 hover:bg-tax-800 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle"></i> Tambah KPP
                </a>
            <?php else: ?>
                <a href="kpp.php?tab=petugas&action=create" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Tambah Petugas AR
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6">
            <a href="kpp.php?tab=kpp" class="py-3 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2 <?= $tab === 'kpp' ? 'border-amber-500 text-tax-950' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                <i class="fa-solid fa-building"></i> Kantor KPP (<?= count($listKPP) ?>)
            </a>
            <a href="kpp.php?tab=petugas" class="py-3 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2 <?= $tab === 'petugas' ? 'border-amber-500 text-tax-950' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                <i class="fa-solid fa-user-tie"></i> Petugas Pajak / AR (<?= count($listPetugas) ?>)
            </a>
        </nav>
    </div>

    <!-- TAB 1: KANTOR KPP -->
    <?php if ($tab === 'kpp'): ?>

        <!-- FORM CREATE / EDIT KPP -->
        <?php if ($action === 'create' || ($action === 'edit' && $editKPP)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fadeIn max-w-2xl mx-auto">
                <h2 class="text-lg font-black text-tax-950 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid <?= $action === 'create' ? 'fa-plus-circle text-amber-500' : 'fa-pen-to-square text-tax-600' ?>"></i>
                    <?= $action === 'create' ? 'Tambah Kantor Pelayanan Pajak (KPP)' : 'Edit Data KPP' ?>
                </h2>

                <form method="POST" action="kpp.php?tab=kpp" class="space-y-4">
                    <input type="hidden" name="entity" value="kpp">
                    <input type="hidden" name="post_action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $editKPP['id'] ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Kode KPP (3 Digit) *
                            </label>
                            <input type="text" name="kode_kpp" required maxlength="3"
                                   value="<?= htmlspecialchars($editKPP['kode_kpp'] ?? '') ?>"
                                   placeholder="012"
                                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Nama Kantor KPP *
                            </label>
                            <input type="text" name="nama_kpp" required maxlength="100"
                                   value="<?= htmlspecialchars($editKPP['nama_kpp'] ?? '') ?>"
                                   placeholder="KPP Pratama Jakarta Gambir Satu"
                                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Nomor Telepon Resmi
                        </label>
                        <input type="text" name="no_telepon" maxlength="15"
                               value="<?= htmlspecialchars($editKPP['no_telepon'] ?? '') ?>"
                               placeholder="021-3841234"
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Alamat Fisik Kantor
                        </label>
                        <textarea name="alamat" rows="2" placeholder="Jl. Ridwan Rais No. 5-7, Gambir, Jakarta Pusat"
                                  class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"><?= htmlspecialchars($editKPP['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="kpp.php?tab=kpp" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">Batal</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-tax-900 hover:bg-tax-800 text-white font-bold text-xs shadow-md transition">
                            <i class="fa-solid fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- TABLE KPP -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100/80 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Nama Kantor KPP</th>
                            <th class="py-3 px-4">Alamat & Kontak</th>
                            <th class="py-3 px-4 text-center">Wajib Pajak</th>
                            <th class="py-3 px-4 text-center">Petugas AR</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($listKPP as $row): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-tax-950 text-sm">[<?= htmlspecialchars($row['kode_kpp']) ?>]</td>
                                <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($row['nama_kpp']) ?></td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div><?= htmlspecialchars($row['alamat'] ?? '-') ?></div>
                                    <div class="text-[10px] text-slate-400"><i class="fa-solid fa-phone mr-1"></i> <?= htmlspecialchars($row['no_telepon'] ?? '-') ?></div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200"><?= $row['total_wp'] ?> WP</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200"><?= $row['total_petugas'] ?> Petugas</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="kpp.php?tab=kpp&action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form method="POST" action="kpp.php?tab=kpp" onsubmit="return confirm('Hapus KPP ini?');" class="inline">
                                            <input type="hidden" name="entity" value="kpp">
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

    <!-- TAB 2: PETUGAS PAJAK -->
    <?php else: ?>

        <!-- FORM CREATE / EDIT PETUGAS -->
        <?php if ($action === 'create' || ($action === 'edit' && $editPetugas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fadeIn max-w-2xl mx-auto">
                <h2 class="text-lg font-black text-tax-950 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fa-solid <?= $action === 'create' ? 'fa-user-plus text-amber-500' : 'fa-pen-to-square text-tax-600' ?>"></i>
                    <?= $action === 'create' ? 'Pendaftaran Petugas Pajak / AR' : 'Edit Petugas Pajak' ?>
                </h2>

                <form method="POST" action="kpp.php?tab=petugas" class="space-y-4">
                    <input type="hidden" name="entity" value="petugas">
                    <input type="hidden" name="post_action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $editPetugas['id'] ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                NIP (18 Digit) *
                            </label>
                            <input type="text" name="nip" required maxlength="18"
                                   value="<?= htmlspecialchars($editPetugas['nip'] ?? '') ?>"
                                   placeholder="198504122008011001"
                                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Penugasan Unit KPP *
                            </label>
                            <select name="kpp_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <?php foreach ($listKPP as $kpp): ?>
                                    <option value="<?= $kpp['id'] ?>" <?= (($editPetugas['kpp_id'] ?? '') == $kpp['id']) ? 'selected' : '' ?>>
                                        [<?= $kpp['kode_kpp'] ?>] <?= htmlspecialchars($kpp['nama_kpp']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Nama Lengkap Petugas & Gelar *
                        </label>
                        <input type="text" name="nama_petugas" required maxlength="100"
                               value="<?= htmlspecialchars($editPetugas['nama_petugas'] ?? '') ?>"
                               placeholder="Hendro Prasetyo, S.E., M.Ak."
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Jabatan Fungsional *
                            </label>
                            <select name="jabatan" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <?php foreach (['AR', 'Pemeriksa', 'Penelaah', 'Admin'] as $jab): ?>
                                    <option value="<?= $jab ?>" <?= ($editPetugas['jabatan'] ?? 'AR') === $jab ? 'selected' : '' ?>><?= $jab ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                                Email Kedinasan *
                            </label>
                            <input type="email" name="email" required maxlength="100"
                                   value="<?= htmlspecialchars($editPetugas['email'] ?? '') ?>"
                                   placeholder="petugas@pajak.go.id"
                                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="status_aktif" value="1" <?= (!empty($editPetugas['status_aktif']) || !isset($editPetugas)) ? 'checked' : '' ?> class="rounded text-amber-600 focus:ring-amber-500">
                            <span>Status Petugas Aktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="kpp.php?tab=petugas" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">Batal</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition">
                            <i class="fa-solid fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- TABLE PETUGAS -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100/80 text-slate-700 uppercase text-[10px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">NIP</th>
                            <th class="py-3 px-4">Nama Petugas</th>
                            <th class="py-3 px-4">Jabatan</th>
                            <th class="py-3 px-4">KPP Penugasan</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4 text-center">WP Diampu</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($listPetugas as $row): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-tax-950"><?= htmlspecialchars($row['nip']) ?></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($row['nama_petugas']) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $row['jabatan'] === 'AR' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= $row['jabatan'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-800">[<?= $row['kode_kpp'] ?>]</span>
                                    <span class="text-[11px] text-slate-500 block"><?= htmlspecialchars($row['nama_kpp']) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-mono text-[11px]"><?= htmlspecialchars($row['email']) ?></td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-tax-900">
                                    <?= $row['total_wp_diampu'] ?> WP
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="kpp.php?tab=petugas&action=edit&id=<?= $row['id'] ?>" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 rounded-lg bg-tax-100 hover:bg-tax-200 text-tax-900 transition" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form method="POST" action="kpp.php?tab=petugas" onsubmit="return confirm('Hapus petugas ini?');" class="inline">
                                            <input type="hidden" name="entity" value="petugas">
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

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

