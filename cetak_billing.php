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
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #F4F4F5 !important;
            padding: 2rem 1rem !important;
            color: #0A0A0A !important;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        .billing-doc-container {
            max-width: 760px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #0A0A0A;
            padding: 2.5rem;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: relative;
        }
        .action-bar {
            max-width: 760px;
            margin: 0 auto 1.5rem auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0A0A0A;
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .doc-title h1 {
            font-size: 1.35rem !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .doc-title p {
            font-size: 0.8rem;
            color: #52525B;
        }
        .status-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FAFAFA;
            border: 1px solid #E4E4E7;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        .section-header {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #52525B;
            border-bottom: 1px solid #E4E4E7;
            padding-bottom: 0.35rem;
            margin: 1.25rem 0 0.75rem 0;
        }
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }
        .doc-table td {
            padding: 0.5rem 0.25rem;
            border-bottom: 1px solid #F4F4F5;
            vertical-align: top;
        }
        .doc-table td.label-col {
            width: 180px;
            color: #52525B;
        }
        .doc-table td.val-col {
            color: #0A0A0A;
        }
        .total-highlight {
            background: #FAFAFA;
            border-top: 1px solid #0A0A0A;
            border-bottom: 1px solid #0A0A0A !important;
        }
        .total-highlight td {
            padding: 0.75rem 0.25rem !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
        }
        .doc-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid #E4E4E7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.75rem;
            color: #52525B;
        }
        .stamp-box {
            width: 90px;
            height: 90px;
            border: 2px solid #0A0A0A;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-radius: 4px;
            color: #0A0A0A;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
            }
            .billing-doc-container {
                border: 1px solid #0A0A0A !important;
                box-shadow: none !important;
                padding: 1.5rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar -->
    <div class="action-bar no-print">
        <a href="pembayaran.php" class="btn btn-secondary btn-sm">
            &larr; Kembali ke Modul Pembayaran
        </a>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                Cetak Dokumen / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Official Document Box -->
    <div class="billing-doc-container">
        
        <!-- Kop Dokumen Resmi -->
        <div class="doc-header">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div class="brand-mark" style="width: 44px; height: 44px; font-size: 1.1rem;">
                    P
                </div>
                <div>
                    <div style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #52525B;">
                        Kementerian Keuangan Republik Indonesia
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 600; color: #0A0A0A; text-transform: uppercase;">
                        Direktorat Jenderal Pajak
                    </div>
                    <div style="font-size: 0.75rem; color: #52525B;">
                        Kantor Pelayanan Pajak: <?= htmlspecialchars($trx['nama_kpp']) ?> [<?= $trx['kode_kpp'] ?>]
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: #0A0A0A; color: #FFFFFF; font-family: monospace; font-size: 0.75rem; padding: 0.35rem 0.7rem;">
                    e-Billing DJP
                </span>
                <div style="font-size: 0.7rem; color: #71717A; font-family: monospace; margin-top: 0.35rem;">Ref: SI-PAJAK-BPN</div>
            </div>
        </div>

        <!-- Judul Dokumen -->
        <div class="doc-title">
            <h1>Bukti Penerimaan Negara (BPN)</h1>
            <p>Surat Setoran Elektronik Berdasarkan Modul Penerimaan Negara Generasi Terpadu</p>
        </div>

        <!-- Status Tag -->
        <div class="status-box">
            <div>
                <span style="font-size: 0.72rem; color: #71717A; display: block;">Status Dokumen:</span>
                <?php if ($trx['status_pembayaran'] === 'Paid'): ?>
                    <span style="font-size: 0.9rem; font-weight: 600; color: #0A0A0A;">
                        [LUNAS] Transaksi Sah dan Terverifikasi
                    </span>
                <?php else: ?>
                    <span style="font-size: 0.9rem; font-weight: 600; color: #52525B;">
                        [PENDING] Menunggu Pembayaran Kas Negara
                    </span>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.72rem; color: #71717A; display: block;">Kode Billing:</span>
                <span style="font-family: monospace; font-size: 1.15rem; font-weight: 600; letter-spacing: 0.1em; color: #0A0A0A;">
                    <?= htmlspecialchars($trx['kode_billing']) ?>
                </span>
            </div>
        </div>

        <!-- Data Wajib Pajak -->
        <div class="section-header">A. Data Wajib Pajak</div>
        <table class="doc-table">
            <tbody>
                <tr>
                    <td class="label-col">NPWP (16 Digit):</td>
                    <td class="val-col" style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($trx['npwp']) ?></td>
                </tr>
                <?php if ($trx['nik']): ?>
                    <tr>
                        <td class="label-col">NIK:</td>
                        <td class="val-col" style="font-family: monospace;"><?= htmlspecialchars($trx['nik']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="label-col">Nama Wajib Pajak:</td>
                    <td class="val-col" style="font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($trx['nama_wp']) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Kategori Entitas:</td>
                    <td class="val-col"><?= htmlspecialchars($trx['jenis_wp']) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Alamat Terdaftar:</td>
                    <td class="val-col"><?= htmlspecialchars($trx['alamat']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Rincian Setoran -->
        <div class="section-header">B. Rincian Setoran Pajak</div>
        <table class="doc-table">
            <tbody>
                <tr>
                    <td class="label-col">Kode Akun Pajak:</td>
                    <td class="val-col" style="font-family: monospace; font-weight: 600;">
                        <?= htmlspecialchars($trx['kode_akun_pajak']) ?> - <?= htmlspecialchars($trx['nama_jenis_pajak']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="label-col">Tarif Standar:</td>
                    <td class="val-col" style="font-family: monospace;"><?= number_format($trx['tarif_standar'], 2) ?>%</td>
                </tr>
                <tr>
                    <td class="label-col">Dasar Pengenaan (DPP):</td>
                    <td class="val-col" style="font-family: monospace;"><?= formatRupiah($trx['dpp']) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Nomor Transaksi (NTPN):</td>
                    <td class="val-col" style="font-family: monospace; font-weight: 600; letter-spacing: 0.05em;">
                        <?= htmlspecialchars($trx['ntpn'] ?? 'MENUNGGU SETORAN KAS NEGARA') ?>
                    </td>
                </tr>
                <tr>
                    <td class="label-col">Bank Persepsi:</td>
                    <td class="val-col"><?= htmlspecialchars($trx['bank_persepsi'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Waktu Pembayaran:</td>
                    <td class="val-col" style="font-family: monospace;"><?= formatTanggalIndo($trx['tanggal_bayar']) ?></td>
                </tr>
                <tr class="total-highlight">
                    <td style="color: #0A0A0A; font-weight: 600;">Jumlah Pajak Disetor:</td>
                    <td style="font-family: monospace; font-size: 1.2rem !important; font-weight: 700; color: #0A0A0A;">
                        <?= formatRupiah($trx['jumlah_bayar']) ?>
                    </td>
                </tr>
                <tr>
                    <td class="label-col" style="font-style: italic;">Terbilang:</td>
                    <td class="val-col" style="font-style: italic; color: #52525B;">
                        # <?= strtoupper(terbilang($trx['jumlah_bayar'])) ?> RUPIAH #
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Catatan Legalitas -->
        <div class="doc-footer">
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #0A0A0A; margin-bottom: 0.2rem;">Ketentuan Resmi:</div>
                <div style="line-height: 1.5; color: #71717A;">
                    BPN ini diterbitkan melalui Sistem Basis Data Perpajakan SI-PAJAK terpadu dan sah sebagai Bukti Penerimaan Surat Setoran Pajak sesuai regulasi Direktorat Jenderal Pajak Republik Indonesia.
                </div>
                <div style="font-family: monospace; font-size: 0.7rem; color: #A1A1AA; margin-top: 0.35rem;">
                    Dicetak pada: <?= date('d/m/Y H:i:s') ?> WIB
                </div>
            </div>
            
            <div style="text-align: center;">
                <div class="stamp-box">
                    <span style="font-weight: 700; font-size: 1.05rem; letter-spacing: 0.05em;">DJP</span>
                    <span style="font-size: 0.55rem; letter-spacing: 0.05em; font-weight: 600;">TERVALIDASI</span>
                </div>
                <span style="font-size: 0.65rem; color: #71717A; font-family: monospace; display: block; margin-top: 0.25rem;">
                    Otentikasi Digital
                </span>
            </div>
        </div>

    </div>

</body>
</html>
