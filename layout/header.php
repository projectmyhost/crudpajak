<?php
// layout/header.php
if (!isset($page_title)) {
    $page_title = 'Sistem Informasi Perpajakan';
}
if (!isset($active_nav)) {
    $active_nav = 'landing';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SI-PAJAK</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Main Minimalist Monochrome Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <nav class="navbar" aria-label="Navigasi Utama">
      <a href="index.php" class="brand-logo">
        <span class="brand-mark">P</span>
        <span>SI-PAJAK</span>
      </a>

      <button class="mobile-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi" aria-expanded="false">
        Menu
      </button>

      <ul class="nav-links" id="navLinks">
        <li><a href="index.php" class="nav-link <?= $active_nav === 'landing' ? 'active' : ''; ?>">Beranda</a></li>
        <li><a href="dashboard.php" class="nav-link <?= $active_nav === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="wajib_pajak.php" class="nav-link <?= $active_nav === 'wajib_pajak' ? 'active' : ''; ?>">Wajib Pajak</a></li>
        <li><a href="pembayaran.php" class="nav-link <?= $active_nav === 'pembayaran' ? 'active' : ''; ?>">Billing &amp; Setoran</a></li>
        <li><a href="spt.php" class="nav-link <?= $active_nav === 'spt' ? 'active' : ''; ?>">Pelaporan SPT</a></li>
        <li><a href="jenis_pajak.php" class="nav-link <?= $active_nav === 'jenis_pajak' ? 'active' : ''; ?>">Tarif Pajak</a></li>
        <li><a href="kpp.php" class="nav-link <?= $active_nav === 'kpp' ? 'active' : ''; ?>">KPP</a></li>
      </ul>

      <div class="nav-actions">
        <a href="pembayaran.php?action=create" class="btn btn-primary btn-sm">+ Buat Billing</a>
      </div>
    </nav>
  </div>
</header>

<main class="site-main">
  <div class="container">
