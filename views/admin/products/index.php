<?php
use App\Core\Security;
?>

<div class="panel-surface">
    <div class="panel-header">
        <div class="panel-title">
            <i class="fa-solid fa-box-archive" style="color: var(--accent-cyan);"></i>
            <span>Digital Products Catalog</span>
        </div>
        <a href="/admin/products/create" class="btn-utility cyan">
            <i class="fa-solid fa-plus"></i> Add New Product
        </a>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Stock / Inventory</th>
                    <th>Sales</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No digital products created yet. Click "Add New Product" to create your first item!</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="font-family: var(--font-mono); color: var(--text-muted);">#<?= $p['id'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if (!empty($p['image_path'])): ?>
                                        <img src="<?= url(Security::escape($p['image_path'])) ?>" alt="" style="width: 44px; height: 32px; object-fit: cover; border-radius: 4px; background: var(--surface-card); flex-shrink: 0;">
                                    <?php else: ?>
                                        <div style="width: 44px; height: 32px; border-radius: 4px; background: var(--surface-card); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 14px; flex-shrink: 0;">
                                            <i class="fa-solid <?= $p['product_type'] === 'card_license' ? 'fa-id-card' : 'fa-box' ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong style="color: #fff; font-size: 13px;"><?= Security::escape($p['name']) ?></strong>
                                        <div style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                                            slug: /checkout/<?= Security::escape($p['slug']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($p['product_type'] === 'card_license'): ?>
                                    <span class="badge" style="background:#3b0764; color:#d8b4fe;"><i class="fa-solid fa-key"></i> Card / License</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#083344; color:#67e8f9;"><i class="fa-solid fa-file-arrow-down"></i> File Download</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: var(--font-mono); font-weight: 600; font-size: 13px; color: #4ade80;">
                                $<?= number_format((float)$p['price'], 2) ?> <?= Security::escape($p['currency']) ?>
                            </td>
                            <td style="font-family: var(--font-mono);">
                                <?php if ($p['product_type'] === 'card_license'): ?>
                                    <span style="color: <?= $p['available_cards'] > 0 ? '#4ade80' : '#f87171' ?>; font-weight: 700;">
                                        <?= $p['available_cards'] ?> keys available
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">
                                        <?= !empty($p['file_path']) ? 'File attached' : 'No file attached' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: var(--font-mono);"><?= (int)$p['sales_count'] ?></td>
                            <td>
                                <span class="badge <?= $p['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Draft' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="/admin/products/toggle/<?= $p['id'] ?>" class="btn-utility" title="Toggle Active">
                                        <i class="fa-solid fa-power-off"></i>
                                    </a>
                                    <a href="/checkout/<?= Security::escape($p['slug']) ?>" target="_blank" class="btn-utility" title="View Storefront">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="/admin/products/delete/<?= $p['id'] ?>" class="btn-utility danger" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete Product">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
