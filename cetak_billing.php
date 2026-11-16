<?php
// cetak_billing.php - Bukti Penerimaan Negara (BPN) / Cetak Surat Setoran Pajak
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("ID Transaksi tidak valid.");
}

$stmt = $pdo->prepare("
    SELECT p.*, w.nama_wp, w.npwp, w.nik, w.alamat, w.jenis_wp,
           j.nama_jenis_pajak, j.kode_akun_pajak, j.tarif_standar,
           k.kode_kpp, k.nama_kpp,
           s.nomor_spt, s.jenis_spt, s.tahun_pajak, s.masa_pajak
    FROM pembayaran_pajak p
    JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
    JOIN kpp k ON w.kpp_id = k.id
    LEFT JOIN spt s ON p.spt_id = s.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Data transaksi perpajakan tidak ditemukan.");
}

// Fungsi Terbilang Rupiah Sederhana
function terbilang($nilai) {
    $nilai = abs((int)$nilai);
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = terbilang($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = terbilang((int)($nilai / 10)) . " Puluh" . terbilang($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . terbilang($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = terbilang((int)($nilai / 100)) . " Ratus" . terbilang($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . terbilang($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = terbilang((int)($nilai / 1000)) . " Ribu" . terbilang($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = terbilang((int)($nilai / 1000000)) . " Juta" . terbilang($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = terbilang((int)($nilai / 1000000000)) . " Milyar" . terbilang(fmod($nilai, 1000000000));
    }
    return trim($temp);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPN - <?= htmlspecialchars($trx['kode_billing']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
                color: black;
                font-size: 11pt;
            }
            .print-border {
                border: 2px solid #000 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-800">

    <!-- Action Bar -->
    <div class="max-w-3xl mx-auto mb-6 flex flex-wrap justify-between items-center gap-3 no-print">
        <a href="pembayaran.php" class="inline-flex items-center min-h-[44px] gap-2 text-xs font-bold text-slate-700 hover:text-slate-900 bg-white px-4 py-2.5 rounded-xl shadow-sm border border-slate-300">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Modul Pembayaran
        </a>
        <div class="flex gap-2">
            <button onclick="window.print()" class="inline-flex items-center min-h-[44px] gap-2 text-xs font-bold text-tax-950 bg-amber-400 hover:bg-amber-500 px-5 py-2.5 rounded-xl shadow-md transition">
                <i class="fa-solid fa-print"></i> Cetak Dokumen / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Official Document Box -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-300 p-8 sm:p-12 relative overflow-hidden print-border">
        
        <!-- Watermark LUNAS jika status Paid -->
        <?php if ($trx['status_pembayaran'] === 'Paid'): ?>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.06] select-none rotate-[-25deg]">
                <span class="text-9xl font-black text-emerald-800 tracking-widest border-8 border-emerald-800 px-12 py-4 rounded-3xl">
                    LUNAS
                </span>
            </div>
        <?php endif; ?>

        <!-- Kop Dokumen Resmi -->
        <div class="flex items-center justify-between border-b-2 border-slate-800 pb-5 mb-6">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-slate-900 text-amber-400 rounded-xl flex items-center justify-center text-3xl font-bold shadow-md">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold tracking-wider uppercase text-slate-600">Kementerian Keuangan Republik Indonesia</h3>
                    <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight">Direktorat Jenderal Pajak</h2>
                    <p class="text-xs text-slate-500">Kantor Pelayanan Pajak: <?= htmlspecialchars($trx['nama_kpp']) ?> [<?= $trx['kode_kpp'] ?>]</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-amber-400 font-mono font-bold text-xs rounded tracking-wider uppercase">
                    e-Billing DJP
                </span>
                <p class="text-[10px] text-slate-400 font-mono mt-1">Ref: SI-PAJAK-BPN</p>
            </div>
        </div>

        <!-- Judul Surat -->
        <div class="text-center mb-6">
            <h1 class="text-xl font-black text-slate-900 tracking-wide uppercase">
                Bukti Penerimaan Negara (BPN)
            </h1>
            <p class="text-xs text-slate-500">Surat Setoran Elektronik Berdasarkan Modul Penerimaan Negara Generasi Terpadu</p>
        </div>

        <!-- Status Tag -->
        <div class="flex justify-between items-center bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6">
            <div>
                <span class="text-xs text-slate-500 block">Status Dokumen:</span>
                <?php if ($trx['status_pembayaran'] === 'Paid'): ?>
                    <span class="text-sm font-extrabold text-emerald-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check"></i> TRANSAKSI SAH & DINYATAKAN LUNAS
                    </span>
                <?php else: ?>
                    <span class="text-sm font-extrabold text-amber-600 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock"></i> MENUNGGU PEMBAYARAN (UNPAID)
                    </span>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-500 block">Kode Billing:</span>
                <span class="font-mono text-lg font-black text-slate-900 tracking-widest"><?= htmlspecialchars($trx['kode_billing']) ?></span>
            </div>
        </div>

        <!-- Grid Data Wajib Pajak -->
        <div class="mb-6 space-y-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-200 pb-1">
                A. Data Wajib Pajak
            </h3>
            <table class="w-full text-xs">
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500 w-44">NPWP (16 Digit):</td>
                        <td class="py-1.5 font-mono font-bold text-slate-900"><?= htmlspecialchars($trx['npwp']) ?></td>
                    </tr>
                    <?php if ($trx['nik']): ?>
                        <tr class="border-b border-slate-100">
                            <td class="py-1.5 text-slate-500">NIK:</td>
                            <td class="py-1.5 font-mono text-slate-800"><?= htmlspecialchars($trx['nik']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Nama Wajib Pajak:</td>
                        <td class="py-1.5 font-bold text-slate-900 uppercase"><?= htmlspecialchars($trx['nama_wp']) ?></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Kategori Entitas:</td>
                        <td class="py-1.5 text-slate-800"><?= htmlspecialchars($trx['jenis_wp']) ?></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Alamat Terdaftar:</td>
                        <td class="py-1.5 text-slate-700"><?= htmlspecialchars($trx['alamat']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Grid Rincian Pembayaran -->
        <div class="mb-6 space-y-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-200 pb-1">
                B. Rincian Setoran Pajak
            </h3>
            <table class="w-full text-xs">
                <tbody>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500 w-44">Kode Akun Pajak (KAP):</td>
                        <td class="py-1.5 font-mono font-bold text-slate-900"><?= htmlspecialchars($trx['kode_akun_pajak']) ?> - <?= htmlspecialchars($trx['nama_jenis_pajak']) ?></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Tarif Standar:</td>
                        <td class="py-1.5 font-mono text-slate-800"><?= number_format($trx['tarif_standar'], 2) ?>%</td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Dasar Pengenaan Pajak (DPP):</td>
                        <td class="py-1.5 font-mono font-semibold text-slate-800"><?= formatRupiah($trx['dpp']) ?></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Nomor Transaksi Negara (NTPN):</td>
                        <td class="py-1.5 font-mono font-black text-emerald-700 text-sm tracking-wider">
                            <?= htmlspecialchars($trx['ntpn'] ?? 'MENUNGGU SETORAN KAS NEGARA') ?>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Bank Persepsi:</td>
                        <td class="py-1.5 font-semibold text-slate-800"><?= htmlspecialchars($trx['bank_persepsi'] ?? '-') ?></td>
                    </tr>
                    <tr class="border-b border-slate-100">
                        <td class="py-1.5 text-slate-500">Waktu Pembayaran:</td>
                        <td class="py-1.5 font-mono text-slate-800"><?= formatTanggalIndo($trx['tanggal_bayar']) ?></td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td class="py-2.5 px-2 text-slate-900 font-bold text-sm">Jumlah Pajak Disetor:</td>
                        <td class="py-2.5 px-2 font-mono font-black text-xl text-slate-950"><?= formatRupiah($trx['jumlah_bayar']) ?></td>
                    </tr>
                    <tr class="bg-slate-50/50">
                        <td class="py-2 px-2 text-slate-500 italic">Terbilang:</td>
                        <td class="py-2 px-2 font-semibold text-slate-800 italic"># <?= strtoupper(terbilang($trx['jumlah_bayar'])) ?> RUPIAH #</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Catatan Legalitas & Verifikasi QR -->
        <div class="mt-8 pt-6 border-t border-slate-300 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-600">
            <div class="space-y-1 text-center sm:text-left">
                <p class="font-bold text-slate-900">Ketentuan Resmi:</p>
                <p class="text-[11px] text-slate-500 max-w-md">
                    BPN ini dicetak melalui Sistem Basis Data Perpajakan SI-PAJAK terhubung dan diakui sebagai Bukti Penerimaan Surat Setoran Pajak yang sah sesuai regulasi Direktorat Jenderal Pajak.
                </p>
                <p class="text-[10px] text-slate-400 font-mono">Dicetak pada: <?= date('d/m/Y H:i:s') ?> WIB</p>
            </div>
            
            <div class="text-center flex-shrink-0">
                <div class="w-24 h-24 bg-slate-100 border-2 border-dashed border-slate-400 rounded-xl flex flex-col items-center justify-center p-1 text-[9px] text-slate-500">
                    <i class="fa-solid fa-qrcode text-3xl text-slate-800 mb-1"></i>
                    <span>VALID DJP</span>
                </div>
                <span class="text-[9px] text-slate-400 font-mono mt-1 block">Otentikasi Digital</span>
            </div>
        </div>

    </div>

</body>
</html>

