<?php
declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Core\View;
use App\Services\TelegramBotService;

class TelegramWebhookController
{
    public function handle(): void
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            View::json(['status' => 'empty payload'], 400);
            return;
        }

        $update = json_decode($raw, true);
        if (!is_array($update)) {
            View::json(['status' => 'invalid json'], 400);
            return;
        }

        // Process message / command / callback
        TelegramBotService::processUpdate($update);

        View::json(['ok' => true]);
    }
}
