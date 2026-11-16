<?php
// config/database.php - Koneksi Database MySQL & Helper Functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_perpajakan';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()) . "<br>Pastikan MySQL di Laragon sudah dinyalakan dan database <code>db_perpajakan</code> sudah dibuat.");
}

/**
 * Format angka ke mata uang Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggalIndo($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $hari = date('d', $time);
    $bulan = $bulanIndo[(int)date('m', $time)];
    $tahun = date('Y', $time);
    $jam = date('H:i', $time);
    return "{$hari} {$bulan} {$tahun}, {$jam} WIB";
}

/**
 * Flash notification helper
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate 15-digit Kode Billing unik
 */
function generateKodeBilling($pdo) {
    do {
        $billing = '';
        for ($i = 0; $i < 15; $i++) {
            $billing .= mt_rand(0, 9);
        }
        $stmt = $pdo->prepare("SELECT id FROM pembayaran_pajak WHERE kode_billing = ?");
        $stmt->execute([$billing]);
    } while ($stmt->fetch());

    return $billing;
}

/**
 * Generate 16-character NTPN unik
 */
function generateNTPN($pdo) {
    $chars = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    do {
        $ntpn = '';
        for ($i = 0; $i < 16; $i++) {
            $ntpn .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare("SELECT id FROM pembayaran_pajak WHERE ntpn = ?");
        $stmt->execute([$ntpn]);
    } while ($stmt->fetch());

    return $ntpn;
}

/**
 * Generate Nomor BPS SPT unik
 */
function generateNomorBPS($pdo, $kodePajak = 'SPT') {
    $year = date('Y');
    do {
        $rand = mt_rand(1000, 9999);
        $nomor = "BPS-{$year}-{$kodePajak}-{$rand}";
        $stmt = $pdo->prepare("SELECT id FROM spt WHERE nomor_spt = ?");
        $stmt->execute([$nomor]);
    } while ($stmt->fetch());

    return $nomor;
}

