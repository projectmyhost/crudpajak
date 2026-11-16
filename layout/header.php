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
    <title><?= htmlspecialchars($page_title) ?> - SI-PAJAK Terpadu</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        tax: {
                            50: '#f0f5fa',
                            100: '#e1ecf6',
                            200: '#c3dbed',
                            300: '#94c2df',
                            400: '#5fa3cd',
                            500: '#3886b8',
                            600: '#276c9b',
                            700: '#21577e',
                            800: '#1e4b6b',
                            900: '#0e2942',
                            950: '#091c2e',
                        },
                        gold: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .hero-pattern {
            background-color: #091c2e;
            background-image: radial-gradient(rgba(245, 158, 11, 0.12) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        /* Focus-visible standard for accessibility (R-32) */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
            outline: 2px solid #f59e0b;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">

<!-- Top Announcement & Status Bar -->
<div class="bg-tax-950 text-slate-200 text-xs py-2.5 px-4 border-b border-tax-900">
    <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
        <div class="flex items-center space-x-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span>
                Laragon MySQL Terhubung
            </span>
            <span class="hidden sm:inline text-slate-300">Basis Data: <strong class="text-amber-300">db_perpajakan</strong></span>
            <span class="hidden md:inline text-slate-400">|</span>
            <span class="hidden md:inline text-slate-300">Host: Localhost (Port 3306)</span>
        </div>
        <div class="flex items-center space-x-4 text-xs">
            <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="text-slate-300 hover:text-amber-300 transition flex items-center gap-1.5 focus:ring-1 focus:ring-amber-400 rounded px-1">
                <i class="fa-solid fa-database text-amber-400"></i> Kelola di phpMyAdmin
            </a>
            <span class="text-slate-600">|</span>
            <span class="text-slate-300"><i class="fa-regular fa-clock mr-1"></i> <?= date('d M Y') ?></span>
        </div>
    </div>
</div>

<!-- Main Navbar -->
<nav class="bg-tax-900 text-white sticky top-0 z-50 shadow-lg border-b border-tax-800" aria-label="Navigasi Utama">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <a href="index.php" class="flex items-center space-x-3 group rounded-xl p-1 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-tax-950 font-bold shadow-md">
                    <i class="fa-solid fa-scale-balanced text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-lg tracking-tight text-white">SI-PAJAK</span>
                        <span class="bg-amber-400/20 text-amber-300 text-[10px] font-bold px-1.5 py-0.5 rounded tracking-wide border border-amber-400/30">DJP</span>
                    </div>
                    <p class="text-[11px] text-slate-300 font-medium leading-none">Sistem Administrasi Perpajakan</p>
                </div>
            </a>

            <!-- Nav Links Desktop -->
            <div class="hidden lg:flex items-center space-x-1">
                <a href="index.php" class="px-3.5 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-400 <?= $active_nav === 'landing' ? 'bg-tax-800 text-amber-300' : 'text-slate-200 hover:bg-tax-800 hover:text-white' ?>">
                    <i class="fa-solid fa-house mr-1.5"></i> Beranda
                </a>
                <a href="dashboard.php" class="px-3.5 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-400 <?= $active_nav === 'dashboard' ? 'bg-tax-800 text-amber-300' : 'text-slate-200 hover:bg-tax-800 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-pie mr-1.5"></i> Dashboard CRUD
                </a>
                <a href="wajib_pajak.php" class="px-3.5 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-400 <?= $active_nav === 'wajib_pajak' ? 'bg-tax-800 text-amber-300' : 'text-slate-200 hover:bg-tax-800 hover:text-white' ?>">
                    <i class="fa-solid fa-users mr-1.5"></i> Wajib Pajak
                </a>
                <a href="pembayaran.php" class="px-3.5 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-400 <?= $active_nav === 'pembayaran' ? 'bg-tax-800 text-amber-300' : 'text-slate-200 hover:bg-tax-800 hover:text-white' ?>">
                    <i class="fa-solid fa-receipt mr-1.5"></i> Pembayaran & Billing
                </a>
                <a href="spt.php" class="px-3.5 py-2.5 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-400 <?= $active_nav === 'spt' ? 'bg-tax-800 text-amber-300' : 'text-slate-200 hover:bg-tax-800 hover:text-white' ?>">
                    <i class="fa-solid fa-file-invoice-dollar mr-1.5"></i> Pelaporan SPT
                </a>

                <!-- Dropdown Master Data with Full Keyboard Support (R-32) -->
                <div class="relative" id="masterDropdownContainer">
                    <button id="masterDataBtn" type="button" aria-haspopup="true" aria-expanded="false" onclick="toggleMasterDropdown()" onkeydown="handleMasterKeyDown(event)"
                            class="px-3.5 py-2.5 rounded-lg text-sm font-medium transition flex items-center gap-1.5 text-slate-200 hover:bg-tax-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-amber-400 <?= in_array($active_nav, ['jenis_pajak', 'kpp']) ? 'bg-tax-800 text-amber-300' : '' ?>">
                        <i class="fa-solid fa-database mr-1"></i> Master Data <i class="fa-solid fa-chevron-down text-xs ml-1 opacity-75"></i>
                    </button>
                    <div id="masterDropdownMenu" class="absolute right-0 mt-1.5 w-56 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 hidden text-slate-800 z-50">
                        <a href="jenis_pajak.php" class="flex items-center px-4 py-2.5 text-sm hover:bg-slate-100 hover:text-tax-900 transition font-medium focus:bg-slate-100 focus:outline-none">
                            <i class="fa-solid fa-percent text-amber-600 w-6"></i> Tarif & Jenis Pajak
                        </a>
                        <a href="kpp.php" class="flex items-center px-4 py-2.5 text-sm hover:bg-slate-100 hover:text-tax-900 transition font-medium focus:bg-slate-100 focus:outline-none">
                            <i class="fa-solid fa-building-columns text-tax-700 w-6"></i> KPP & Petugas Pajak
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Action CTA -->
            <div class="hidden sm:flex items-center space-x-3">
                <a href="pembayaran.php?action=create" class="inline-flex items-center min-h-[44px] px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-tax-950 font-bold text-xs shadow-md transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400">
                    <i class="fa-solid fa-plus-circle mr-1.5 text-sm"></i> Buat Kode Billing
                </a>
            </div>

            <!-- Mobile Menu Button with Text Label and min 44x44px Touch Target (R-03 / antislop-layoutmobile) -->
            <div class="flex lg:hidden">
                <button type="button" id="mobileMenuBtn" onclick="toggleMobileNav()" aria-label="Buka Menu Navigasi" aria-expanded="false"
                        class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-slate-200 hover:text-white hover:bg-tax-800 border border-tax-700 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <i class="fa-solid fa-bars text-lg"></i>
                    <span class="text-xs font-bold uppercase tracking-wider">Menu</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobileMenu" class="hidden lg:hidden bg-tax-950 border-t border-tax-800 px-4 pt-3 pb-5 space-y-1">
        <a href="index.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'landing' ? 'bg-tax-800 text-amber-300' : '' ?>">Beranda</a>
        <a href="dashboard.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'dashboard' ? 'bg-tax-800 text-amber-300' : '' ?>">Dashboard CRUD</a>
        <a href="wajib_pajak.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'wajib_pajak' ? 'bg-tax-800 text-amber-300' : '' ?>">Wajib Pajak</a>
        <a href="pembayaran.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'pembayaran' ? 'bg-tax-800 text-amber-300' : '' ?>">Pembayaran & Billing</a>
        <a href="spt.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'spt' ? 'bg-tax-800 text-amber-300' : '' ?>">Pelaporan SPT</a>
        <a href="jenis_pajak.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'jenis_pajak' ? 'bg-tax-800 text-amber-300' : '' ?>">Tarif & Jenis Pajak</a>
        <a href="kpp.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-tax-800 <?= $active_nav === 'kpp' ? 'bg-tax-800 text-amber-300' : '' ?>">KPP & Petugas Pajak</a>
        <div class="pt-2">
            <a href="pembayaran.php?action=create" class="w-full min-h-[44px] flex items-center justify-center px-4 py-2.5 rounded-xl bg-amber-500 text-tax-950 font-bold text-xs shadow-md">
                <i class="fa-solid fa-plus-circle mr-1.5"></i> Buat Kode Billing Baru
            </a>
        </div>
    </div>
</nav>

<script>
    function toggleMasterDropdown() {
        const btn = document.getElementById('masterDataBtn');
        const menu = document.getElementById('masterDropdownMenu');
        const isHidden = menu.classList.contains('hidden');
        if (isHidden) {
            menu.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    }

    function handleMasterKeyDown(e) {
        if (e.key === 'Escape') {
            document.getElementById('masterDropdownMenu').classList.add('hidden');
            document.getElementById('masterDataBtn').setAttribute('aria-expanded', 'false');
        }
    }

    // Close dropdown when clicked outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('masterDropdownContainer');
        if (container && !container.contains(e.target)) {
            const menu = document.getElementById('masterDropdownMenu');
            if (menu) menu.classList.add('hidden');
            const btn = document.getElementById('masterDataBtn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });

    function toggleMobileNav() {
        const menu = document.getElementById('mobileMenu');
        const btn = document.getElementById('mobileMenuBtn');
        const isHidden = menu.classList.contains('hidden');
        if (isHidden) {
            menu.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    }
</script>

<!-- Main Container -->
<main class="flex-grow">
