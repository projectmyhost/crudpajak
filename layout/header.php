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
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Poppins"', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        tax: {
                            50: '#fafafa',
                            100: '#f4f4f5',
                            200: '#e4e4e7',
                            300: '#d4d4d8',
                            400: '#a1a1aa',
                            500: '#71717a',
                            600: '#52525b',
                            700: '#3f3f46',
                            800: '#27272a',
                            900: '#0a0a0a',
                            950: '#000000',
                        },
                        gold: {
                            100: '#f4f4f5',
                            200: '#e4e4e7',
                            300: '#d4d4d8',
                            400: '#0a0a0a',
                            500: '#000000',
                            600: '#27272a',
                        },
                        amber: {
                            50: '#fafafa',
                            100: '#f4f4f5',
                            200: '#e4e4e7',
                            300: '#0a0a0a',
                            400: '#0a0a0a',
                            500: '#000000',
                            600: '#27272a',
                            700: '#0a0a0a',
                            800: '#000000',
                        },
                        emerald: {
                            50: '#fafafa',
                            100: '#f4f4f5',
                            200: '#e4e4e7',
                            300: '#0a0a0a',
                            400: '#0a0a0a',
                            500: '#000000',
                            600: '#27272a',
                            700: '#0a0a0a',
                            800: '#000000',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-zinc-900 flex flex-col min-h-screen">

<!-- Clear Minimalist Navbar (No dark bar behind it, strict black & white clean) -->
<nav class="bg-white text-zinc-900 sticky top-0 z-50 border-b border-zinc-200" aria-label="Navigasi Utama">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <a href="index.php" class="flex items-center space-x-3 group focus:outline-none focus:ring-2 focus:ring-black">
                <div class="w-8 h-8 rounded bg-black flex items-center justify-center text-white font-bold text-sm">
                    P
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-lg tracking-tight text-black">SI-PAJAK</span>
                        <span class="bg-zinc-100 text-zinc-800 text-[10px] font-semibold px-1.5 py-0.5 rounded border border-zinc-200">DJP</span>
                    </div>
                </div>
            </a>

            <!-- Nav Links Desktop -->
            <div class="hidden lg:flex items-center space-x-1">
                <a href="index.php" class="px-3 py-2 rounded text-xs font-semibold uppercase tracking-wider transition focus:outline-none focus:ring-2 focus:ring-black <?= $active_nav === 'landing' ? 'bg-zinc-100 text-black font-bold' : 'text-zinc-600 hover:text-black hover:bg-zinc-50' ?>">
                    Beranda
                </a>
                <a href="dashboard.php" class="px-3 py-2 rounded text-xs font-semibold uppercase tracking-wider transition focus:outline-none focus:ring-2 focus:ring-black <?= $active_nav === 'dashboard' ? 'bg-zinc-100 text-black font-bold' : 'text-zinc-600 hover:text-black hover:bg-zinc-50' ?>">
                    Dashboard
                </a>
                <a href="wajib_pajak.php" class="px-3 py-2 rounded text-xs font-semibold uppercase tracking-wider transition focus:outline-none focus:ring-2 focus:ring-black <?= $active_nav === 'wajib_pajak' ? 'bg-zinc-100 text-black font-bold' : 'text-zinc-600 hover:text-black hover:bg-zinc-50' ?>">
                    Wajib Pajak
                </a>
                <a href="pembayaran.php" class="px-3 py-2 rounded text-xs font-semibold uppercase tracking-wider transition focus:outline-none focus:ring-2 focus:ring-black <?= $active_nav === 'pembayaran' ? 'bg-zinc-100 text-black font-bold' : 'text-zinc-600 hover:text-black hover:bg-zinc-50' ?>">
                    Billing &amp; Pembayaran
                </a>
                <a href="spt.php" class="px-3 py-2 rounded text-xs font-semibold uppercase tracking-wider transition focus:outline-none focus:ring-2 focus:ring-black <?= $active_nav === 'spt' ? 'bg-zinc-100 text-black font-bold' : 'text-zinc-600 hover:text-black hover:bg-zinc-50' ?>">
                    Pelaporan SPT
                </a>

                <!-- Dropdown Master Data -->
                <div class="relative" id="masterDropdownContainer">
                    <button id="masterDataBtn" type="button" aria-haspopup="true" aria-expanded="false" onclick="toggleMasterDropdown()" onkeydown="handleMasterKeyDown(event)"
                            class="px-3 py-2 rounded text-xs font-semibold uppercase tracking-wider transition flex items-center gap-1 text-zinc-600 hover:text-black hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-black <?= in_array($active_nav, ['jenis_pajak', 'kpp']) ? 'bg-zinc-100 text-black font-bold' : '' ?>">
                        Master Data <i class="fa-solid fa-chevron-down text-[10px] ml-1 opacity-70"></i>
                    </button>
                    <div id="masterDropdownMenu" class="absolute right-0 mt-1 w-48 bg-white rounded border border-zinc-200 py-1.5 hidden shadow-lg z-50">
                        <a href="jenis_pajak.php" class="flex items-center px-4 py-2 text-xs font-medium text-zinc-800 hover:bg-zinc-100 hover:text-black transition">
                            Tarif &amp; Jenis Pajak
                        </a>
                        <a href="kpp.php" class="flex items-center px-4 py-2 text-xs font-medium text-zinc-800 hover:bg-zinc-100 hover:text-black transition">
                            KPP &amp; Petugas Pajak
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Action CTA -->
            <div class="hidden sm:flex items-center space-x-3">
                <a href="pembayaran.php?action=create" class="inline-flex items-center min-h-[40px] px-4 py-2 rounded bg-black hover:bg-zinc-800 text-white font-semibold text-xs transition focus:outline-none focus:ring-2 focus:ring-black gap-1.5">
                    <i class="fa-solid fa-plus text-xs"></i> Buat Kode Billing
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="flex lg:hidden">
                <button type="button" id="mobileMenuBtn" onclick="toggleMobileNav()" aria-label="Buka Menu Navigasi" aria-expanded="false"
                        class="min-h-[40px] min-w-[40px] inline-flex items-center justify-center gap-2 px-3 py-2 rounded text-zinc-800 hover:text-black hover:bg-zinc-100 border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-black">
                    <span class="text-xs font-bold uppercase tracking-wider">Menu</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobileMenu" class="hidden lg:hidden bg-white border-t border-zinc-200 px-4 pt-3 pb-5 space-y-1">
        <a href="index.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'landing' ? 'bg-zinc-100 font-bold' : '' ?>">Beranda</a>
        <a href="dashboard.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'dashboard' ? 'bg-zinc-100 font-bold' : '' ?>">Dashboard</a>
        <a href="wajib_pajak.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'wajib_pajak' ? 'bg-zinc-100 font-bold' : '' ?>">Wajib Pajak</a>
        <a href="pembayaran.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'pembayaran' ? 'bg-zinc-100 font-bold' : '' ?>">Billing &amp; Pembayaran</a>
        <a href="spt.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'spt' ? 'bg-zinc-100 font-bold' : '' ?>">Pelaporan SPT</a>
        <a href="jenis_pajak.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'jenis_pajak' ? 'bg-zinc-100 font-bold' : '' ?>">Tarif &amp; Jenis Pajak</a>
        <a href="kpp.php" class="block min-h-[44px] flex items-center px-4 py-2.5 rounded text-sm font-medium text-zinc-800 hover:bg-zinc-100 <?= $active_nav === 'kpp' ? 'bg-zinc-100 font-bold' : '' ?>">KPP &amp; Petugas Pajak</a>
        <div class="pt-2">
            <a href="pembayaran.php?action=create" class="w-full min-h-[44px] flex items-center justify-center px-4 py-2.5 rounded bg-black text-white font-bold text-xs">
                + Buat Kode Billing Baru
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

<main class="flex-grow">
