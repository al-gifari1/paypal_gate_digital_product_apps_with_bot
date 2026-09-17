<?php
use App\Core\Security;
use App\Core\Session;
use App\Services\SettingsService;

$siteTitle = SettingsService::get('site_title', 'Nexus Vault');
?>

<div class="detail-panel">
    <div class="subpage-header">
        <div class="subpage-title-wrap">
            <span class="hero-tag">Direct Communication</span>
            <h1 class="subpage-title">
                CUSTOMER SUPPORT &amp; HELP DESK
            </h1>
        </div>
        <a href="/" class="nav-btn subpage-back-btn">
            <i class="fa-solid fa-arrow-left"></i> Storefront
        </a>
    </div>

    <!-- Flash Alerts -->
    <?php if ($successMsg = Session::flash('success')): ?>
        <div style="background: #064e3b; color: #34d399; padding: 14px 18px; border-radius: var(--radius-btn); font-weight: 500; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= Security::escape($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg = Session::flash('error')): ?>
        <div style="background: #7f1d1d; color: #f87171; padding: 14px 18px; border-radius: var(--radius-btn); font-weight: 500; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= Security::escape($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <div class="contact-layout-grid">
        <!-- Contact Form Column -->
        <div class="contact-form-col">
            <form method="POST" action="/contact" style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="name">
                        Your Name *
                    </label>
                    <input type="text" id="name" name="name" class="form-input" required placeholder="Alex Turner">
                </div>

                <div>
                    <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="email">
                        Email Address *
                    </label>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="alex@example.com">
                    <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); margin-top: 4px; display: block;">
                        We will reply directly to this email address.
                    </span>
                </div>

                <div>
                    <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="subject">
                        Subject / Order Number
                    </label>
                    <input type="text" id="subject" name="subject" class="form-input" placeholder="e.g. Order #ORD-XXXX or General Query">
                </div>

                <div>
                    <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="message">
                        Your Message *
                    </label>
                    <textarea id="message" name="message" class="form-input" rows="5" required placeholder="Describe your question or issue in detail..."></textarea>
                </div>

                <button type="submit" class="form-submit-btn">
                    <i class="fa-solid fa-paper-plane"></i> Send Inquiry
                </button>
            </form>
        </div>

        <!-- Support Channels & Info Column -->
        <div class="contact-sidebar-col">
            <div class="contact-channel-card">
                <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; text-transform: uppercase; margin: 0;">
                    <i class="fa-solid fa-headset" style="color: var(--accent);"></i> Support Channels
                </h3>

                <div class="contact-channel-item">
                    <span class="contact-channel-label">Telegram Support Bot</span>
                    <div class="contact-channel-val">
                        <span>Available 24/7 Automated Dispatch</span>
                    </div>
                    <?php 
                    $botTokenSet = !empty(SettingsService::get('telegram_bot_token'));
                    if ($botTokenSet): 
                    ?>
                        <a href="https://t.me" target="_blank" class="channel-action-btn">
                            <i class="fa-brands fa-telegram" style="font-size: 14px;"></i> Launch Telegram Bot
                        </a>
                    <?php endif; ?>
                </div>

                <div class="contact-channel-item">
                    <span class="contact-channel-label">Average Response Time</span>
                    <div class="contact-channel-val" style="color: #4ade80;">
                        <i class="fa-solid fa-clock"></i> Under 4 Hours (&lt; 24h Guaranteed)
                    </div>
                </div>

                <div class="contact-channel-item">
                    <span class="contact-channel-label">Expedited Resolution</span>
                    <div style="color: var(--text-main); font-size: 13px; line-height: 1.5;">
                        Include your PayPal Transaction ID or Order Number in your inquiry for immediate matching.
                    </div>
                </div>
            </div>

            <div class="contact-notice-card">
                <div style="font-weight: 600; color: #fff; font-size: 13px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i>
                    <span>Verified Buyer Guarantee</span>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                    Before filing a PayPal dispute, please contact our support desk first. Most replacement keys, link regenerations, or technical inquiries are resolved within a few hours.
                </div>
            </div>
        </div>
    </div>
</div>
