<?php
// layout/alerts.php
$flash = getFlashMessage();
if ($flash):
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    $isError = in_array($type, ['danger', 'error']);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <div id="alertBox" class="flex items-center justify-between p-4 rounded border <?= $isError ? 'bg-black text-white border-black' : 'bg-zinc-50 text-black border-zinc-300' ?> transition-all duration-300">
        <div class="flex items-center space-x-3">
            <span class="text-sm font-medium"><?= htmlspecialchars($message) ?></span>
        </div>
        <button type="button" onclick="document.getElementById('alertBox').remove()" class="<?= $isError ? 'text-zinc-400 hover:text-white' : 'text-zinc-500 hover:text-black' ?> transition ml-4" aria-label="Tutup">
            &times;
        </button>
    </div>
</div>
<?php endif; ?>
