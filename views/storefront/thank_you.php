<?php
use App\Core\Security;
use App\Services\SettingsService;
?>

<div class="detail-panel" style="max-width: 650px; margin: 0 auto; text-align: center;">
    <div style="width: 56px; height: 56px; background: #064e3b; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #34d399; font-size: 26px; box-shadow: 0 0 16px rgba(52, 211, 153, 0.4);">
        <i class="fa-solid fa-circle-check"></i>
    </div>

    <div>
        <h1 style="font-family: var(--font-display); font-size: 26px; color: #fff; line-height: 1.1;">
            PAYMENT CONFIRMED & FULFILLED!
        </h1>
        <p style="color: var(--text-muted); font-size: 13px; margin-top: 6px;">
            Thank you for your purchase. Your digital goods have been released below.
        </p>
    </div>

    <!-- Receipt Card -->
    <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-btn); text-align: left; display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--hairline); padding-bottom: 8px;">
            <span style="font-size: 12px; color: var(--text-muted);">Order Number:</span>
            <span style="font-family: var(--font-mono); font-weight: 700; color: var(--accent);">
                #<?= Security::escape($order['order_number']) ?>
            </span>
        </div>

        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--hairline); padding-bottom: 8px;">
            <span style="font-size: 12px; color: var(--text-muted);">Item Purchased:</span>
            <span style="font-weight: 600; color: #fff;"><?= Security::escape($order['product_name']) ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--hairline); padding-bottom: 8px;">
            <span style="font-size: 12px; color: var(--text-muted);">Amount Paid:</span>
            <span style="font-family: var(--font-mono); font-weight: 700; color: #4ade80;">
                $<?= number_format((float)$order['amount'], 2) ?> <?= Security::escape($order['currency']) ?>
            </span>
        </div>

        <div style="display: flex; justify-content: space-between;">
            <span style="font-size: 12px; color: var(--text-muted);">Delivery Recipient:</span>
            <span style="font-size: 12px; color: #fff;"><?= Security::escape($order['buyer_email']) ?></span>
        </div>
    </div>

    <!-- Fulfillment Deliverable Box -->
    <div style="background: #0f172a; border: 1px solid var(--hairline); padding: 20px; border-radius: var(--radius-btn); text-align: left; display: flex; flex-direction: column; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 12px; font-weight: 700; color: #fff; text-transform: uppercase;">
            <i class="fa-solid fa-gift" style="color: #facc15;"></i> Your Digital Delivery
        </div>

        <?php if ($order['product_type'] === 'card_license'): ?>
            <div style="background: #0b1120; padding: 14px; border-radius: var(--radius-btn); position: relative;">
                <pre id="licenseData" style="font-family: var(--font-mono); font-size: 13px; color: #4ade80; white-space: pre-wrap; word-break: break-all; margin: 0;"><?= Security::escape($order['delivered_content']) ?></pre>
            </div>
            <button type="button" onclick="copyDelivery()" class="nav-btn" style="align-self: flex-start;">
                <i class="fa-solid fa-copy"></i> Copy License Data
            </button>

        <?php else: ?>
            <?php if (!empty($downloadToken)): ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="<?= url('/download/' . Security::escape($downloadToken)) ?>" class="nav-btn green" style="justify-content: center; padding: 14px; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-download"></i> Download Purchased File
                    </a>
                    <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">
                        ⚠️ Security Notice: This download link is cryptographically signed and expires in 24 hours (max 3 downloads).
                    </div>
                </div>
            <?php else: ?>
                <div style="color: var(--text-muted); font-size: 13px;">
                    <?= Security::escape($order['delivered_content'] ?: 'Download instructions have been dispatched.') ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div style="display: flex; justify-content: center; gap: 12px; margin-top: 10px;">
        <a href="<?= url('/') ?>" class="nav-btn">
            <i class="fa-solid fa-house"></i> Return to Store
        </a>
    </div>
</div>

<script>
function copyDelivery() {
    const text = document.getElementById('licenseData').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}
</script>
