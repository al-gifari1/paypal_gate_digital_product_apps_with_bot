<?php
use App\Core\Security;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
?>

<div class="detail-panel">
    <div class="subpage-header">
        <div class="subpage-title-wrap">
            <span class="hero-tag">Legal Terms &amp; Conditions</span>
            <h1 class="subpage-title">
                TERMS OF SERVICE
            </h1>
        </div>
        <a href="/" class="nav-btn subpage-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Storefront
        </a>
    </div>

    <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-btn); color: var(--text-main); line-height: 1.8; font-size: 13px; display: flex; flex-direction: column; gap: 18px;">
        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                1. Agreement to Terms
            </h3>
            <p>
                By accessing or purchasing digital goods from <?= Security::escape($siteTitle) ?>, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you must not use this service.
            </p>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                2. Digital Goods &amp; License Grant
            </h3>
            <p>
                All software source packs, license keys, and downloadable files available on this platform are intangible digital assets. Upon completed payment verification:
            </p>
            <ul style="margin-left: 20px; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px;">
                <li>You are granted a non-exclusive, non-transferable, revocable license for personal or business use as specified in the product documentation.</li>
                <li>You may not redistribute, resell, sub-license, or publicly leak digital keys or download archives.</li>
                <li>Digital card serials are intended for single-account activation unless explicitly marketed as multi-seat.</li>
            </ul>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                3. Automated Delivery &amp; Expiration
            </h3>
            <p>
                Digital download tokens are cryptographically generated and expire within 24 hours of purchase, limited to a maximum of 3 download attempts to prevent automated link scraping. Buyers are responsible for backing up their acquired files promptly upon initial delivery.
            </p>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                4. Fraud Prevention &amp; Account Protection
            </h3>
            <p>
                Any attempt to conduct unauthorized transactions, chargeback fraud, or system disruption will result in immediate blacklisting from our Telegram bot and revocation of all associated digital licenses.
            </p>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                5. Limitation of Liability
            </h3>
            <p>
                <?= Security::escape($siteTitle) ?> provides all digital materials "as is" without warranties of any kind, whether express or implied. Under no circumstances will our team be liable for indirect, incidental, or consequential damages resulting from the use of our software.
            </p>
        </div>
    </div>
</div>
