<?php
// index.php - Landing Page Sistem Informasi Perpajakan
require_once __DIR__ . '/config/database.php';

$page_title = 'Beranda - Sistem Manajemen Database Perpajakan';
$active_nav = 'landing';

// Ambil Ringkasan Statistik Riil dari Database (R-17 / R-38 Data Nyata)
$totalWP = $pdo->query("SELECT COUNT(*) FROM wajib_pajak")->fetchColumn();
$totalSPT = $pdo->query("SELECT COUNT(*) FROM spt")->fetchColumn();
$totalPenerimaan = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_pajak WHERE status_pembayaran = 'Paid'")->fetchColumn();
$totalBilling = $pdo->query("SELECT COUNT(*) FROM pembayaran_pajak")->fetchColumn();
$totalKPP = $pdo->query("SELECT COUNT(*) FROM kpp")->fetchColumn();

// Cek Billing jika ada pencarian
$cekBillingResult = null;
$searchedBilling = '';
if (!empty($_GET['cek_billing'])) {
    $searchedBilling = trim($_GET['cek_billing']);
    $stmtCek = $pdo->prepare("
        SELECT p.*, w.nama_wp, w.npwp, j.nama_jenis_pajak, j.kode_akun_pajak, j.tarif_standar
        FROM pembayaran_pajak p
        JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
        JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
        WHERE p.kode_billing = ?
    ");
    $stmtCek->execute([$searchedBilling]);
    $cekBillingResult = $stmtCek->fetch();
}

// Ambil Jenis Pajak untuk Kalkulator
$listJenisPajak = $pdo->query("SELECT id, kode_akun_pajak, nama_jenis_pajak, tarif_standar, kategori_pajak FROM jenis_pajak ORDER BY id ASC")->fetchAll();

// Ambil Sampel Data untuk Tampilan Sesuai Docx (Bab 2)
$sampleWP = $pdo->query("SELECT id, npwp, nama_wp, alamat, jenis_wp FROM wajib_pajak LIMIT 3")->fetchAll();
$sampleJenis = $pdo->query("SELECT id, kode_akun_pajak, nama_jenis_pajak, tarif_standar FROM jenis_pajak LIMIT 3")->fetchAll();
$sampleTransaksi = $pdo->query("
    SELECT p.id, w.nama_wp, j.nama_jenis_pajak, j.kode_akun_pajak, p.dpp, p.jumlah_bayar, p.status_pembayaran, p.kode_billing
    FROM pembayaran_pajak p
    JOIN wajib_pajak w ON p.wajib_pajak_id = w.id
    JOIN jenis_pajak j ON p.jenis_pajak_id = j.id
    ORDER BY p.id ASC LIMIT 3
")->fetchAll();

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/alerts.php';
?>

<!-- Header / Intro Area (Matching Perpustakaan Index) -->
<div style="margin-bottom: 2.5rem; text-align: left;">
  <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
    Sistem Informasi Perpajakan
  </span>
  <h1 style="margin-top: 0.35rem; margin-bottom: 0.6rem;">Administrasi &amp; Manajemen Basis Data Pajak</h1>
  <p style="max-width: 650px;">
    Pengelolaan basis data perpajakan berbasis MySQL: pencatatan Wajib Pajak (NPWP/NIK), otomasi kalkulasi tarif (PPN/PPh), pelaporan SPT, serta penatausahaan kode billing penerimaan negara secara real-time.
  </p>
  <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.25rem;">
    <a href="dashboard.php" class="btn btn-primary">Buka Dashboard CRUD</a>
    <a href="#kalkulator" class="btn btn-secondary">Hitung Pajak Cepat</a>
    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="btn btn-subtle">phpMyAdmin</a>
  </div>
</div>

<!-- Search & Cek Status Billing Card -->
<div class="card" style="margin-bottom: 2.5rem; padding: 1.5rem;" id="cek-billing">
  <div style="margin-bottom: 1rem;">
    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
      Layanan Mandiri
    </span>
    <h2 style="font-size: 1.25rem; margin-top: 0.2rem; margin-bottom: 0.25rem;">Periksa Status Kode Billing</h2>
    <p style="font-size: 0.88rem;">Masukkan 15 digit kode billing untuk memeriksa validasi pelunasan setoran kas negara.</p>
  </div>

  <form method="GET" action="#cek-billing" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
    <input 
      type="text" 
      name="cek_billing" 
      maxlength="15" 
      value="<?= htmlspecialchars($searchedBilling) ?>" 
      placeholder="Masukkan 15 digit kode billing (Contoh: 123456789012345)" 
      class="form-control" 
      style="flex: 1; min-width: 250px; font-family: monospace;" 
      required
    >
    <button type="submit" class="btn btn-primary">Periksa Status</button>
    <?php if (!empty($searchedBilling)): ?>
      <a href="index.php" class="btn btn-subtle">Reset</a>
    <?php endif; ?>
  </form>

  <div style="font-size: 0.8rem; color: var(--text-muted);">
    Sampel kode billing uji coba: 
    <a href="?cek_billing=123456789012345#cek-billing" style="text-decoration: underline; font-weight: 500;">123456789012345</a> &bull;
    <a href="?cek_billing=345678901234567#cek-billing" style="text-decoration: underline; font-weight: 500;">345678901234567</a>
  </div>

  <?php if ($searchedBilling): ?>
    <div style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light);">
      <?php if ($cekBillingResult): ?>
        <div style="background: var(--bg-subtle); border: 1px solid var(--border-medium); border-radius: var(--radius-sm); padding: 1.25rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <div>
              <div style="font-weight: 600; font-size: 1.05rem;"><?= htmlspecialchars($cekBillingResult['nama_wp']) ?></div>
              <div style="font-size: 0.8rem; color: var(--text-light); font-family: monospace;">NPWP: <?= htmlspecialchars($cekBillingResult['npwp']) ?></div>
            </div>
            <div>
              <?php if ($cekBillingResult['status_pembayaran'] === 'Paid'): ?>
                <span class="badge badge-black">LUNAS (PAID)</span>
              <?php else: ?>
                <span class="badge badge-outline">MENUNGGU PEMBAYARAN</span>
              <?php endif; ?>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; font-size: 0.85rem; margin-top: 0.75rem; border-top: 1px solid var(--border-light); padding-top: 0.75rem;">
            <div>
              <span style="color: var(--text-light); display: block; font-size: 0.75rem;">Jenis Pajak:</span>
              <span style="font-weight: 500;"><?= htmlspecialchars($cekBillingResult['nama_jenis_pajak']) ?></span>
            </div>
            <div>
              <span style="color: var(--text-light); display: block; font-size: 0.75rem;">Jumlah Pajak:</span>
              <span style="font-weight: 600; font-family: monospace;"><?= formatRupiah($cekBillingResult['jumlah_bayar']) ?></span>
            </div>
            <?php if ($cekBillingResult['ntpn']): ?>
              <div>
                <span style="color: var(--text-light); display: block; font-size: 0.75rem;">NTPN:</span>
                <span style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($cekBillingResult['ntpn']) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div style="margin-top: 1rem; text-align: right;">
            <a href="cetak_billing.php?id=<?= $cekBillingResult['id'] ?>" target="_blank" class="btn btn-subtle btn-sm">
              Cetak Bukti Penerimaan Negara (BPN) &rarr;
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-error">
          Kode billing "<?= htmlspecialchars($searchedBilling) ?>" tidak ditemukan dalam basis data perpajakan.
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Real Metrics Stats Grid -->
<div class="stats-grid">
  <div class="stat-box">
    <div class="stat-label">Penerimaan Realisasi</div>
    <div class="stat-number"><?= formatRupiah($totalPenerimaan) ?></div>
    <div class="stat-desc">Setoran kas negara berstatus lunas (NTPN)</div>
  </div>

  <div class="stat-box">
    <div class="stat-label">Total Kode Billing</div>
    <div class="stat-number"><?= $totalBilling ?></div>
    <div class="stat-desc">Transaksi penatausahaan e-Billing</div>
  </div>

  <div class="stat-box">
    <div class="stat-label">Wajib Pajak</div>
    <div class="stat-number"><?= $totalWP ?></div>
    <div class="stat-desc">Entitas Badan &amp; Orang Pribadi terdaftar</div>
  </div>

  <div class="stat-box">
    <div class="stat-label">Pelaporan SPT</div>
    <div class="stat-number"><?= $totalSPT ?></div>
    <div class="stat-desc">SPT Masa &amp; Tahunan terarsip</div>
  </div>
</div>

<!-- Section: 3 Fungsi Utama -->
<section style="margin-top: 4.5rem; padding-top: 2.5rem; border-top: 1px solid var(--border-light);">
  <div style="margin-bottom: 2rem;">
    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
      Arsitektur Sistem
    </span>
    <h2 style="margin-top: 0.35rem; margin-bottom: 0.5rem;">Tiga Fungsi Utama Basis Data Perpajakan</h2>
    <p style="max-width: 600px;">
      Sistem basis data relasional untuk mencatat, mengelola, dan memproses data pendaftaran, transaksi, serta pembayaran pajak secara terpadu.
    </p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
    <!-- Card 1 -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <span style="font-size: 0.8rem; font-family: monospace; font-weight: 600;">#01</span>
          <span class="badge badge-muted">Master Data</span>
        </div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Manajemen Wajib Pajak</h3>
        <p style="font-size: 0.88rem; line-height: 1.6;">
          Menyimpan identitas tunggal Wajib Pajak (NPWP 16 digit &amp; NIK) untuk Orang Pribadi dan Badan hukum, lengkap dengan domisili KPP dan Account Representative (AR).
        </p>
      </div>
      <div style="margin-top: 1.5rem; padding-top: 0.85rem; border-top: 1px solid var(--border-light);">
        <a href="wajib_pajak.php" class="btn btn-subtle btn-sm" style="width: 100%;">Kelola Wajib Pajak &rarr;</a>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <span style="font-size: 0.8rem; font-family: monospace; font-weight: 600;">#02</span>
          <span class="badge badge-muted">Otomasi</span>
        </div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Perhitungan Tarif Otomatis</h3>
        <p style="font-size: 0.88rem; line-height: 1.6;">
          Menghitung kewajiban pajak (PPN 11%, PPh 21, PPh 23, PBB) secara presisi dari Dasar Pengenaan Pajak (DPP) sesuai tarif standar resmi dalam basis data.
        </p>
      </div>
      <div style="margin-top: 1.5rem; padding-top: 0.85rem; border-top: 1px solid var(--border-light);">
        <a href="#kalkulator" class="btn btn-subtle btn-sm" style="width: 100%;">Buka Kalkulator Pajak &rarr;</a>
      </div>
    </div>

    <!-- Card 3 -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <span style="font-size: 0.8rem; font-family: monospace; font-weight: 600;">#03</span>
          <span class="badge badge-muted">Kepatuhan</span>
        </div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Pelaporan SPT Elektronik</h3>
        <p style="font-size: 0.88rem; line-height: 1.6;">
          Menyediakan riwayat transaksi setoran negara, pelaporan SPT Masa/Tahunan dengan Bukti Penerimaan Surat (BPS), dan pencatatan status pelunasan.
        </p>
      </div>
      <div style="margin-top: 1.5rem; padding-top: 0.85rem; border-top: 1px solid var(--border-light);">
        <a href="spt.php" class="btn btn-subtle btn-sm" style="width: 100%;">Pelaporan SPT &rarr;</a>
      </div>
    </div>
  </div>
</section>

<!-- Section: Sampel Data Transaksional -->
<section style="margin-top: 4.5rem; padding-top: 2.5rem; border-top: 1px solid var(--border-light);">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
      <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
        Implementasi Skema
      </span>
      <h2 style="margin-top: 0.25rem; margin-bottom: 0.35rem;">Sampel Transaksi Billing Terkini</h2>
      <p style="font-size: 0.88rem;">Data relasional pembayaran yang tersimpan di MySQL database.</p>
    </div>
    <a href="pembayaran.php" class="btn btn-subtle btn-sm">Buka Seluruh Transaksi &rarr;</a>
  </div>

  <div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive" style="border: none;">
      <table class="table-clean">
        <thead>
          <tr>
            <th style="width: 80px;">ID</th>
            <th>Wajib Pajak</th>
            <th>Jenis Pajak</th>
            <th style="text-align: right;">DPP (Rp)</th>
            <th style="text-align: right;">Jumlah Pajak</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: right; width: 140px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sampleTransaksi as $tr): ?>
            <tr>
              <td><span class="badge badge-outline">#<?= $tr['id'] ?></span></td>
              <td>
                <div style="font-weight: 600;"><?= htmlspecialchars($tr['nama_wp']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-light); font-family: monospace;"><?= htmlspecialchars($tr['kode_billing']) ?></div>
              </td>
              <td><?= htmlspecialchars($tr['nama_jenis_pajak']) ?></td>
              <td style="text-align: right; font-family: monospace;"><?= formatRupiah($tr['dpp']) ?></td>
              <td style="text-align: right; font-family: monospace; font-weight: 600;"><?= formatRupiah($tr['jumlah_bayar']) ?></td>
              <td style="text-align: center;">
                <?php if ($tr['status_pembayaran'] === 'Paid'): ?>
                  <span class="badge badge-black">LUNAS</span>
                <?php else: ?>
                  <span class="badge badge-outline">PENDING</span>
                <?php endif; ?>
              </td>
              <td style="text-align: right;">
                <div style="display: inline-flex; gap: 0.4rem;">
                  <a href="cetak_billing.php?id=<?= $tr['id'] ?>" target="_blank" class="btn btn-subtle btn-sm" title="Cetak BPN">
                    Cetak
                  </a>
                  <a href="pembayaran.php" class="btn btn-subtle btn-sm">
                    Rincian
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- Section: Kalkulator Pajak Interaktif -->
<section id="kalkulator" style="margin-top: 4.5rem; padding-top: 2.5rem; border-top: 1px solid var(--border-light);">
  <div style="margin-bottom: 2rem;">
    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
      Simulasi Cepat
    </span>
    <h2 style="margin-top: 0.35rem; margin-bottom: 0.5rem;">Kalkulator Pajak Interaktif</h2>
    <p style="max-width: 600px;">
      Hitung estimasi kewajiban pajak terutang berdasarkan Dasar Pengenaan Pajak (DPP) dan tarif standar yang berlaku.
    </p>
  </div>

  <div class="card" style="padding: 2rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2.5rem; align-items: center;">
      <!-- Inputs -->
      <div>
        <div class="form-group">
          <label for="calcJenisPajak" class="form-label">1. Pilih Objek / Jenis Pajak</label>
          <select id="calcJenisPajak" class="form-control" onchange="hitungPajakLive()">
            <?php foreach ($listJenisPajak as $jp): ?>
              <option value="<?= $jp['tarif_standar'] ?>" data-nama="<?= htmlspecialchars($jp['nama_jenis_pajak']) ?>" data-kap="<?= htmlspecialchars($jp['kode_akun_pajak']) ?>">
                <?= htmlspecialchars($jp['nama_jenis_pajak']) ?> (<?= number_format($jp['tarif_standar'], 2) ?>%)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="calcDPP" class="form-label">2. Dasar Pengenaan Pajak (DPP)</label>
          <input 
            type="number" 
            id="calcDPP" 
            value="100000000" 
            min="0" 
            step="10000" 
            class="form-control" 
            style="font-family: monospace;" 
            oninput="hitungPajakLive()"
          >
          <span style="font-size: 0.75rem; color: var(--text-light); margin-top: 0.35rem; display: block;">
            Contoh: Rp 100.000.000 atau Rp 50.000.000
          </span>
        </div>

        <div>
          <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
            Pilihan Cepat Nilai DPP:
          </span>
          <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" onclick="setDPP(15000000)" class="btn btn-subtle btn-sm">15 Juta</button>
            <button type="button" onclick="setDPP(50000000)" class="btn btn-subtle btn-sm">50 Juta</button>
            <button type="button" onclick="setDPP(100000000)" class="btn btn-subtle btn-sm">100 Juta</button>
            <button type="button" onclick="setDPP(500000000)" class="btn btn-subtle btn-sm">500 Juta</button>
          </div>
        </div>
      </div>

      <!-- Results Card (Solid Minimalist Black Box) -->
      <div style="background: #0A0A0A; color: #FFFFFF; border-radius: var(--radius-md); padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #27272A; padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
          <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #A1A1AA; font-weight: 600;">
            Hasil Perhitungan
          </span>
          <span id="resTarif" class="badge badge-outline" style="border-color: #52525B; color: #FFFFFF;">
            Tarif 11.00%
          </span>
        </div>

        <div style="margin-bottom: 1.25rem;">
          <span style="font-size: 0.78rem; color: #71717A; display: block;">Dasar Pengenaan Pajak:</span>
          <span id="resDPP" style="font-size: 1.1rem; font-family: monospace; font-weight: 600; color: #E4E4E7;">Rp 100.000.000</span>
        </div>

        <div style="padding-top: 1rem; border-top: 1px solid #27272A; margin-bottom: 1.5rem;">
          <span style="font-size: 0.78rem; color: #71717A; display: block;">Estimasi Pajak Terutang:</span>
          <span id="resPajak" style="font-size: 2rem; font-family: monospace; font-weight: 700; color: #FFFFFF; line-height: 1.2;">Rp 11.000.000</span>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
          <a href="pembayaran.php?action=create" class="btn btn-sm" style="background: #FFFFFF; color: #000000; flex: 1;">
            Terbitkan Kode Billing
          </a>
          <button type="button" onclick="salinHasil()" class="btn btn-subtle btn-sm" style="background: #18181B; color: #FFFFFF; border-color: #27272A;">
            Salin Nilai
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Section: Skema Relasi 6 Tabel -->
<section style="margin-top: 4.5rem; padding-top: 2.5rem; border-top: 1px solid var(--border-light); margin-bottom: 2rem;">
  <div style="margin-bottom: 2rem;">
    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);">
      Integritas Relasional
    </span>
    <h2 style="margin-top: 0.35rem; margin-bottom: 0.5rem;">Struktur 6 Tabel Basis Data MySQL</h2>
    <p style="max-width: 600px;">
      Tabel-tabel terhubung menggunakan Foreign Key, Indexing Unik, dan integritas referensial.
    </p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
    <div class="card" style="padding: 1.25rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-family: monospace; font-weight: 600;">4.1 kpp</span>
        <span class="badge badge-muted">Master</span>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace; line-height: 1.5;">
        PK: id (INT)<br>
        UQ: kode_kpp (VARCHAR 3)<br>
        nama_kpp &bull; alamat
      </div>
    </div>

    <div class="card" style="padding: 1.25rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-family: monospace; font-weight: 600;">4.2 petugas_pajak</span>
        <span class="badge badge-muted">AR</span>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace; line-height: 1.5;">
        PK: id (INT)<br>
        UQ: nip (VARCHAR 18)<br>
        FK: kpp_id &rarr; kpp.id
      </div>
    </div>

    <div class="card" style="padding: 1.25rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-family: monospace; font-weight: 600;">4.3 wajib_pajak</span>
        <span class="badge badge-muted">Master</span>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace; line-height: 1.5;">
        PK: id (INT)<br>
        UQ: npwp (VARCHAR 25)<br>
        FK: kpp_id, petugas_ar_id
      </div>
    </div>

    <div class="card" style="padding: 1.25rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-family: monospace; font-weight: 600;">4.4 jenis_pajak</span>
        <span class="badge badge-muted">Tarif</span>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace; line-height: 1.5;">
        PK: id (INT)<br>
        UQ: kode_akun_pajak (KAP)<br>
        tarif_standar (%)
      </div>
    </div>

    <div class="card" style="padding: 1.25rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-family: monospace; font-weight: 600;">4.5 spt</span>
        <span class="badge badge-muted">Laporan</span>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace; line-height: 1.5;">
        PK: id (INT)<br>
        UQ: nomor_spt / BPS<br>
        FK: wajib_pajak_id, jenis_id
      </div>
    </div>

    <div class="card" style="padding: 1.25rem;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-family: monospace; font-weight: 600;">4.6 pembayaran_pajak</span>
        <span class="badge badge-muted">Billing</span>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted); font-family: monospace; line-height: 1.5;">
        PK: id (INT)<br>
        UQ: kode_billing, ntpn<br>
        FK: spt_id, wajib_pajak_id
      </div>
    </div>
  </div>
</section>

<!-- Client Side Tax Calculator Script -->
<script>
function formatRupiahJs(number) {
    return 'Rp ' + Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function setDPP(amount) {
    document.getElementById('calcDPP').value = amount;
    hitungPajakLive();
}

function hitungPajakLive() {
    const select = document.getElementById('calcJenisPajak');
    const tarif = parseFloat(select.value) || 0;
    const dpp = parseFloat(document.getElementById('calcDPP').value) || 0;
    
    const pajak = (dpp * tarif) / 100;

    document.getElementById('resTarif').innerText = 'Tarif ' + tarif.toFixed(2) + '%';
    document.getElementById('resDPP').innerText = formatRupiahJs(dpp);
    document.getElementById('resPajak').innerText = formatRupiahJs(pajak);
}

function salinHasil() {
    const text = document.getElementById('resPajak').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Nilai Pajak berhasil disalin: ' + text);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    hitungPajakLive();
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
