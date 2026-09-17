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

            <!-- Pluggable Payment Gateway Selector -->
            <div>
                <label style="display: block; font-family: var(--font-mono); font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                    Select Payment Method *
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <!-- Direct Card Option (v1 Wallet Engine) -->
                    <label style="background: var(--bg-card); border: 2px solid var(--border-subtle); padding: 12px 14px; border-radius: var(--radius-btn); cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" class="gateway-option-card">
                        <input type="radio" name="payment_gateway" value="direct_card" checked style="accent-color: var(--accent);" id="gw_direct_card">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 14px; color: #fff; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-credit-card" style="color: var(--accent);"></i>
                                Credit / Debit Card
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                Pay directly with Visa, Mastercard, Amex with 1-click saved wallet.
                            </div>
                        </div>
                    </label>

                    <?php 
                    if (!empty($activeGateways)):
                        foreach ($activeGateways as $gw): 
                            $gwId = Security::escape($gw->getId());
                    ?>
                        <label style="background: var(--bg-card); border: 2px solid var(--border-subtle); padding: 12px 14px; border-radius: var(--radius-btn); cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" class="gateway-option-card">
                            <input type="radio" name="payment_gateway" value="<?= $gwId ?>" style="accent-color: var(--accent);">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 14px; color: #fff; display: flex; align-items: center; gap: 8px;">
                                    <i class="<?= Security::escape($gw->getIcon()) ?>" style="color: var(--accent);"></i>
                                    <?= Security::escape($gw->getName()) ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    <?= Security::escape($gw->getDescription()) ?>
                                </div>
                            </div>
                        </label>
                    <?php 
                        endforeach; 
                    endif; ?>
                </div>
            </div>

            <!-- Direct Card & Wallet Section (v1 Testing Architecture) -->
            <div id="card_details_container" style="background: var(--bg-card); border: 1px solid var(--border-subtle); padding: 18px; border-radius: var(--radius-btn); display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-family: var(--font-mono); font-size: 12px; font-weight: 700; color: #fff; text-transform: uppercase;">
                        <i class="fa-solid fa-wallet" style="color: #4ade80;"></i> Card Wallet & Details
                    </span>
                    <span id="card_brand_badge" style="font-family: var(--font-mono); font-size: 11px; color: var(--accent); font-weight: 600;">
                        <i class="fa-brands fa-cc-visa"></i> <i class="fa-brands fa-cc-mastercard"></i>
                    </span>
                </div>

                <!-- Saved Cards Detected Banner (Hidden by default, shown when customer has saved cards) -->
                <div id="saved_cards_box" style="display: none; background: #0f172a; border: 1px solid #334155; padding: 12px; border-radius: 6px;">
                    <div style="font-size: 11px; font-family: var(--font-mono); color: #38bdf8; margin-bottom: 8px;">
                        <i class="fa-solid fa-bolt"></i> Saved Cards in Your Wallet:
                    </div>
                    <div id="saved_cards_list" style="display: flex; flex-direction: column; gap: 6px;"></div>
                </div>

                <div id="new_card_fields" style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <label style="display: block; font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-bottom: 4px;" for="cardholder_name">
                            Cardholder Name
                        </label>
                        <input type="text" id="cardholder_name" name="cardholder_name" class="form-input" placeholder="Name on card">
                    </div>

                    <div>
                        <label style="display: block; font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-bottom: 4px;" for="card_number">
                            Card Number
                        </label>
                        <input type="text" id="card_number" name="card_number" class="form-input" placeholder="4111 2222 3333 4444" maxlength="19">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">
                                Expiration (MM / YY)
                            </label>
                            <div style="display: flex; gap: 6px;">
                                <input type="text" name="exp_month" id="exp_month" class="form-input" placeholder="MM" maxlength="2" style="text-align: center;">
                                <input type="text" name="exp_year" id="exp_year" class="form-input" placeholder="YY" maxlength="4" style="text-align: center;">
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); margin-bottom: 4px;" for="cvv">
                                CVV / CVC
                            </label>
                            <input type="password" id="cvv" name="cvv" class="form-input" placeholder="123" maxlength="4" style="text-align: center;">
                        </div>
                    </div>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 4px; font-size: 12px; color: #cbd5e1;">
                        <input type="checkbox" name="save_card_wallet" value="1" checked style="accent-color: var(--accent);">
                        <span>Save this card securely in my profile wallet for instant 1-click future checkout</span>
                    </label>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" id="submitPaymentBtn" class="nav-btn primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 15px; font-weight: 700;">
                    <i class="fa-solid fa-lock" style="font-size: 16px;"></i> Confirm & Pay &bull; $<?= number_format((float)$product['price'], 2) ?>
                </button>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const emailInput = document.getElementById('buyer_email');
        const cardContainer = document.getElementById('card_details_container');
        const savedCardsBox = document.getElementById('saved_cards_box');
        const savedCardsList = document.getElementById('saved_cards_list');
        const newCardFields = document.getElementById('new_card_fields');
        const gatewayRadios = document.querySelectorAll('input[name="payment_gateway"]');
        const cardNumberInput = document.getElementById('card_number');
        const cardBrandBadge = document.getElementById('card_brand_badge');

        // Toggle card inputs when payment gateway changes
        gatewayRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'direct_card') {
                    cardContainer.style.display = 'flex';
                } else {
                    cardContainer.style.display = 'none';
                }
            });
        });

        // Auto-detect brand icon
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '');
                if (val.startsWith('4')) {
                    cardBrandBadge.innerHTML = '<i class="fa-brands fa-cc-visa" style="color: #60a5fa; font-size: 16px;"></i> Visa';
                } else if (/^(5[1-5]|2[2-7])/.test(val)) {
                    cardBrandBadge.innerHTML = '<i class="fa-brands fa-cc-mastercard" style="color: #f97316; font-size: 16px;"></i> Mastercard';
                } else if (/^3[47]/.test(val)) {
                    cardBrandBadge.innerHTML = '<i class="fa-brands fa-cc-amex" style="color: #38bdf8; font-size: 16px;"></i> Amex';
                } else {
                    cardBrandBadge.innerHTML = '<i class="fa-brands fa-cc-visa"></i> <i class="fa-brands fa-cc-mastercard"></i>';
                }
            });
        }

        // Auto-lookup saved cards when email changes
        let lookupTimer = null;
        if (emailInput) {
            emailInput.addEventListener('input', () => {
                clearTimeout(lookupTimer);
                const email = emailInput.value.trim();
                if (email.includes('@') && email.includes('.')) {
                    lookupTimer = setTimeout(() => {
                        fetch('<?= url('/api/v1/wallet/cards') ?>?email=' + encodeURIComponent(email))
                            .then(res => res.json())
                            .then(data => {
                                if (data.has_cards && data.cards.length > 0) {
                                    savedCardsList.innerHTML = '';
                                    data.cards.forEach((card, idx) => {
                                        const label = document.createElement('label');
                                        label.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 12px; color: #fff; cursor: pointer; padding: 4px 0;';
                                        label.innerHTML = `
                                            <input type="radio" name="selected_saved_card" value="${card.id}" ${idx === 0 ? 'checked' : ''} style="accent-color: #4ade80;">
                                            <span>💳 <strong>${card.card_brand.toUpperCase()}</strong> ending in •••• ${card.last4} (Exp: ${card.exp_month}/${card.exp_year})</span>
                                        `;
                                        savedCardsList.appendChild(label);
                                    });

                                    // Add option to enter a new card
                                    const newCardOpt = document.createElement('label');
                                    newCardOpt.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 12px; color: #94a3b8; cursor: pointer; padding-top: 4px; border-top: 1px dashed #334155; margin-top: 4px;';
                                    newCardOpt.innerHTML = `
                                        <input type="radio" name="selected_saved_card" value="new" style="accent-color: #4ade80;">
                                        <span>➕ Enter a new card instead</span>
                                    `;
                                    savedCardsList.appendChild(newCardOpt);

                                    savedCardsBox.style.display = 'block';

                                    // Toggle new card input fields
                                    const cardRadios = savedCardsList.querySelectorAll('input[name="selected_saved_card"]');
                                    cardRadios.forEach(cr => {
                                        cr.addEventListener('change', () => {
                                            if (cr.value === 'new') {
                                                newCardFields.style.display = 'flex';
                                            } else {
                                                newCardFields.style.display = 'none';
                                            }
                                        });
                                    });
                                    newCardFields.style.display = 'none'; // Default to using saved card
                                } else {
                                    savedCardsBox.style.display = 'none';
                                    newCardFields.style.display = 'flex';
                                }
                            })
                            .catch(() => {});
                    }, 500);
                }
            });
        }
    });
    </script>

    <div style="text-align: center; font-size: 11px; font-family: var(--font-mono); color: var(--text-muted); margin-top: 8px;">
        <i class="fa-solid fa-shield-halved"></i> 256-Bit Cryptographic SSL Security &bull; Zero-Trust Instant Product Delivery
    </div>
</div>
