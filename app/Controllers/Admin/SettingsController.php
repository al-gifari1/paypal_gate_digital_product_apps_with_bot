<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Core\View;
use App\Services\SettingsService;
use App\Services\TelegramBotService;

class SettingsController
{
    public function index(): void
    {
        AuthController::requireAuth();

        $settings = SettingsService::getAll();

        View::render('admin/settings/index', [
            'pageTitle' => 'System & API Settings',
            'activeMenu' => 'settings',
            'settings' => $settings,
        ], 'admin/layout');
    }

    public function save(): void
    {
        AuthController::requireAuth();

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::flash('error', 'Security token expired.');
            View::redirect('/admin/settings');
        }

        $fields = [
            'site_title',
            'currency',
            'app_url',
            'paypal_mode',
            'paypal_client_id',
            'paypal_client_secret',
            'paypal_webhook_id',
            'gateway_paypal_enabled',
            'gateway_fride_enabled',
            'fride_merchant_id',
            'fride_api_key',
            'fride_webhook_secret',
            'telegram_bot_token',
            'telegram_admin_chat_id',
        ];

        $payload = [];
        foreach ($fields as $field) {
            $payload[$field] = trim($_POST[$field] ?? '');
        }

        SettingsService::updateMany($payload);

        Session::flash('success', 'System settings saved successfully!');
        View::redirect('/admin/settings');
    }

    public function testBot(): void
    {
        AuthController::requireAuth();

        $result = TelegramBotService::getMe();
        if (!empty($result['ok'])) {
            $botName = $result['result']['username'] ?? 'Bot';
            Session::flash('success', "Telegram Bot Connected Successfully! Bot: @$botName");
        } else {
            $msg = $result['description'] ?? 'Connection error';
            Session::flash('error', "Telegram Bot Test Failed: $msg");
        }

        View::redirect('/admin/settings');
    }

    public function setWebhook(): void
    {
        AuthController::requireAuth();

        $appUrl = SettingsService::getAppUrl();
        $webhookUrl = $appUrl . '/api/telegram/webhook';

        $result = TelegramBotService::setWebhook($webhookUrl);
        if (!empty($result['ok'])) {
            Session::flash('success', "Telegram Webhook Registered: $webhookUrl");
        } else {
            $msg = $result['description'] ?? 'Failed to set webhook';
            Session::flash('error', "Webhook Setup Error: $msg");
        }

        View::redirect('/admin/settings');
    }

    public function deleteWebhook(): void
    {
        AuthController::requireAuth();

        $result = TelegramBotService::deleteWebhook();
        if (!empty($result['ok'])) {
            Session::flash('success', 'Telegram Webhook removed (Polling mode enabled).');
        } else {
            Session::flash('error', 'Failed to remove webhook: ' . ($result['description'] ?? ''));
        }

        View::redirect('/admin/settings');
    }
}
