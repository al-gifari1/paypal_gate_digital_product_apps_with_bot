<?php
use App\Core\Security;
?>

<!-- KPI Banner -->
<div class="kpi-row">
    <div class="kpi-tile green">
        <span class="kpi-label"><i class="fa-solid fa-sack-dollar"></i> Total Revenue</span>
        <span class="kpi-value green">$<?= number_format($totalRevenue, 2) ?></span>
        <span class="kpi-sub">PayPal Business Net</span>
    </div>

    <div class="kpi-tile">
        <span class="kpi-label"><i class="fa-solid fa-cart-check"></i> Completed Orders</span>
        <span class="kpi-value"><?= $completedOrders ?></span>
        <span class="kpi-sub">Delivered digital units</span>
    </div>

    <div class="kpi-tile amber">
        <span class="kpi-label"><i class="fa-solid fa-boxes-stacked"></i> Active Products</span>
        <span class="kpi-value amber"><?= $activeProducts ?></span>
        <span class="kpi-sub">Live in store & bot</span>
    </div>

    <div class="kpi-tile purple">
        <span class="kpi-label"><i class="fa-solid fa-key"></i> Available Keys/Cards</span>
        <span class="kpi-value"><?= $availableCards ?></span>
        <span class="kpi-sub">Ready in inventory</span>
    </div>
</div>

<!-- Main Dual Pane Grid -->
<div class="dashboard-grid">
    <!-- Recent Orders Panel -->
    <div class="panel-surface">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent-cyan);"></i>
                <span>Recent Live Orders</span>
            </div>
            <a href="/admin/orders" class="btn-utility">View All</a>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Delivery</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No orders recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ord): ?>
                            <tr>
                                <td><strong style="font-family: var(--font-mono); color: var(--accent-cyan);">#<?= Security::escape($ord['order_number']) ?></strong></td>
                                <td><?= Security::escape($ord['product_name']) ?></td>
                                <td style="font-family: var(--font-mono);">$<?= number_format((float)$ord['amount'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $ord['payment_status'] ?>"><?= $ord['payment_status'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $ord['delivery_status'] ?>"><?= $ord['delivery_status'] ?></span>
                                </td>
                                <td style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                                    <?= date('M d, H:i', strtotime($ord['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent PayPal Transactions -->
    <div class="panel-surface">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-brands fa-paypal" style="color: var(--accent-blue);"></i>
                <span>PayPal Capture Log</span>
            </div>
            <a href="/admin/transactions" class="btn-utility">Transactions</a>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>PayPal ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 24px;">No transactions logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTransactions as $t): ?>
                            <tr>
                                <td style="font-family: var(--font-mono); font-size: 11px;">
                                    <?= Security::escape(substr($t['paypal_order_id'] ?? 'N/A', 0, 14)) ?>...
                                </td>
                                <td style="font-family: var(--font-mono); color: #4ade80;">
                                    +$<?= number_format((float)$t['amount'], 2) ?>
                                </td>
                                <td>
                                    <span class="badge <?= strtolower($t['status']) ?>"><?= Security::escape($t['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
