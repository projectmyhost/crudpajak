<?php
// layout/footer.php
?>
  </div><!-- /.container -->
</main><!-- /.site-main -->

<footer class="site-footer">
  <div class="container">
    <div class="footer-inner">
      <div>
        <p style="font-weight: 600; color: var(--text-main); margin-bottom: 0.2rem;">SI-PAJAK Terpadu</p>
        <p style="font-size: 0.8rem; color: var(--text-light);">Sistem Administrasi Basis Data Perpajakan Relasional &bull; Laragon MySQL Server</p>
      </div>

      <ul class="footer-links">
        <li><a href="index.php">Beranda</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="wajib_pajak.php">Wajib Pajak</a></li>
        <li><a href="pembayaran.php">Billing &amp; Setoran</a></li>
        <li><a href="spt.php">Pelaporan SPT</a></li>
        <li><a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=db_perpajakan" target="_blank">phpMyAdmin</a></li>
      </ul>
    </div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const mobileToggle = document.getElementById('mobileNavToggle');
  const navLinks = document.getElementById('navLinks');
  if (mobileToggle && navLinks) {
    mobileToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      const expanded = navLinks.classList.contains('open');
      mobileToggle.setAttribute('aria-expanded', expanded);
    });
  }

  // Auto dismiss alert
  const alertBox = document.getElementById('alertBox');
  if (alertBox) {
    setTimeout(() => {
      alertBox.style.transition = 'opacity 0.4s ease';
      alertBox.style.opacity = '0';
      setTimeout(() => alertBox.remove(), 400);
    }, 5000);
  }
});
</script>
</body>
</html>
