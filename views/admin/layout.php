<?php
use App\Core\Session;
use App\Core\Security;
use App\Services\SettingsService;

$admin = Session::getAdmin();
$activeMenu = $activeMenu ?? 'dashboard';
$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
$botTokenSet = !empty(SettingsService::getTelegramBotToken());
$paypalLive = SettingsService::getPaypalMode() === 'live';

$moduleTitles = [
    'dashboard' => 'Mission Control',
    'products' => 'Products Manager',
    'cards' => 'Cards & Serials',
    'orders' => 'Order Ledger',
    'customers' => 'Customers & Wallets',
    'transactions' => 'PayPal Captures',
    'settings' => 'System Settings',
];
$currentModuleTitle = $moduleTitles[$activeMenu] ?? ($pageTitle ?? 'Mission Control');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= Security::escape($pageTitle ?? 'Mission Control') ?> — <?= Security::escape($siteTitle) ?></title>
    
    <!-- Telegram WebApp SDK for Mini App Context -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <base href="<?= rtrim(SettingsService::getAppUrl(), '/') ?>/">
    <link rel="stylesheet" href="<?= url('/css/radar_admin.css') ?>?v=<?= APP_VERSION ?>">
</head>
<body>

<!-- Backdrop for Mobile Drawer -->
<div class="radar-backdrop" id="radarBackdrop"></div>

<div class="radar-wrapper">
    <!-- Screen-Anchored Fixed Sidebar / Mobile Slide Drawer -->
    <aside class="radar-sidebar" id="radarSidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-main">
                <div class="radar-logo-pulse">
                    <i class="fa-solid fa-satellite-dish"></i>
                </div>
                <div class="brand-text">
                    <h1><?= Security::escape($siteTitle) ?></h1>
                    <span>RADAR MISSION CONTROL</span>
                </div>
            </div>
            <!-- Dedicated Mobile Drawer Close Button [X] -->
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close navigation drawer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-category">Main Console</div>
            <a href="<?= url('/admin') ?>" class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>

            <div class="nav-category">E-Commerce & Assets</div>
            <a href="<?= url('/admin/products') ?>" class="nav-link <?= $activeMenu === 'products' ? 'active' : '' ?>">
                <i class="fa-solid fa-box-archive"></i> <span>Products Manager</span>
            </a>
            <a href="<?= url('/admin/cards') ?>" class="nav-link <?= $activeMenu === 'cards' ? 'active' : '' ?>">
                <i class="fa-solid fa-id-card"></i> <span>Cards Manager</span>
            </a>
            <a href="<?= url('/admin/orders') ?>" class="nav-link <?= $activeMenu === 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-cart-shopping"></i> <span>Order Manager</span>
            </a>
            <a href="<?= url('/admin/customers') ?>" class="nav-link <?= $activeMenu === 'customers' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> <span>Customers & Wallets</span>
            </a>
            <a href="<?= url('/admin/transactions') ?>" class="nav-link <?= $activeMenu === 'transactions' ? 'active' : '' ?>">
                <i class="fa-brands fa-paypal"></i> <span>Transactions</span>
            </a>

            <div class="nav-category">Automation & Developer</div>
            <a href="<?= url('/admin/api') ?>" class="nav-link <?= $activeMenu === 'api' ? 'active' : '' ?>">
                <i class="fa-solid fa-code"></i> <span>System API & LLM</span>
            </a>

            <div class="nav-category">Configuration</div>
            <a href="<?= url('/admin/settings') ?>" class="nav-link <?= $activeMenu === 'settings' ? 'active' : '' ?>">
                <i class="fa-solid fa-sliders"></i> <span>System Settings</span>
            </a>
            <a href="<?= url('/') ?>" target="_blank" class="nav-link">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Live Storefront</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <span>v<?= APP_VERSION ?></span>
            <span>PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></span>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="radar-main">
        <!-- Modern Anti-Gravity V3 Topbar -->
        <header class="radar-topbar">
            <div class="topbar-left">
                <!-- Mobile Hamburger Button -->
                <button type="button" class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Open Navigation Drawer">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <!-- Global Breadcrumb -->
                <div class="topbar-breadcrumb">
                    <div class="radar-logo-mini">
                        <i class="fa-solid fa-satellite-dish"></i>
                    </div>
                    <div class="breadcrumb-text">
                        <span class="breadcrumb-app">NEXUS</span>
                        <i class="fa-solid fa-chevron-right breadcrumb-separator"></i>
                        <span class="breadcrumb-current"><?= Security::escape($currentModuleTitle) ?></span>
                    </div>
                </div>

                <!-- Desktop Quick Search -->
                <div class="search-box desktop-only">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="globalTableSearch" placeholder="Filter ledger records...">
                </div>
            </div>

            <div class="topbar-right">
                <!-- PayPal Indicator -->
                <div class="status-badge-live" title="PayPal Mode: <?= $paypalLive ? 'LIVE' : 'SANDBOX' ?>">
                    <div class="status-dot <?= $paypalLive ? 'live' : 'sandbox' ?>"></div>
                    <span class="desktop-only"><?= $paypalLive ? 'PAYPAL LIVE' : 'PAYPAL SANDBOX' ?></span>
                    <span class="mobile-only-pill <?= $paypalLive ? 'live' : 'sandbox' ?>"><?= $paypalLive ? 'LIVE' : 'SBOX' ?></span>
                </div>

                <!-- Telegram Bot Indicator -->
                <div class="status-badge-live" style="color: <?= $botTokenSet ? '#38bdf8' : '#f87171' ?>;" title="Telegram Bot: <?= $botTokenSet ? 'ONLINE' : 'OFFLINE' ?>">
                    <i class="fa-brands fa-telegram"></i>
                    <span class="desktop-only"><?= $botTokenSet ? 'BOT CONNECTED' : 'BOT OFFLINE' ?></span>
                </div>

                <!-- Storefront Direct Link -->
                <a href="<?= url('/') ?>" target="_blank" class="topbar-icon-btn" title="View Live Storefront">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>

                <!-- User Shield & Logout -->
                <div class="topbar-user-pill" title="Logged in as <?= Security::escape($admin['username'] ?? 'Admin') ?>">
                    <i class="fa-solid fa-user-shield"></i>
                    <span class="desktop-only"><?= Security::escape($admin['username'] ?? 'Admin') ?></span>
                </div>

                <a href="<?= url('/admin/logout') ?>" class="topbar-logout-btn" title="Sign Out">
                    <i class="fa-solid fa-power-off"></i>
                    <span class="desktop-only">Logout</span>
                </a>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <div class="content-body">
            <?php if ($successMsg = Session::flash('success')): ?>
                <div class="flash-alert success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= Security::escape($successMsg) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg = Session::flash('error')): ?>
                <div class="flash-alert error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= Security::escape($errorMsg) ?></span>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>
</div>

<!-- Telegram Mini App & Mobile Bottom Navigation Dock -->
<nav class="radar-mobile-bottom-nav">
    <a href="<?= url('/admin') ?>" class="radar-mb-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Radar</span>
    </a>
    <a href="<?= url('/admin/products') ?>" class="radar-mb-item <?= $activeMenu === 'products' ? 'active' : '' ?>">
        <i class="fa-solid fa-box-archive"></i>
        <span>Products</span>
    </a>
    <a href="<?= url('/admin/orders') ?>" class="radar-mb-item <?= $activeMenu === 'orders' ? 'active' : '' ?>">
        <i class="fa-solid fa-cart-shopping"></i>
        <span>Orders</span>
    </a>
    <a href="<?= url('/admin/cards') ?>" class="radar-mb-item <?= $activeMenu === 'cards' ? 'active' : '' ?>">
        <i class="fa-solid fa-id-card"></i>
        <span>Cards</span>
    </a>
    <button type="button" class="radar-mb-item" id="mobileBottomMenuTrigger" aria-label="Open Full Admin Drawer">
        <i class="fa-solid fa-bars-staggered"></i>
        <span>More</span>
    </button>
</nav>

<script src="<?= url('/js/radar_admin.js') ?>?v=<?= APP_VERSION ?>"></script>
</body>
</html>
