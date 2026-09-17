<?php
use App\Core\Security;
?>

<div class="panel-surface">
    <div class="panel-header">
        <div class="panel-title">
            <i class="fa-solid fa-cart-shopping" style="color: var(--accent-cyan);"></i>
            <span>Customer Orders Ledger</span>
        </div>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Product</th>
                    <th>Buyer Info</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Delivery</th>
                    <th>Delivered Data</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">No customer orders found in the database.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <strong style="font-family: var(--font-mono); color: var(--accent-cyan);">
                                    #<?= Security::escape($o['order_number']) ?>
                                </strong>
                                <?php if (!empty($o['paypal_order_id'])): ?>
                                    <div style="font-family: var(--font-mono); font-size: 10px; color: var(--text-muted);">
                                        PP: <?= Security::escape(substr($o['paypal_order_id'], 0, 15)) ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= Security::escape($o['product_name']) ?></td>
                            <td>
                                <div style="font-size: 12px; font-weight: 500; color: #fff;"><?= Security::escape($o['buyer_email']) ?></div>
                                <?php if (!empty($o['telegram_user_id'])): ?>
                                    <div style="font-family: var(--font-mono); font-size: 10px; color: #38bdf8;">
                                        <i class="fa-brands fa-telegram"></i> TG ID: <?= Security::escape($o['telegram_user_id']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: var(--font-mono); font-weight: 700; color: #4ade80;">
                                $<?= number_format((float)$o['amount'], 2) ?> <?= Security::escape($o['currency']) ?>
                            </td>
                            <td>
                                <span class="badge <?= $o['payment_status'] ?>"><?= $o['payment_status'] ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $o['delivery_status'] ?>"><?= $o['delivery_status'] ?></span>
                            </td>
                            <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: var(--font-mono); font-size: 11px;">
                                <?= Security::escape($o['delivered_content'] ?? '—') ?>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                                <?= date('Y-m-d H:i', strtotime($o['created_at'])) ?>
                            </td>
                            <td>
                                <a href="/admin/orders/fulfill/<?= $o['id'] ?>" class="btn-utility" onclick="return confirm('Re-trigger fulfillment for this order?');" title="Fulfill / Resend Delivery">
                                    <i class="fa-solid fa-rotate-right"></i> Fulfill
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
