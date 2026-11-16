<?php
// layout/footer.php
?>
</main>

<!-- Footer -->
<footer class="bg-tax-950 text-slate-300 mt-16 border-t border-tax-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Col 1: About -->
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-tax-950 font-bold">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <span class="text-white font-extrabold text-lg">SI-PAJAK</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Sistem Basis Data Perpajakan Relasional terpadu untuk pencatatan identitas Wajib Pajak, kalkulasi otomatis PPN/PPh, pelaporan SPT, serta penatausahaan billing penerimaan kas negara.
                </p>
                <div class="pt-2 text-xs text-amber-300">
                    <i class="fa-solid fa-server mr-1"></i> Laragon Web Server &bull; MySQL 5.7+
                </div>
            </div>

            <!-- Col 2: Fitur CRUD -->
            <div>
                <h4 class="text-white font-bold text-sm mb-3 uppercase tracking-wider text-amber-300">Modul CRUD</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="wajib_pajak.php" class="hover:text-white transition flex items-center gap-1.5 py-1 text-slate-200"><i class="fa-solid fa-angle-right text-slate-400"></i> Master Wajib Pajak (NPWP/NIK)</a></li>
                    <li><a href="pembayaran.php" class="hover:text-white transition flex items-center gap-1.5 py-1 text-slate-200"><i class="fa-solid fa-angle-right text-slate-400"></i> Transaksi Billing & NTPN</a></li>
                    <li><a href="spt.php" class="hover:text-white transition flex items-center gap-1.5 py-1 text-slate-200"><i class="fa-solid fa-angle-right text-slate-400"></i> Pelaporan SPT Masa & Tahunan</a></li>
                    <li><a href="jenis_pajak.php" class="hover:text-white transition flex items-center gap-1.5 py-1 text-slate-200"><i class="fa-solid fa-angle-right text-slate-400"></i> Tarif & Jenis Objek Pajak</a></li>
                    <li><a href="kpp.php" class="hover:text-white transition flex items-center gap-1.5 py-1 text-slate-200"><i class="fa-solid fa-angle-right text-slate-400"></i> KPP & Petugas AR</a></li>
                </ul>
            </div>

            <!-- Col 3: Database & Tech Stack -->
            <div>
                <h4 class="text-white font-bold text-sm mb-3 uppercase tracking-wider text-amber-300">Spesifikasi Sistem</h4>
                <div class="space-y-2 text-xs text-slate-200">
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-300">Engine DB:</span>
                        <span class="font-mono text-emerald-300 font-semibold">MySQL InnoDB</span>
                    </div>
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-300">Nama Database:</span>
                        <span class="font-mono text-amber-300 font-semibold">db_perpajakan</span>
                    </div>
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-300">Total Relasi:</span>
                        <span class="font-semibold text-white">6 Tabel Terhubung FK</span>
                    </div>
                    <div class="flex justify-between border-b border-tax-900 pb-1.5">
                        <span class="text-slate-300">Port Default:</span>
                        <span class="font-mono text-white">3306 (Root / No Pass)</span>
                    </div>
                </div>
            </div>

            <!-- Col 4: Quick Action -->
            <div>
                <h4 class="text-white font-bold text-sm mb-3 uppercase tracking-wider text-amber-300">Akses Database</h4>
                <p class="text-xs text-slate-300 mb-3">
                    Kelola database secara langsung melalui GUI phpMyAdmin bawaan Laragon:
                </p>
                <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank" class="w-full min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-tax-800 hover:bg-tax-700 text-white font-semibold text-xs border border-tax-700 transition gap-2">
                    <i class="fa-solid fa-database text-amber-300"></i> Buka Tabel di phpMyAdmin
                </a>
                <div class="mt-3 text-xs text-slate-400">
                    File SQL: <code class="text-slate-200 bg-tax-900 px-1 py-0.5 rounded">laragon\www\perpajakan\database.sql</code>
                </div>
            </div>
        </div>

        <div class="border-t border-tax-900 mt-10 pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-400 gap-3">
            <p>&copy; <?= date('Y') ?> SI-PAJAK Terpadu &bull; Sistem Informasi Perpajakan Indonesia</p>
            <p class="text-slate-300">Dikembangkan untuk Laragon Local Server Environment</p>
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

