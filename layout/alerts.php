<?php
// layout/alerts.php
$flash = getFlashMessage();
if ($flash):
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    $isError = in_array($type, ['danger', 'error']);
?>
<div class="alert <?= $isError ? 'alert-error' : 'alert-info' ?>" id="alertBox" style="margin-bottom: 2rem;">
    <span><?= htmlspecialchars($message) ?></span>
    <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer; font-weight:700; margin-left:1rem;" aria-label="Tutup">&times;</button>
</div>
<?php endif; ?>
