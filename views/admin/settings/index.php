<?php
use App\Core\Security;
use App\Core\Session;
use App\Services\SettingsService;

$appUrl = SettingsService::getAppUrl();
$paypalWebhookUrl = rtrim($appUrl, '/') . '/api/paypal/webhook';
$telegramWebhookUrl = rtrim($appUrl, '/') . '/api/telegram/webhook';

$isLive = ($settings['paypal_mode'] ?? 'sandbox') === 'live';
$paypalReady = !empty($settings['paypal_client_id']) && !empty($settings['paypal_client_secret']);
$botReady = !empty($settings['telegram_bot_token']);
?>

<div class="settings-container">
    <!-- Top KPI Readiness Matrix -->
    <div class="settings-status-matrix">
        <!-- Store Identity -->
        <div class="status-card">
            <div class="status-card-info">
                <span class="status-card-label">Storefront Identity</span>
                <div class="status-card-value">
                    <?= Security::escape($settings['site_title'] ?? 'Digital Vault & Bot') ?>
                </div>
                <span class="status-card-sub">Base Currency: <strong><?= Security::escape($settings['currency'] ?? 'USD') ?></strong></span>
            </div>
            <div class="status-card-icon">
                <i class="fa-solid fa-store" style="color: var(--accent-cyan);"></i>
            </div>
        </div>

        <!-- PayPal Gateway Status -->
        <div class="status-card paypal <?= $isLive ? 'active' : 'warning' ?>">
            <div class="status-card-info">
                <span class="status-card-label">PayPal Gateway</span>
                <div class="status-card-value">
                    <span class="mobile-only-pill <?= $isLive ? 'live' : 'sandbox' ?>" style="display: inline-block; font-size: 11px; padding: 2px 6px;">
                        <?= $isLive ? 'LIVE' : 'SANDBOX' ?>
                    </span>
                    <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">
                        <?= $paypalReady ? 'Configured' : 'Incomplete' ?>
                    </span>
                </div>
                <span class="status-card-sub">
                    <?= $paypalReady ? 'Client ID & Secret set' : 'Missing API credentials' ?>
                </span>
            </div>
            <div class="status-card-icon">
                <i class="fa-brands fa-paypal" style="color: #0284c7;"></i>
            </div>
        </div>

        <!-- Telegram Engine Status -->
        <div class="status-card telegram <?= $botReady ? 'active' : '' ?>">
            <div class="status-card-info">
                <span class="status-card-label">Telegram Engine</span>
                <div class="status-card-value">
                    <span style="font-size: 13px; font-weight: 700; color: <?= $botReady ? '#4ade80' : 'var(--text-muted)' ?>;">
                        <i class="fa-solid <?= $botReady ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                        <?= $botReady ? 'Token Active' : 'No Token' ?>
                    </span>
                </div>
                <span class="status-card-sub">
                    Chat ID: <?= !empty($settings['telegram_admin_chat_id']) ? Security::escape($settings['telegram_admin_chat_id']) : 'Not Linked' ?>
                </span>
            </div>
            <div class="status-card-icon">
                <i class="fa-brands fa-telegram" style="color: #38bdf8;"></i>
            </div>
        </div>
    </div>

    <!-- Tabbed Navigation Bar -->
    <div class="settings-tab-nav" role="tablist">
        <button type="button" class="settings-tab-btn active" data-tab="tab-general">
            <i class="fa-solid fa-globe"></i>
            <span>General & Branding</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="tab-paypal">
            <i class="fa-brands fa-paypal"></i>
            <span>PayPal Gateway</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="tab-telegram">
            <i class="fa-brands fa-telegram"></i>
            <span>Telegram Bot Engine</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="tab-diagnostics">
            <i class="fa-solid fa-satellite-dish"></i>
            <span>Diagnostics & CLI</span>
        </button>
    </div>

    <!-- Main Settings Form Covering All Config Tabs -->
    <form method="POST" action="/admin/settings/save" id="systemSettingsForm">
        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">

        <!-- TAB 1: General & Branding -->
        <div class="settings-tab-pane active" id="tab-general">
            <div class="panel-surface">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-solid fa-sliders" style="color: var(--accent-cyan);"></i>
                        <span>Platform Identity & URLs</span>
                    </div>
                </div>

                <div class="settings-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="site_title">Platform / Store Title</label>
                        <input type="text" id="site_title" name="site_title" class="form-control" 
                               value="<?= Security::escape($settings['site_title'] ?? 'Digital Vault & Bot') ?>" 
                               required placeholder="e.g. Digital Vault & Bot">
                        <div class="settings-field-hint">Displayed in storefront header, checkout banner, and customer receipts.</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="currency">Store Default Currency</label>
                        <input type="text" id="currency" name="currency" class="form-control" 
                               value="<?= Security::escape($settings['currency'] ?? 'USD') ?>" 
                               required placeholder="USD, EUR, GBP...">
                        <div class="settings-field-hint">Standard ISO 4217 currency code for store transactions.</div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 8px;">
                    <label class="form-label" for="app_url">Public Base Application URL</label>
                    <div class="input-action-wrapper">
                        <input type="url" id="app_url" name="app_url" class="form-control" 
                               value="<?= Security::escape($settings['app_url'] ?? 'http://localhost:8080') ?>" 
                               required placeholder="https://your-domain.com">
                        <button type="button" class="input-action-btn btn-copy-code" data-copy="<?= Security::escape($settings['app_url'] ?? 'http://localhost:8080') ?>" title="Copy URL">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    <div class="settings-field-hint">
                        Crucial: Used for PayPal return/cancel redirects, Telegram Mini App URLs, and asset links.
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: PayPal Gateway -->
        <div class="settings-tab-pane" id="tab-paypal">
            <div class="panel-surface">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-brands fa-paypal" style="color: var(--accent-blue);"></i>
                        <span>PayPal Business REST API Credentials</span>
                    </div>
                    <span class="mobile-only-pill <?= $isLive ? 'live' : 'sandbox' ?>" style="display: inline-block;">
                        <?= $isLive ? 'LIVE' : 'SANDBOX' ?>
                    </span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="paypal_mode">Gateway Environment</label>
                    <select id="paypal_mode" name="paypal_mode" class="form-control">
                        <option value="sandbox" <?= ($settings['paypal_mode'] ?? '') === 'sandbox' ? 'selected' : '' ?>>
                            Sandbox (Simulated Payments & Testing)
                        </option>
                        <option value="live" <?= ($settings['paypal_mode'] ?? '') === 'live' ? 'selected' : '' ?>>
                            Live (Production Real Money Payments)
                        </option>
                    </select>
                    <div class="settings-field-hint">
                        Always test checkout in Sandbox mode first before switching to Live production mode.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="paypal_client_id">PayPal Client ID</label>
                    <input type="text" id="paypal_client_id" name="paypal_client_id" class="form-control" 
                           value="<?= Security::escape($settings['paypal_client_id'] ?? '') ?>" 
                           placeholder="Axxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" 
                           spellcheck="false" autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label" for="paypal_client_secret">PayPal Client Secret</label>
                    <div class="input-action-wrapper">
                        <input type="password" id="paypal_client_secret" name="paypal_client_secret" class="form-control" 
                               value="<?= Security::escape($settings['paypal_client_secret'] ?? '') ?>" 
                               placeholder="Exxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" 
                               spellcheck="false" autocomplete="new-password">
                        <button type="button" class="input-action-btn password-toggle-btn" data-target="paypal_client_secret" title="Toggle Visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="paypal_webhook_id">PayPal Webhook ID (Optional)</label>
                    <input type="text" id="paypal_webhook_id" name="paypal_webhook_id" class="form-control" 
                           value="<?= Security::escape($settings['paypal_webhook_id'] ?? '') ?>" 
                           placeholder="WH-xxxxxxxxxxxxxxxx">
                    <div class="settings-field-hint">
                        Configure this webhook URL inside your PayPal Developer Dashboard:
                    </div>
                    <div class="settings-code-box">
                        <span class="settings-code-text"><?= Security::escape($paypalWebhookUrl) ?></span>
                        <button type="button" class="btn-copy-code" data-copy="<?= Security::escape($paypalWebhookUrl) ?>">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Telegram Bot Engine -->
        <div class="settings-tab-pane" id="tab-telegram">
            <div class="panel-surface">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-brands fa-telegram" style="color: #38bdf8;"></i>
                        <span>Telegram Bot Integration & Notifications</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telegram_bot_token">Telegram Bot API Token</label>
                    <div class="input-action-wrapper">
                        <input type="password" id="telegram_bot_token" name="telegram_bot_token" class="form-control" 
                               value="<?= Security::escape($settings['telegram_bot_token'] ?? '') ?>" 
                               placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxYZ" 
                               spellcheck="false" autocomplete="new-password">
                        <button type="button" class="input-action-btn password-toggle-btn" data-target="telegram_bot_token" title="Toggle Visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="settings-field-hint">
                        Generate via <strong style="color: var(--text-primary);">@BotFather</strong> on Telegram.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="telegram_admin_chat_id">Admin Telegram Chat ID</label>
                    <input type="text" id="telegram_admin_chat_id" name="telegram_admin_chat_id" class="form-control" 
                           value="<?= Security::escape($settings['telegram_admin_chat_id'] ?? '') ?>" 
                           placeholder="e.g. 123456789" spellcheck="false">
                    <div class="settings-field-hint">
                        Instant notifications for every PayPal sale, instant card delivery, and alerts will be sent directly to this Chat ID.
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="settings-sticky-footer">
            <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                <i class="fa-solid fa-shield-halved" style="color: var(--accent-green);"></i>
                <span>Changes will be applied immediately across all services upon saving.</span>
            </div>
            <button type="submit" class="btn-utility cyan" style="padding: 10px 24px; font-size: 13px;">
                <i class="fa-solid fa-floppy-disk"></i> Save System Configuration
            </button>
        </div>
    </form>

    <!-- TAB 4: Diagnostics & Tools (Separated from form to prevent accidental submit) -->
    <div class="settings-tab-pane" id="tab-diagnostics">
        <div class="panel-surface">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-satellite-dish" style="color: var(--accent-cyan);"></i>
                    <span>Bot Webhook & Connectivity Hub</span>
                </div>
            </div>

            <p style="font-size: 12px; color: var(--text-secondary); line-height: 1.6;">
                Tactical diagnostic tools to test bot token validity, register production Telegram Webhook, or switch to CLI long-polling mode.
            </p>

            <div class="diag-action-grid">
                <div class="diag-action-card">
                    <h5><i class="fa-solid fa-satellite" style="color: var(--accent-cyan);"></i> Test Bot Connection</h5>
                    <p>Pings Telegram Bot API servers using your configured bot token to verify authentication status.</p>
                    <a href="/admin/settings/test-bot" class="btn-utility" style="justify-content: center;">
                        <i class="fa-solid fa-plug-circle-bolt"></i> Run Ping Test
                    </a>
                </div>

                <div class="diag-action-card">
                    <h5><i class="fa-solid fa-link" style="color: var(--accent-green);"></i> Register Webhook</h5>
                    <p>Sets the live Telegram webhook URL for zero-latency incoming messages on public HTTPS domains.</p>
                    <a href="/admin/settings/set-webhook" class="btn-utility cyan" style="justify-content: center;">
                        <i class="fa-solid fa-globe"></i> Set Webhook
                    </a>
                </div>

                <div class="diag-action-card">
                    <h5><i class="fa-solid fa-link-slash" style="color: var(--accent-amber);"></i> Polling Mode (Local)</h5>
                    <p>Removes the active webhook from Telegram so you can run the bot worker locally via command line.</p>
                    <a href="/admin/settings/delete-webhook" class="btn-utility" style="justify-content: center;" onclick="return confirm('Remove webhook and switch to polling?');">
                        <i class="fa-solid fa-trash-can"></i> Delete Webhook
                    </a>
                </div>
            </div>

            <div style="margin-top: 18px; border-top: 1px solid var(--hairline); padding-top: 16px;">
                <div class="panel-title" style="font-size: 13px; margin-bottom: 8px;">
                    <i class="fa-solid fa-terminal" style="color: var(--accent-green);"></i>
                    <span>Local CLI Bot Worker Command</span>
                </div>
                <p style="font-size: 12px; color: var(--text-secondary); line-height: 1.6;">
                    When developing locally on <code>localhost</code> without a public SSL URL, execute this long-polling worker in your terminal:
                </p>
                <div class="settings-code-box">
                    <span class="settings-code-text">php bot_worker.php</span>
                    <button type="button" class="btn-copy-code" data-copy="php bot_worker.php">
                        <i class="fa-solid fa-copy"></i> Copy Command
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
