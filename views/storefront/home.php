<?php
use App\Core\Security;
use App\Core\Session;
?>

<!-- Flash Alerts -->
<?php if ($errorMsg = Session::flash('error')): ?>
    <div style="background: #7f1d1d; color: #f87171; padding: 14px 18px; border-radius: 6px; margin-bottom: 16px; font-weight: 500; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?= Security::escape($errorMsg) ?></span>
    </div>
<?php endif; ?>

<!-- Hero Banner (Clean & Mobile-Tuned) -->
<div class="hero-banner">
    <span class="hero-tag">
        Instant Digital Delivery
    </span>
    <h1 class="hero-title">
        DIGITAL ASSETS &amp; LICENSES
    </h1>
    <p class="hero-desc">
        Verified software packages, algorithmic scripts, and license cards with automated PayPal fulfillment.
    </p>
</div>

<!-- Product Catalog Header & Controls -->
<section class="catalog-section">
    <div class="catalog-bar">
        <h2 class="catalog-title">
            AVAILABLE CATALOG (<?= count($products) ?>)
        </h2>

        <!-- View Mode Switcher: Grid Mode vs List Mode -->
        <div class="view-switcher" role="group" aria-label="Catalog View Mode">
            <button type="button" class="btn-view-toggle active" id="btnViewGrid" onclick="setCatalogView('grid')" title="Grid View">
                <i class="fa-solid fa-table-cells-large"></i> Grid
            </button>
            <button type="button" class="btn-view-toggle" id="btnViewList" onclick="setCatalogView('list')" title="List View">
                <i class="fa-solid fa-bars"></i> List
            </button>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div style="background: var(--bg-surface); border-radius: var(--radius-box); padding: 48px 24px; text-align: center; color: var(--text-muted); margin-top: 14px;">
            <i class="fa-solid fa-box-open" style="font-size: 36px; margin-bottom: 14px; color: var(--hairline-border);"></i>
            <p style="font-size: 14px;">Store inventory is currently being replenished. Please check back shortly!</p>
        </div>
    <?php else: ?>
        <!-- Product Catalog Container (defaults to mode-grid, toggles to mode-list) -->
        <div class="product-catalog mode-grid" id="productCatalogContainer">
            <?php foreach ($products as $p): ?>
                <?php
                    $imgSrc = !empty($p['image_path']) ? url(Security::escape($p['image_path'])) : null;
                    $isCard = $p['product_type'] === 'card_license';
                ?>
                <div class="product-card">
                    <!-- Thumbnail Container -->
                    <div class="product-thumb-wrap">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= Security::escape($p['name']) ?>" class="product-thumb-img" loading="lazy">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e293b, #0f172a); color: var(--text-muted); font-size: 32px;">
                                <i class="fa-solid <?= $isCard ? 'fa-id-card' : 'fa-box-archive' ?>"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Badge Overlay for Grid Mode -->
                        <div class="product-badge-overlay">
                            <?php if ($isCard): ?>
                                <span class="product-type-badge type-card">
                                    <i class="fa-solid fa-key"></i> License Card
                                </span>
                            <?php else: ?>
                                <span class="product-type-badge type-file">
                                    <i class="fa-solid fa-file-arrow-down"></i> File Asset
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Content & Metadata -->
                    <div class="product-content">
                        <div class="product-meta">
                            <!-- In List Mode, show type badge inline -->
                            <div class="product-list-badge" style="display: none;">
                                <?php if ($isCard): ?>
                                    <span class="product-type-badge type-card">
                                        <i class="fa-solid fa-key"></i> License Card
                                    </span>
                                <?php else: ?>
                                    <span class="product-type-badge type-file">
                                        <i class="fa-solid fa-file-arrow-down"></i> File Asset
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="product-name"><?= Security::escape($p['name']) ?></h3>
                            <p class="product-desc"><?= Security::escape($p['description'] ?? 'Instant automated delivery.') ?></p>
                        </div>

                        <!-- Footer Pricing & CTAs -->
                        <div class="product-footer">
                            <div>
                                <div class="product-price">$<?= number_format((float)$p['price'], 2) ?></div>
                                <div class="product-currency"><?= Security::escape($p['currency']) ?></div>
                            </div>

                            <div class="product-footer-actions" style="display: flex; gap: 8px;">
                                <a href="<?= url('/product/' . Security::escape($p['slug'])) ?>" class="nav-btn">
                                    Details
                                </a>
                                <a href="<?= url('/checkout/' . Security::escape($p['slug'])) ?>" class="nav-btn primary">
                                    <i class="fa-brands fa-paypal"></i> Buy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
function setCatalogView(mode) {
    const container = document.getElementById('productCatalogContainer');
    const btnGrid = document.getElementById('btnViewGrid');
    const btnList = document.getElementById('btnViewList');
    const listBadges = document.querySelectorAll('.product-list-badge');

    if (!container) return;

    if (mode === 'list') {
        container.classList.remove('mode-grid');
        container.classList.add('mode-list');
        btnList.classList.add('active');
        btnGrid.classList.remove('active');
        listBadges.forEach(b => b.style.display = 'inline-block');
        localStorage.setItem('store_view_mode', 'list');
    } else {
        container.classList.remove('mode-list');
        container.classList.add('mode-grid');
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
        listBadges.forEach(b => b.style.display = 'none');
        localStorage.setItem('store_view_mode', 'grid');
    }
}

// Restore user view preference on page load
document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('store_view_mode') || 'grid';
    setCatalogView(savedMode);
});
</script>
