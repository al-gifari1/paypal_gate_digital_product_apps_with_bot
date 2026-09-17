<?php
use App\Core\Security;
use App\Core\Session;
?>

<div class="detail-panel" style="max-width: 650px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <a href="<?= url('/product/' . Security::escape($product['slug'])) ?>" class="nav-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to Product
        </a>
        <span style="font-family: var(--font-mono); font-size: 11px; color: var(--accent);">
            <i class="fa-solid fa-lock"></i> SSL Secured PayPal Gateway
        </span>
    </div>

    <?php if ($errorMsg = Session::flash('error')): ?>
        <div style="background: #7f1d1d; color: #f87171; padding: 12px 16px; border-radius: 6px; font-weight: 500;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= Security::escape($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Summary Box -->
    <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-btn); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted); text-transform: uppercase;">Order Item</div>
            <h2 style="font-family: var(--font-display); font-size: 20px; color: #fff; margin-top: 2px;">
                <?= Security::escape($product['name']) ?>
            </h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Type: <?= $product['product_type'] === 'card_license' ? 'Digital License / Card' : 'Downloadable Asset' ?>
            </div>
        </div>

        <div style="text-align: right;">
            <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted); text-transform: uppercase;">Total Due</div>
            <div style="font-family: var(--font-mono); font-size: 24px; font-weight: 700; color: #4ade80;">
                $<?= number_format((float)$product['price'], 2) ?>
            </div>
            <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);"><?= Security::escape($product['currency']) ?></div>
        </div>
    </div>

    <!-- Checkout Form -->
    <form method="POST" action="<?= url('/checkout/process/' . Security::escape($product['slug'])) ?>">
        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="buyer_email">
                    Delivery Email Address *
                </label>
                <input type="email" id="buyer_email" name="buyer_email" class="form-input" required placeholder="name@example.com">
                <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); margin-top: 4px; display: block;">
                    Your license details & download access links will be associated with this email.
                </span>
            </div>

            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="buyer_name">
                    Full Name (Optional)
                </label>
                <input type="text" id="buyer_name" name="buyer_name" class="form-input" placeholder="John Doe">
            </div>

            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;" for="telegram_user_id">
                    Telegram User ID (Optional for Direct Bot Delivery)
                </label>
                <input type="text" id="telegram_user_id" name="telegram_user_id" class="form-input" value="<?= Security::escape($tgUserId) ?>" placeholder="e.g. 123456789">
                <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); margin-top: 4px; display: block;">
                    If entered, the bot will automatically message you the digital file or key in Telegram!
                </span>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="nav-btn primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 15px; font-weight: 700; background: #0070ba;">
                    <i class="fa-brands fa-paypal" style="font-size: 18px;"></i> Continue to PayPal Checkout &bull; $<?= number_format((float)$product['price'], 2) ?>
                </button>
            </div>
        </div>
    </form>

    <div style="text-align: center; font-size: 11px; font-family: var(--font-mono); color: var(--text-muted); margin-top: 8px;">
        <i class="fa-solid fa-lock"></i> Transactions are securely authorized by PayPal. You will return here for instant fulfillment.
    </div>
</div>
