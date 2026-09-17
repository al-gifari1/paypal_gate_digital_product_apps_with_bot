<?php
use App\Core\Security;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
?>

<div class="detail-panel">
    <div class="subpage-header">
        <div class="subpage-title-wrap">
            <span class="hero-tag">Compliance &amp; Data Security</span>
            <h1 class="subpage-title">
                PRIVACY POLICY
            </h1>
        </div>
        <a href="/" class="nav-btn subpage-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Storefront
        </a>
    </div>

    <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-btn); color: var(--text-main); line-height: 1.8; font-size: 13px; display: flex; flex-direction: column; gap: 18px;">
        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                1. Commitment to Privacy
            </h3>
            <p>
                At <?= Security::escape($siteTitle) ?>, protecting the personal data of our customers is a fundamental operational standard. This Privacy Policy details how we collect, process, and safeguard information when you interact with our web storefront, purchase digital goods, or communicate with our Telegram bot.
            </p>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                2. Information We Collect
            </h3>
            <p>We collect only the minimal data points necessary to process payments and fulfill digital goods:</p>
            <ul style="margin-left: 20px; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px;">
                <li><strong>Contact Information:</strong> Buyer email address and optional name provided at checkout.</li>
                <li><strong>Transaction Identifiers:</strong> PayPal Order ID, Capture ID, transaction amount, and timestamp.</li>
                <li><strong>Telegram User ID:</strong> If you use our Telegram Bot or supply your Telegram ID at checkout for automated direct delivery.</li>
                <li><strong>Technical Data:</strong> IP address and download attempt counters for rate-limiting expiring asset tokens.</li>
            </ul>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                3. Payment Security (Zero Card Storage)
            </h3>
            <p>
                We do <strong>not</strong> collect, process, or store credit card numbers, debit cards, or financial bank credentials on our servers. All financial checkout transactions are securely transmitted and processed directly through <strong>PayPal Business Gateway</strong> using 256-bit TLS/SSL encryption.
            </p>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                4. Local Storage &amp; Cookies
            </h3>
            <p>
                We use secure session cookies strictly for anti-CSRF protection and essential navigation. We also utilize browser <code>localStorage</code> solely to remember your preferred catalog display mode (Grid vs. List View). No third-party tracking or advertising cookies are utilized.
            </p>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                5. Data Retention &amp; Rights
            </h3>
            <p>
                Order and transaction records are retained in compliance with commercial accounting standards and PayPal seller verification rules. You may request verification or deletion of non-accounting contact records by reaching out through our <a href="/contact" style="color: var(--accent); text-decoration: none;">Help Desk</a>.
            </p>
        </div>
    </div>
</div>
