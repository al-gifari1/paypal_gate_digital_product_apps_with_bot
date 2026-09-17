<?php
use App\Core\Security;
?>

<div class="panel-surface">
    <div class="panel-header">
        <div class="panel-title">
            <i class="fa-brands fa-paypal" style="color: var(--accent-blue);"></i>
            <span>PayPal Gateway Transactions</span>
        </div>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Associated Order</th>
                    <th>PayPal Order ID</th>
                    <th>Capture ID</th>
                    <th>Payer Email</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">No PayPal transactions recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td style="font-family: var(--font-mono); color: var(--text-muted);">#<?= $t['id'] ?></td>
                            <td style="font-family: var(--font-mono);">
                                <?php if (!empty($t['order_number'])): ?>
                                    <span style="color: var(--accent-cyan);">#<?= Security::escape($t['order_number']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 11px;">
                                <?= Security::escape($t['paypal_order_id'] ?? 'N/A') ?>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 11px; color: #38bdf8;">
                                <?= Security::escape($t['paypal_capture_id'] ?? 'N/A') ?>
                            </td>
                            <td><?= Security::escape($t['payer_email'] ?? 'N/A') ?></td>
                            <td style="font-family: var(--font-mono); font-weight: 700; color: #4ade80;">
                                $<?= number_format((float)$t['amount'], 2) ?> <?= Security::escape($t['currency']) ?>
                            </td>
                            <td>
                                <span class="badge <?= strtolower($t['status']) ?>"><?= Security::escape($t['status']) ?></span>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">
                                <?= date('Y-m-d H:i', strtotime($t['created_at'])) ?>
                            </td>
                            <td>
                                <button type="button" class="btn-utility" onclick="inspectPayload('<?= htmlspecialchars(addslashes($t['raw_payload'] ?? '{}'), ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-code"></i> Inspect
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Raw Payload Inspect -->
<div id="payloadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--surface-bg); max-width: 700px; width: 100%; border-radius: var(--radius-box); box-shadow: var(--shadow-v3-modal); padding: 20px; display: flex; flex-direction: column; gap: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--hairline); padding-bottom: 10px;">
            <h3 style="font-family: var(--font-display); font-size: 16px; color: #fff;">PayPal Raw Event Payload</h3>
            <button type="button" class="btn-utility" onclick="document.getElementById('payloadModal').style.display='none'">✕ Close</button>
        </div>
        <pre id="payloadContent" style="background: var(--canvas-bg); padding: 14px; border-radius: var(--radius-btn); font-family: var(--font-mono); font-size: 11px; color: #38bdf8; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"></pre>
    </div>
</div>

<script>
function inspectPayload(raw) {
    try {
        const obj = JSON.parse(raw);
        document.getElementById('payloadContent').textContent = JSON.stringify(obj, null, 2);
    } catch(e) {
        document.getElementById('payloadContent').textContent = raw;
    }
    document.getElementById('payloadModal').style.display = 'flex';
}
</script>
