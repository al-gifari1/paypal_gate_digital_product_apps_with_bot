<?php
use App\Core\Security;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
?>

<div class="detail-panel">
    <div class="subpage-header">
        <div class="subpage-title-wrap">
            <span class="hero-tag">Buyer Guarantee</span>
            <h1 class="subpage-title">
                REFUND &amp; REPLACEMENT POLICY
            </h1>
        </div>
        <a href="/" class="nav-btn subpage-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Storefront
        </a>
    </div>

    <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-btn); color: var(--text-main); line-height: 1.8; font-size: 13px; display: flex; flex-direction: column; gap: 18px;">
        <div style="background: var(--bg-surface); padding: 16px; border-radius: var(--radius-btn); border-left: 3px solid var(--accent-green);">
            <strong style="color: #4ade80;">100% Quality &amp; Functionality Guarantee:</strong>
            <div style="color: var(--text-muted); margin-top: 4px;">
                Every digital product sold on <?= Security::escape($siteTitle) ?> is tested prior to listing. We provide free replacement keys or full refunds if a delivered digital asset is provably defective.
            </div>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                1. Conditions for Free Replacement or Full Refund
            </h3>
            <p>You are entitled to an immediate replacement key or full PayPal refund within 14 days of purchase under the following circumstances:</p>
            <ul style="margin-left: 20px; color: var(--text-muted); display: flex; flex-direction: column; gap: 6px;">
                <li><strong>Invalid or Expired Serial Key:</strong> If a delivered license card is non-working or already claimed upon receipt.</li>
                <li><strong>Corrupted File Archive:</strong> If a downloaded digital archive cannot be unpacked or verified and our team is unable to provide a functional replacement within 24 hours.</li>
                <li><strong>Out of Stock Delivery:</strong> If an order is marked as out of stock and you do not wish to wait for inventory replenishment.</li>
            </ul>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                2. Non-Refundable Circumstances
            </h3>
            <p>Because digital assets are irrevocable once decrypted and downloaded, refunds cannot be issued under:</p>
            <ul style="margin-left: 20px; color: var(--text-muted); display: flex; flex-direction: column; gap: 6px;">
                <li>Change of mind after successful file download or license activation.</li>
                <li>Incompatibility due to failure to review minimum technical requirements clearly listed on the product details page.</li>
                <li>Accounts banned by third-party services due to user misuse or violation of external provider terms.</li>
            </ul>
        </div>

        <div>
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase;">
                3. How to Request a Refund
            </h3>
            <p>
                To request support, please submit an inquiry via our <a href="/contact" style="color: var(--accent); text-decoration: none; font-weight: 600;">Contact Desk</a> or message our Telegram Bot. Please include:
            </p>
            <ol style="margin-left: 20px; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px;">
                <li>Your Order Number (e.g. <code>#ORD-XXXX</code>).</li>
                <li>PayPal Transaction / Capture ID.</li>
                <li>A concise description or screenshot of the error encountered.</li>
            </ol>
            <p style="margin-top: 8px;">
                Our team responds to all refund and key-replacement inquiries in under 4 hours during business coverage.
            </p>
        </div>
    </div>
</div>
