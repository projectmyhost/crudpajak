<?php
// dashboard.php - Dashboard CRUD Perpajakan
require_once __DIR__ . '/config/database.php';

$page_title = 'Dashboard Operasional Perpajakan';
$active_nav = 'dashboard';

// Ambil Statistik Ringkasan Nyata dari Basis Data
$countWP = (int)$pdo->query("SELECT COUNT(*) FROM wajib_pajak")->fetchColumn();
$countWPBadan = (int)$pdo->query("SELECT COUNT(*) FROM wajib_pajak WHERE jenis_wp = 'Badan'")->fetchColumn();
$countWPOP = (int)$pdo->query("SELECT COUNT(*) FROM wajib_pajak WHERE jenis_wp = 'Orang Pribadi'")->fetchColumn();

$countSPT = (int)$pdo->query("SELECT COUNT(*) FROM spt")->fetchColumn();
$sumPaid = (float)$pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_pajak WHERE status_pembayaran = 'Paid'")->fetchColumn();
$sumUnpaid = (float)$pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_pajak WHERE status_pembayaran = 'Unpaid'")->fetchColumn();

$countBillingPaid = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran_pajak WHERE status_pembayaran = 'Paid'")->fetchColumn();
$countBillingUnpaid = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran_pajak WHERE status_pembayaran = 'Unpaid'")->fetchColumn();

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

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
      Ringkasan Operasional
    </span>
    <h1 style="margin-top: 0.25rem; margin-bottom: 0.35rem;">Dashboard Administrasi Pajak</h1>
    <p>Pencatatan penerimaan kas negara, data kepatuhan Wajib Pajak, dan transaksi e-Billing.</p>
  </div>

  <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
    <a href="pembayaran.php?action=create" class="btn btn-primary">+ Buat Billing</a>
    <a href="wajib_pajak.php?action=create" class="btn btn-secondary">+ Wajib Pajak</a>
    <a href="spt.php?action=create" class="btn btn-subtle">+ Lapor SPT</a>
  </div>
</div>

<!-- 4 KPI Metrics Grid (Matching Perpustakaan) -->
<div class="stats-grid">
  <div class="stat-box">
    <div class="stat-label">Penerimaan Realisasi</div>
    <div class="stat-number"><?= formatRupiah($sumPaid) ?></div>
    <div class="stat-desc"><?= $countBillingPaid ?> transaksi lunas tervalidasi NTPN</div>
  </div>

  <div class="stat-box">
    <div class="stat-label">Billing Pending</div>
    <div class="stat-number"><?= formatRupiah($sumUnpaid) ?></div>
    <div class="stat-desc"><?= $countBillingUnpaid ?> kode billing menunggu setoran</div>
  </div>

  <div class="stat-box">
    <div class="stat-label">Total Wajib Pajak</div>
    <div class="stat-number"><?= $countWP ?> WP</div>
    <div class="stat-desc">Badan: <?= $countWPBadan ?> &bull; Orang Pribadi: <?= $countWPOP ?></div>
  </div>

  <div class="stat-box">
    <div class="stat-label">Pelaporan SPT</div>
    <div class="stat-number"><?= $countSPT ?> Laporan</div>
    <div class="stat-desc">SPT Elektronik BPS terdata resmi</div>
  </div>
</div>

<!-- Section: Transaksi Pembayaran Pajak Terbaru -->
<div class="card" style="margin-bottom: 2rem;">
  <div class="card-header">
    <div>
      <h2 class="card-title">Transaksi Pembayaran Pajak Terbaru</h2>
      <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 0.2rem;">
        Daftar kode billing yang diterbitkan beserta status verifikasi kas negara.
      </p>
    </div>
    <a href="pembayaran.php" class="btn btn-subtle btn-sm">Buka Seluruh Transaksi</a>
  </div>

  <div class="table-responsive" style="border: none;">
    <table class="table-clean">
      <thead>
        <tr>
          <th>Kode Billing</th>
          <th>Wajib Pajak</th>
          <th>Jenis Pajak</th>
          <th style="text-align: right;">Jumlah Pajak</th>
          <th style="text-align: center;">Status</th>
          <th style="text-align: right; width: 170px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transaksiList)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
              Belum ada transaksi pembayaran pajak. Silakan terbitkan kode billing baru.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($transaksiList as $row): ?>
            <tr>
              <td>
                <div style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($row['kode_billing']) ?></div>
                <?php if ($row['ntpn']): ?>
                  <div style="font-size: 0.72rem; font-family: monospace; color: var(--text-light);">NTPN: <?= htmlspecialchars($row['ntpn']) ?></div>
                <?php else: ?>
                  <div style="font-size: 0.72rem; color: var(--text-light);">Menunggu setoran</div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 600;"><?= htmlspecialchars($row['nama_wp']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-light); font-family: monospace;"><?= htmlspecialchars($row['npwp']) ?></div>
              </td>
              <td>
                <div><?= htmlspecialchars($row['nama_jenis_pajak']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-light); font-family: monospace;">KAP: <?= htmlspecialchars($row['kode_akun_pajak']) ?></div>
              </td>
              <td style="text-align: right; font-family: monospace; font-weight: 600;">
                <?= formatRupiah($row['jumlah_bayar']) ?>
              </td>
              <td style="text-align: center;">
                <?php if ($row['status_pembayaran'] === 'Paid'): ?>
                  <span class="badge badge-black">LUNAS</span>
                <?php else: ?>
                  <span class="badge badge-outline">PENDING</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.35rem;">
                  <a href="cetak_billing.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-subtle btn-sm" title="Cetak BPN">
                    Cetak
                  </a>
                  <?php if ($row['status_pembayaran'] === 'Unpaid'): ?>
                    <a href="pembayaran.php?action=pay&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                      Bayar
                    </a>
                  <?php endif; ?>
                  <a href="pembayaran.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-subtle btn-sm">
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

<!-- Grid 2 Kolom: Pelaporan SPT & Penerimaan per Jenis Pajak -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
  <!-- Pelaporan SPT Terkini -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Pelaporan SPT Terkini</h3>
        <p style="font-size: 0.78rem; color: var(--text-light); margin-top: 0.2rem;">Arsip berkas SPT Masa dan Tahunan.</p>
      </div>
      <a href="spt.php" class="btn btn-subtle btn-sm">Kelola SPT</a>
    </div>

    <div class="table-responsive" style="border: none;">
      <table class="table-clean">
        <thead>
          <tr>
            <th>No. BPS</th>
            <th>Wajib Pajak</th>
            <th>Periode</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sptList)): ?>
            <tr><td colspan="4" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">Belum ada SPT terdaftar.</td></tr>
          <?php else: ?>
            <?php foreach ($sptList as $spt): ?>
              <tr>
                <td style="font-family: monospace; font-weight: 600; font-size: 0.8rem;"><?= htmlspecialchars($spt['nomor_spt']) ?></td>
                <td><?= htmlspecialchars($spt['nama_wp']) ?></td>
                <td><?= $spt['jenis_spt'] ?> <?= $spt['tahun_pajak'] ?></td>
                <td><span class="badge badge-muted"><?= $spt['status_spt'] ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Penerimaan per Jenis Pajak -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Penerimaan per Jenis Pajak</h3>
        <p style="font-size: 0.78rem; color: var(--text-light); margin-top: 0.2rem;">Breakdown setoran berdasarkan kode objek.</p>
      </div>
      <a href="jenis_pajak.php" class="btn btn-subtle btn-sm">Tarif Pajak</a>
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
      <?php foreach ($breakdownList as $item): ?>
        <div style="background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 0.75rem 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
            <span style="font-weight: 600;"><?= htmlspecialchars($item['nama_jenis_pajak']) ?></span>
            <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);"><?= number_format($item['tarif_standar'], 2) ?>%</span>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted); margin-top: 0.35rem;">
            <span><?= $item['total_trx'] ?> Transaksi</span>
            <span style="font-weight: 600; color: var(--text-main); font-family: monospace;"><?= formatRupiah($item['total_nominal_paid']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
