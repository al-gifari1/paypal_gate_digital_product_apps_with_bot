<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Session;
use App\Core\View;
use App\Database\Database;
use App\Services\SettingsService;
use App\Services\TelegramBotService;

class PageController
{
    public function about(): void
    {
        View::render('storefront/pages/about', [
            'pageTitle' => 'About Us & Infrastructure',
        ], 'storefront/layout');
    }

    public function contact(): void
    {
        View::render('storefront/pages/contact', [
            'pageTitle' => 'Contact Support & Help Desk',
        ], 'storefront/layout');
    }

    public function submitContact(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
            Session::flash('error', 'Please provide a valid name, email address, and message.');
            View::redirect('/contact');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            INSERT INTO contact_messages (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$name, $email, $subject ?: 'General Inquiry', $message]);

        // Dispatch alert to Admin Telegram if configured
        $adminChatId = SettingsService::getTelegramAdminChatId();
        if (!empty($adminChatId)) {
            $telegramMsg = "<b>📩 [NEW CONTACT INQUIRY]</b>\n"
                . "<b>From:</b> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")\n"
                . "<b>Subject:</b> " . htmlspecialchars($subject ?: 'General Inquiry') . "\n"
                . "<b>Message:</b>\n" . htmlspecialchars(substr($message, 0, 400));
            TelegramBotService::sendMessage((int)$adminChatId, $telegramMsg);
        }

        Session::flash('success', 'Your message has been received! Our support desk will reply to your email shortly.');
        View::redirect('/contact');
    }

    public function privacy(): void
    {
        View::render('storefront/pages/privacy', [
            'pageTitle' => 'Privacy & Data Protection Policy',
        ], 'storefront/layout');
    }

    public function terms(): void
    {
        View::render('storefront/pages/terms', [
            'pageTitle' => 'Terms of Service & License Agreement',
        ], 'storefront/layout');
    }

    public function refund(): void
    {
        View::render('storefront/pages/refund', [
            'pageTitle' => 'Refund & Replacement Policy',
        ], 'storefront/layout');
    }
}
