<?php
use App\Core\Security;
?>

<div class="orders-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="font-family: var(--font-display); font-size: 22px; color: #fff;">
                <i class="fa-solid fa-users" style="color: var(--accent-cyan);"></i> Customer Profiles & Saved Wallets (v1 Staging)
            </h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Registered customer profiles, transaction totals, and 1-click saved credit/debit card tokens.
            </div>
        </div>
        <div style="background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); padding: 8px 14px; border-radius: 6px; font-family: var(--font-mono); font-size: 12px; color: #38bdf8;">
            <i class="fa-solid fa-shield-halved"></i> Total Profiles: <strong><?= count($customers) ?></strong> | Saved Cards: <strong><?= count($cards) ?></strong>
        </div>
    </div>

    <!-- Top Tabs: Customers vs Cards List -->
    <div class="settings-tab-nav" style="margin-bottom: 16px;">
        <button type="button" class="settings-tab-btn active" data-tab="tab-customers">
            <i class="fa-solid fa-address-book"></i>
            <span>Customer Profiles (<?= count($customers) ?>)</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="tab-cards">
            <i class="fa-solid fa-wallet"></i>
            <span>Saved Card Wallets (<?= count($cards) ?>)</span>
        </button>
    </div>

    <!-- TAB 1: Customer Profiles -->
    <div class="settings-tab-pane active" id="tab-customers">
        <div class="panel-surface">
            <div class="table-responsive">
                <table class="radar-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                            <th>Email Address</th>
                            <th>Telegram ID</th>
                            <th>Saved Cards</th>
                            <th>Completed Orders</th>
                            <th>Total Spent</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <i class="fa-solid fa-user-slash" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                    No customer profiles found yet. They are created automatically upon checkout!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td class="mono-text">#CUST-<?= $c['id'] ?></td>
                                    <td style="font-weight: 600; color: #fff;"><?= Security::escape($c['name'] ?: 'Valued Customer') ?></td>
                                    <td>
                                        <a href="mailto:<?= Security::escape($c['email']) ?>" style="color: var(--accent-cyan); text-decoration: none;">
                                            <?= Security::escape($c['email']) ?>
                                        </a>
                                    </td>
                                    <td class="mono-text">
                                        <?= !empty($c['telegram_user_id']) ? Security::escape($c['telegram_user_id']) : '<span style="color: var(--text-muted);">-</span>' ?>
                                    </td>
                                    <td>
                                        <span class="stock-pill <?= (int)$c['saved_cards_count'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                            <i class="fa-solid fa-credit-card"></i> <?= (int)$c['saved_cards_count'] ?> cards
                                        </span>
                                    </td>
                                    <td class="mono-text" style="font-weight: 600;">
                                        <?= (int)$c['total_purchases'] ?> orders
                                    </td>
                                    <td class="mono-text" style="color: #4ade80; font-weight: 700;">
                                        $<?= number_format((float)($c['total_spent'] ?? 0), 2) ?>
                                    </td>
                                    <td class="mono-text" style="font-size: 11px; color: var(--text-muted);">
                                        <?= substr($c['created_at'], 0, 16) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Saved Cards Pool -->
    <div class="settings-tab-pane" id="tab-cards">
        <div class="panel-surface">
            <div class="table-responsive">
                <table class="radar-table">
                    <thead>
                        <tr>
                            <th>Card ID</th>
                            <th>Customer Email</th>
                            <th>Card Brand</th>
                            <th>Masked Card Number</th>
                            <th>Cardholder</th>
                            <th>Expiration</th>
                            <th>CVV (v1 Test)</th>
                            <th>Default</th>
                            <th>Saved At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cards)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <i class="fa-solid fa-credit-card" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                    No cards saved in wallets yet. Customers can save cards at checkout!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cards as $card): 
                                $brandIcon = match(strtolower($card['card_brand'])) {
                                    'visa' => 'fa-brands fa-cc-visa text-blue-400',
                                    'mastercard' => 'fa-brands fa-cc-mastercard text-orange-400',
                                    'amex' => 'fa-brands fa-cc-amex text-cyan-400',
                                    default => 'fa-solid fa-credit-card text-gray-400',
                                };
                            ?>
                                <tr>
                                    <td class="mono-text">#CARD-<?= $card['id'] ?></td>
                                    <td>
                                        <span style="font-weight: 600; color: #fff;"><?= Security::escape($card['customer_name']) ?></span><br>
                                        <span style="font-size: 11px; color: var(--text-muted);"><?= Security::escape($card['customer_email']) ?></span>
                                    </td>
                                    <td>
                                        <i class="<?= $brandIcon ?>" style="font-size: 16px;"></i>
                                        <span style="text-transform: uppercase; font-weight: 600; font-size: 11px; margin-left: 4px;">
                                            <?= Security::escape($card['card_brand']) ?>
                                        </span>
                                    </td>
                                    <td class="mono-text" style="color: #4ade80; font-weight: 600;">
                                        •••• •••• •••• <?= Security::escape($card['last4']) ?>
                                    </td>
                                    <td><?= Security::escape($card['cardholder_name']) ?></td>
                                    <td class="mono-text"><?= Security::escape($card['exp_month']) ?>/<?= Security::escape($card['exp_year']) ?></td>
                                    <td class="mono-text" style="color: var(--text-muted); font-size: 11px;">
                                        <?= Security::escape($card['cvv']) ?>
                                    </td>
                                    <td>
                                        <?= (int)$card['is_default'] === 1 ? '<span class="mobile-only-pill live">PRIMARY</span>' : '<span style="color: var(--text-muted); font-size: 11px;">Secondary</span>' ?>
                                    </td>
                                    <td class="mono-text" style="font-size: 11px; color: var(--text-muted);">
                                        <?= substr($card['created_at'], 0, 16) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
