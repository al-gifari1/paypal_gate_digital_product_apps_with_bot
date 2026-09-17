<?php
use App\Core\Security;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
?>

<div class="detail-panel">
    <div class="subpage-header">
        <div class="subpage-title-wrap">
            <span class="hero-tag">Brand Philosophy &amp; Architecture</span>
            <h1 class="subpage-title">
                ABOUT <?= Security::escape($siteTitle) ?>
            </h1>
        </div>
        <a href="/" class="nav-btn subpage-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Storefront
        </a>
    </div>

    <!-- Core Identity Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
        <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-btn); display: flex; flex-direction: column; gap: 8px;">
            <div style="font-size: 22px; color: var(--accent);"><i class="fa-solid fa-bolt-lightning"></i></div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff;">Instant Fulfillment</h3>
            <p style="color: var(--text-muted); font-size: 13px; line-height: 1.6;">
                Zero wait times. The moment PayPal verifies your transaction, our backend instantly assigns and delivers your digital license or temporary download link.
            </p>
        </div>

        <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-btn); display: flex; flex-direction: column; gap: 8px;">
            <div style="font-size: 22px; color: var(--accent-green);"><i class="fa-brands fa-paypal"></i></div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff;">PayPal Business Verified</h3>
            <p style="color: var(--text-muted); font-size: 13px; line-height: 1.6;">
                All payments are processed securely through PayPal's modern REST API v2 with SSL encryption and buyer protection standards.
            </p>
        </div>

        <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-btn); display: flex; flex-direction: column; gap: 8px;">
            <div style="font-size: 22px; color: #38bdf8;"><i class="fa-brands fa-telegram"></i></div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff;">Dual-Platform Bot Engine</h3>
            <p style="color: var(--text-muted); font-size: 13px; line-height: 1.6;">
                Interact seamlessly via our web storefront or through our automated Telegram Bot with inline command and Mini App capabilities.
            </p>
        </div>
    </div>

    <!-- Mission Text -->
    <div style="background: var(--bg-card); padding: 22px; border-radius: var(--radius-btn); line-height: 1.8; color: var(--text-main); font-size: 14px;">
        <h3 style="font-family: var(--font-display); font-size: 18px; color: #fff; margin-bottom: 12px; text-transform: uppercase;">
            Engineered for Precision &amp; Reliability
        </h3>
        <p>
            <?= Security::escape($siteTitle) ?> was conceived as an enterprise-grade digital distribution infrastructure. We eliminate bloated checkout funnels and unnecessary intermediaries. Whether you are purchasing developer tools, algorithmic bots, API credentials, or software licenses, our automated dispatch pipeline ensures maximum speed and military-grade asset isolation.
        </p>
        <p style="margin-top: 12px;">
            Every file download link generated is cryptographically signed and rate-limited to protect our digital assets, and all digital serials are uniquely allocated in a strictly audited pool.
        </p>
    </div>
</div>
