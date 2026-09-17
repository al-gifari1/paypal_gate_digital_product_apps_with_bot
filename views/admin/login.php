<?php
use App\Core\Session;
use App\Core\Security;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Digital Vault');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= Security::escape($siteTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <base href="<?= rtrim(SettingsService::getAppUrl(), '/') ?>/">
    <link rel="stylesheet" href="<?= url('/css/radar_admin.css') ?>?v=<?= APP_VERSION ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            background-color: var(--surface-bg);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-v3-modal);
            padding: 32px 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div class="radar-logo-pulse">
            <i class="fa-solid fa-satellite-dish"></i>
        </div>
        <div>
            <h1 style="font-family: var(--font-display); font-size: 18px; color: #fff; letter-spacing: 0.5px;">RADAR CONTROL</h1>
            <p style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">Security Terminal Access</p>
        </div>
    </div>

    <?php if ($errorMsg = Session::flash('error')): ?>
        <div class="flash-alert error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= Security::escape($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/login') ?>">
        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
        
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="admin">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn-utility cyan" style="width: 100%; justify-content: center; padding: 10px; margin-top: 10px;">
            <i class="fa-solid fa-unlock-keyhole"></i> Authorize & Enter
        </button>
    </form>

    <div style="text-align: center; font-family: var(--font-mono); font-size: 10px; color: var(--text-muted);">
        Default login: <code>admin</code> / <code>admin123!</code>
    </div>
</div>

</body>
</html>
