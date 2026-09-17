<?php
declare(strict_types=1);

/**
 * Telegram Bot CLI Long-Polling Worker
 * Usage: php bot_worker.php
 */

require_once __DIR__ . '/config/app.php';

use App\Services\SettingsService;
use App\Services\TelegramBotService;

$token = SettingsService::getTelegramBotToken();
if (empty($token)) {
    echo "❌ [ERROR] Telegram Bot Token is not set in System Settings.\n";
    echo "👉 Please log in to Admin Panel (/admin/settings) and save your Bot Token first.\n";
    exit(1);
}

echo "======================================================\n";
echo "🤖 Telegram Bot CLI Worker Running (Long-Polling Mode)\n";
echo "Platform: " . SettingsService::get('site_title') . " (v" . APP_VERSION . ")\n";
echo "======================================================\n";

$me = TelegramBotService::getMe();
if (empty($me['ok'])) {
    echo "❌ Failed to connect to Telegram API: " . ($me['description'] ?? 'Unknown error') . "\n";
    exit(1);
}

echo "✅ Connected as: @" . ($me['result']['username'] ?? 'Bot') . "\n";
echo "⏳ Listening for Telegram updates... (Press Ctrl+C to stop)\n\n";

$offset = 0;

while (true) {
    try {
        $updates = TelegramBotService::getUpdates($offset, 20, 2);
        if (!empty($updates['ok']) && !empty($updates['result'])) {
            foreach ($updates['result'] as $update) {
                $updateId = $update['update_id'];
                $offset = $updateId + 1;

                $from = $update['message']['from']['username'] ?? $update['message']['from']['first_name'] ?? 'User';
                $text = $update['message']['text'] ?? '[non-text message]';
                echo "[" . date('H:i:s') . "] Received update from {$from}: {$text}\n";

                TelegramBotService::processUpdate($update);
            }
        }
    } catch (\Throwable $e) {
        echo "⚠️ Error during polling loop: " . $e->getMessage() . "\n";
        sleep(1);
    }

    usleep(50000); // 50ms interval between polls for fast response
}
