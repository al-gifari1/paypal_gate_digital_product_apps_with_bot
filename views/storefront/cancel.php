<?php
use App\Core\Security;
?>

<div class="detail-panel" style="max-width: 550px; margin: 40px auto; text-align: center;">
    <div style="width: 56px; height: 56px; background: #451a03; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #fbbf24; font-size: 26px;">
        <i class="fa-solid fa-ban"></i>
    </div>

    <h1 style="font-family: var(--font-display); font-size: 24px; color: #fff; margin-top: 12px;">
        PAYMENT CANCELLED
    </h1>
    <p style="color: var(--text-muted); font-size: 13px; margin-top: 6px;">
        You cancelled the PayPal payment process. No funds were charged to your account.
    </p>

    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 12px;">
        <a href="/" class="nav-btn primary">
            <i class="fa-solid fa-cart-shopping"></i> Return to Shop
        </a>
    </div>
</div>
