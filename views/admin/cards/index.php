<?php
use App\Core\Security;
use App\Core\Session;
?>

<div style="display: grid; grid-template-columns: 380px 1fr; gap: 16px;">
    <!-- Bulk Import Form -->
    <div class="panel-surface">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-file-import" style="color: var(--accent-cyan);"></i>
                <span>Bulk Import Keys/Cards</span>
            </div>
        </div>

        <form method="POST" action="/admin/cards/import">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="product_id">Select Target Product *</label>
                <select id="product_id" name="product_id" class="form-control" required>
                    <option value="">-- Choose Card Product --</option>
                    <?php foreach ($cardProducts as $cp): ?>
                        <option value="<?= $cp['id'] ?>" <?= $selectedProduct === $cp['id'] ? 'selected' : '' ?>>
                            <?= Security::escape($cp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="keys_raw">Paste Keys / Serials / Accounts *</label>
                <textarea id="keys_raw" name="keys_raw" class="form-control" style="min-height: 180px; font-family: var(--font-mono); font-size: 12px;" required placeholder="XXXX-YYYY-ZZZZ-1111&#10;XXXX-YYYY-ZZZZ-2222&#10;user:password"></textarea>
                <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
                    Separate each key on a new line. Empty lines are ignored.
                </span>
            </div>

            <button type="submit" class="btn-utility cyan" style="width: 100%; justify-content: center; margin-top: 10px;">
                <i class="fa-solid fa-plus-circle"></i> Add to Pool Inventory
            </button>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="panel-surface">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-id-card" style="color: var(--accent-purple);"></i>
                <span>License Inventory (<?= count($cards) ?> items)</span>
            </div>

            <!-- Product Filter -->
            <form method="GET" action="/admin/cards" class="table-filter-form">
                <select name="product_id" class="form-control table-filter-select" onchange="this.form.submit()">
                    <option value="">All Products</option>
                    <?php foreach ($cardProducts as $cp): ?>
                        <option value="<?= $cp['id'] ?>" <?= $selectedProduct === $cp['id'] ? 'selected' : '' ?>>
                            <?= Security::escape($cp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Card / License Data</th>
                        <th>Status</th>
                        <th>Assigned Order</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cards)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No card keys found in inventory for this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cards as $c): ?>
                            <tr>
                                <td style="font-family: var(--font-mono); color: var(--text-muted);">#<?= $c['id'] ?></td>
                                <td><?= Security::escape($c['product_name']) ?></td>
                                <td style="font-family: var(--font-mono); font-size: 12px; color: #fff; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <code><?= Security::escape($c['card_data']) ?></code>
                                </td>
                                <td>
                                    <span class="badge <?= $c['status'] ?>"><?= $c['status'] ?></span>
                                </td>
                                <td style="font-family: var(--font-mono); font-size: 11px;">
                                    <?php if (!empty($c['order_number'])): ?>
                                        <a href="/admin/orders" style="color: var(--accent-cyan); text-decoration: none;">
                                            #<?= Security::escape($c['order_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'available'): ?>
                                        <a href="/admin/cards/delete/<?= $c['id'] ?>" class="btn-utility danger" onclick="return confirm('Remove this key from pool?');" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 11px;">Assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
