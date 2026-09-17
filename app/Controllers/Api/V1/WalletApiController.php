<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\View;
use App\Services\CustomerWalletService;

class WalletApiController
{
    public function lookupCards(): void
    {
        $email = trim($_GET['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::json(['cards' => []]);
            return;
        }

        $cards = CustomerWalletService::getSavedCardsByEmail($email);
        View::json([
            'email' => $email,
            'has_cards' => !empty($cards),
            'cards' => $cards,
        ]);
    }
}
