<?php
use App\Core\Security;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$botTokenSet = !empty(SettingsService::getTelegramBotToken());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= Security::escape($pageTitle ?? 'Digital Store') ?> — <?= Security::escape($siteTitle) ?></title>
    
    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <base href="<?= rtrim(SettingsService::getAppUrl(), '/') ?>/">
    <link rel="stylesheet" href="<?= url('/css/storefront.css') ?>?v=<?= APP_VERSION ?>">
</head>
<body>

<div class="store-shell">
    <!-- Top Header Bar -->
    <header class="store-header">
        <a href="<?= url('/') ?>" class="brand-title">
            <div class="brand-icon">
                <i class="fa-solid fa-vault"></i>
            </div>
            <span><?= Security::escape($siteTitle) ?></span>
        </a>

        <!-- Desktop Navigation Cluster -->
        <div class="header-links desktop-nav">
            <a href="<?= url('/') ?>" class="nav-btn <?= $currentUri === '/' || $currentUri === '/abi' ? 'active' : '' ?>">
                <i class="fa-solid fa-store"></i> Products
            </a>
            <a href="<?= url('/about') ?>" class="nav-btn <?= str_ends_with($currentUri, '/about') ? 'active' : '' ?>">
                <i class="fa-solid fa-circle-info"></i> About
            </a>
            <a href="<?= url('/contact') ?>" class="nav-btn <?= str_ends_with($currentUri, '/contact') ? 'active' : '' ?>">
                <i class="fa-solid fa-envelope"></i> Contact
            </a>
            <?php if ($botTokenSet): ?>
                <a href="https://t.me" id="openTelegramBotBtn" target="_blank" class="nav-btn primary">
                    <i class="fa-brands fa-telegram"></i> Telegram Bot
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Header Compact Utility -->
        <?php if ($botTokenSet): ?>
            <a href="https://t.me" target="_blank" class="mobile-header-bot-btn mobile-only" title="Telegram Bot">
                <i class="fa-brands fa-telegram"></i>
            </a>
        <?php endif; ?>
    </header>

    <!-- Main View Dynamic Slot -->
    <main>
        <?= $content ?>
    </main>

    <!-- Desktop Footer -->
    <footer class="store-footer">
        <div class="footer-legal-links" style="display: flex; justify-content: center; flex-wrap: wrap; gap: 16px; margin-bottom: 12px;">
            <a href="<?= url('/about') ?>" style="color: var(--text-muted); text-decoration: none;">About Us</a>
            <span style="color: var(--hairline-border);">&bull;</span>
            <a href="<?= url('/contact') ?>" style="color: var(--text-muted); text-decoration: none;">Help Desk</a>
            <span style="color: var(--hairline-border);">&bull;</span>
            <a href="<?= url('/privacy') ?>" style="color: var(--text-muted); text-decoration: none;">Privacy Policy</a>
            <span style="color: var(--hairline-border);">&bull;</span>
            <a href="<?= url('/terms') ?>" style="color: var(--text-muted); text-decoration: none;">Terms of Service</a>
            <span style="color: var(--hairline-border);">&bull;</span>
            <a href="<?= url('/refund') ?>" style="color: var(--text-muted); text-decoration: none;">Refund Policy</a>
        </div>
        <div>&copy; <?= date('Y') ?> <?= Security::escape($siteTitle) ?> &bull; Automated PayPal Business Delivery</div>
        <div style="margin-top: 6px; color: var(--text-muted); font-size: 11px;">
            <i class="fa-solid fa-shield-halved"></i> 256-Bit SSL Encrypted Checkout &bull; Instant Fulfillment
        </div>
    </footer>
</div>

<!-- Telegram Mini App Bottom Navigation Bar (Mobile Dock) -->
<nav class="mobile-bottom-nav">
    <a href="<?= url('/') ?>" class="mb-nav-item <?= $currentUri === '/' || $currentUri === '/abi' ? 'active' : '' ?>">
        <i class="fa-solid fa-store"></i>
        <span>Shop</span>
    </a>
    <a href="<?= url('/about') ?>" class="mb-nav-item <?= str_ends_with($currentUri, '/about') ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-info"></i>
        <span>About</span>
    </a>
    <a href="<?= url('/contact') ?>" class="mb-nav-item <?= str_ends_with($currentUri, '/contact') ? 'active' : '' ?>">
        <i class="fa-solid fa-headset"></i>
        <span>Help</span>
    </a>
    <a href="<?= url('/refund') ?>" class="mb-nav-item <?= str_ends_with($currentUri, '/refund') ? 'active' : '' ?>">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Warranty</span>
    </a>
    <?php if ($botTokenSet): ?>
        <a href="https://t.me" target="_blank" class="mb-nav-item highlight">
            <i class="fa-brands fa-telegram"></i>
            <span>Bot</span>
        </a>
    <?php endif; ?>
</nav>

<script>
// Telegram Web App auto-expand & adaptive back button
if (window.Telegram && window.Telegram.WebApp) {
    const tg = window.Telegram.WebApp;
    tg.ready();
    tg.expand();

    // Telegram native back button for subpages
    if (window.location.pathname !== '/') {
        tg.BackButton.show();
        tg.BackButton.onClick(() => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        });
    } else {
        tg.BackButton.hide();
    }
    
    // Pass user ID into checkout links if opened inside Telegram
    if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        const userId = tg.initDataUnsafe.user.id;
        document.querySelectorAll('a[href^="/checkout/"]').forEach(link => {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('tg_user_id', userId);
            link.href = url.pathname + url.search;
        });
    }
}
</script>
</body>
</html>
