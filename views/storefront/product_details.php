<?php
use App\Core\Security;
?>

<div class="detail-panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <a href="<?= url('/') ?>" class="nav-btn">
            <i class="fa-solid fa-arrow-left"></i> All Products
        </a>

        <?php if ($product['product_type'] === 'card_license'): ?>
            <span class="product-type-badge type-card">
                <i class="fa-solid fa-key"></i> Digital License / Card
            </span>
        <?php else: ?>
            <span class="product-type-badge type-file">
                <i class="fa-solid fa-file-arrow-down"></i> Downloadable File
            </span>
        <?php endif; ?>
    </div>

    <div class="detail-header">
        <h1 style="font-family: var(--font-display); font-size: 26px; color: #fff; line-height: 1.2;">
            <?= Security::escape($product['name']) ?>
        </h1>
        <div style="font-size: 12px; color: var(--text-muted); font-family: var(--font-mono);">
            Delivery: Instant automated fulfillment via PayPal
        </div>
    </div>

    <?php if (!empty($product['image_path'])): ?>
        <div>
            <img src="<?= url(Security::escape($product['image_path'])) ?>" alt="<?= Security::escape($product['name']) ?>" class="detail-hero-img">
        </div>
    <?php endif; ?>

    <div class="detail-price-box">
        <div>
            <div style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted); text-transform: uppercase;">Official Price</div>
            <div style="font-family: var(--font-mono); font-size: 28px; font-weight: 700; color: #4ade80;">
                $<?= number_format((float)$product['price'], 2) ?> <span style="font-size: 14px;"><?= Security::escape($product['currency']) ?></span>
            </div>
        </div>

        <a href="<?= url('/checkout/' . Security::escape($product['slug'])) ?>" class="nav-btn primary" style="padding: 10px 20px; font-size: 14px;">
            <i class="fa-brands fa-paypal"></i> Proceed to Checkout
        </a>
    </div>

    <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-btn);">
        <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff; margin-bottom: 10px; text-transform: uppercase;">
            Product Specifications & Details
        </h3>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.7; white-space: pre-wrap;">
            <?= Security::escape($product['description'] ?: 'No additional description provided.') ?>
        </p>
    </div>

    <!-- Security Guarantee Badge -->
    <div style="display: flex; align-items: center; gap: 12px; padding: 14px; background: rgba(56, 189, 248, 0.08); border-radius: var(--radius-btn);">
        <i class="fa-solid fa-certificate" style="font-size: 24px; color: var(--accent);"></i>
        <div>
            <div style="font-weight: 600; color: #fff;">Buyer Protection & 100% Delivery Guarantee</div>
            <div style="font-size: 12px; color: var(--text-muted);">
                Payments are processed via PayPal. License keys or secure download tokens are presented on screen immediately upon authorization.
            </div>
        </div>
    </div>
</div>
