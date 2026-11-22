<?php
// layout/footer.php
?>
</main>

<!-- Footer (Minimalist Monochrome) -->
<footer class="bg-white text-zinc-700 mt-16 border-t border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Col 1: About -->
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded bg-black flex items-center justify-center text-white font-bold text-xs">
                        P
                    </div>
                    <span class="text-black font-bold text-base tracking-tight">SI-PAJAK</span>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed">
                    Sistem Basis Data Perpajakan Relasional untuk pencatatan identitas Wajib Pajak, kalkulasi otomatis tarif, pelaporan SPT, serta penatausahaan billing penerimaan kas negara.
                </p>
                <div class="pt-1 text-xs text-zinc-600 font-medium">
                    Laragon Web Server &bull; MySQL 5.7+
                </div>
            </div>

            <!-- Col 2: Fitur CRUD -->
            <div>
                <h4 class="text-black font-semibold text-xs uppercase tracking-wider mb-3">Modul Sistem</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="wajib_pajak.php" class="text-zinc-600 hover:text-black transition py-1 block">Master Wajib Pajak (NPWP/NIK)</a></li>
                    <li><a href="pembayaran.php" class="text-zinc-600 hover:text-black transition py-1 block">Transaksi Billing &amp; NTPN</a></li>
                    <li><a href="spt.php" class="text-zinc-600 hover:text-black transition py-1 block">Pelaporan SPT Masa &amp; Tahunan</a></li>
                    <li><a href="jenis_pajak.php" class="text-zinc-600 hover:text-black transition py-1 block">Tarif &amp; Jenis Objek Pajak</a></li>
                    <li><a href="kpp.php" class="text-zinc-600 hover:text-black transition py-1 block">KPP &amp; Petugas Pajak</a></li>
                </ul>
            </div>

            <!-- Col 3: Database & Tech Stack -->
            <div>
                <h4 class="text-black font-semibold text-xs uppercase tracking-wider mb-3">Spesifikasi Basis Data</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between border-b border-zinc-100 pb-1.5">
                        <span class="text-zinc-500">Engine DB:</span>
                        <span class="font-mono text-black font-semibold">MySQL InnoDB</span>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 pb-1.5">
                        <span class="text-zinc-500">Nama Database:</span>
                        <span class="font-mono text-black font-semibold">db_perpajakan</span>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 pb-1.5">
                        <span class="text-zinc-500">Struktur Relasi:</span>
                        <span class="font-semibold text-black">6 Tabel Terhubung FK</span>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 pb-1.5">
                        <span class="text-zinc-500">Port Server:</span>
                        <span class="font-mono text-black">3306 (Localhost)</span>
                    </div>
                </div>
            </div>

            <!-- Col 4: Quick Action -->
            <div>
                <h4 class="text-black font-semibold text-xs uppercase tracking-wider mb-3">Akses Database</h4>
                <p class="text-xs text-zinc-500 mb-3">
                    Kelola tabel secara langsung melalui phpMyAdmin bawaan Laragon:
                </p>
                <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="w-full min-h-[40px] inline-flex items-center justify-center px-4 py-2 rounded bg-zinc-100 hover:bg-zinc-200 text-black font-semibold text-xs border border-zinc-200 transition gap-2">
                    Buka phpMyAdmin
                </a>
            </div>
        </div>

        <div class="border-t border-zinc-200 mt-10 pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-zinc-500 gap-3">
            <p>&copy; <?= date('Y') ?> SI-PAJAK Terpadu &bull; Sistem Informasi Perpajakan Indonesia</p>
            <p>Lingkungan Server Lokal Laragon</p>
        </div>
    </div>
</footer>

<script>
    // Global auto dismiss alert after 5s
    setTimeout(() => {
        const alert = document.getElementById('alertBox');
        if (alert) {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
</script>
</body>
</html>
