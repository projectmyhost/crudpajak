<?php
// layout/alerts.php
$flash = getFlashMessage();
if ($flash):
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    $colors = [
        'success' => 'bg-emerald-50 border-emerald-300 text-emerald-800',
        'danger'  => 'bg-rose-50 border-rose-300 text-rose-800',
        'warning' => 'bg-amber-50 border-amber-300 text-amber-800',
        'info'    => 'bg-blue-50 border-blue-300 text-blue-800',
    ];
    $icons = [
        'success' => 'fa-circle-check text-emerald-600',
        'danger'  => 'fa-circle-exclamation text-rose-600',
        'warning' => 'fa-triangle-exclamation text-amber-600',
        'info'    => 'fa-circle-info text-blue-600',
    ];
    $alertColor = $colors[$type] ?? $colors['info'];
    $icon = $icons[$type] ?? $icons['info'];
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <div id="alertBox" class="flex items-center justify-between p-4 rounded-xl border <?= $alertColor ?> shadow-sm transition-all duration-300 animate-fadeIn">
        <div class="flex items-center space-x-3">
            <i class="fa-solid <?= $icon ?> text-xl flex-shrink-0"></i>
            <span class="text-sm font-semibold"><?= htmlspecialchars($message) ?></span>
        </div>
        <button type="button" onclick="document.getElementById('alertBox').remove()" class="text-slate-400 hover:text-slate-700 transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
<?php endif; ?>

