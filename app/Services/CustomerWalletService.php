<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

class CustomerWalletService
{
    /**
     * Find or create customer profile by email
     */
    public static function findOrCreateCustomer(string $email, ?string $name = null, ?string $telegramUserId = null): array
    {
        $pdo = Database::getConnection();
        $email = strtolower(trim($email));

        $stmt = $pdo->prepare('SELECT * FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Update name or telegram user id if provided
            $updates = [];
            $params = [];
            if (!empty($name) && empty($customer['name'])) {
                $updates[] = 'name = ?';
                $params[] = $name;
            }
            if (!empty($telegramUserId) && empty($customer['telegram_user_id'])) {
                $updates[] = 'telegram_user_id = ?';
                $params[] = $telegramUserId;
            }

            if (!empty($updates)) {
                $updates[] = 'updated_at = CURRENT_TIMESTAMP';
                $params[] = $customer['id'];
                $pdo->prepare('UPDATE customers SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
                $customer = $pdo->query("SELECT * FROM customers WHERE id = {$customer['id']}")->fetch(PDO::FETCH_ASSOC);
            }

            return $customer;
        }

        // Insert new customer
        $ins = $pdo->prepare('
            INSERT INTO customers (email, name, telegram_user_id) 
            VALUES (?, ?, ?)
        ');
        $ins->execute([$email, $name ?: 'Valued Customer', $telegramUserId ?: null]);
        $id = (int)$pdo->lastInsertId();

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name ?: 'Valued Customer',
            'telegram_user_id' => $telegramUserId ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Detect credit card brand from card number
     */
    public static function detectCardBrand(string $cardNumber): string
    {
        $clean = preg_replace('/\D/', '', $cardNumber) ?? '';
        if (str_starts_with($clean, '4')) {
            return 'visa';
        }
        if (preg_match('/^(5[1-5]|2[2-7])/', $clean)) {
            return 'mastercard';
        }
        if (preg_match('/^3[47]/', $clean)) {
            return 'amex';
        }
        if (preg_match('/^(6011|65|64[4-9])/', $clean)) {
            return 'discover';
        }
        if (preg_match('/^35(2[89]|[3-8][0-9])/', $clean)) {
            return 'jcb';
        }
        return 'credit-card';
    }

    /**
     * Save card to customer's wallet
     */
    public static function saveCard(
        int $customerId,
        string $cardholderName,
        string $cardNumber,
        string $expMonth,
        string $expYear,
        string $cvv,
        bool $isDefault = true
    ): array {
        $pdo = Database::getConnection();
        $cleanNumber = preg_replace('/\D/', '', $cardNumber) ?? '';
        $last4 = substr($cleanNumber, -4) ?: '0000';
        $brand = self::detectCardBrand($cleanNumber);

        // Check if card already saved for this customer
        $stmt = $pdo->prepare('SELECT * FROM customer_cards WHERE customer_id = ? AND last4 = ? AND exp_month = ? AND exp_year = ?');
        $stmt->execute([$customerId, $last4, $expMonth, $expYear]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update CVV or cardholder name if changed
            $upd = $pdo->prepare('UPDATE customer_cards SET cardholder_name = ?, card_number = ?, cvv = ? WHERE id = ?');
            $upd->execute([$cardholderName, $cleanNumber, $cvv, $existing['id']]);
            return $existing;
        }

        if ($isDefault) {
            $pdo->prepare('UPDATE customer_cards SET is_default = 0 WHERE customer_id = ?')->execute([$customerId]);
        }

        $ins = $pdo->prepare('
            INSERT INTO customer_cards (customer_id, cardholder_name, card_number, last4, card_brand, exp_month, exp_year, cvv, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $ins->execute([
            $customerId,
            $cardholderName,
            $cleanNumber,
            $last4,
            $brand,
            $expMonth,
            $expYear,
            $cvv,
            $isDefault ? 1 : 0
        ]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'customer_id' => $customerId,
            'cardholder_name' => $cardholderName,
            'last4' => $last4,
            'card_brand' => $brand,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'is_default' => $isDefault ? 1 : 0,
        ];
    }

    /**
     * Get all saved cards for a customer by email
     */
    public static function getSavedCardsByEmail(string $email): array
    {
        $pdo = Database::getConnection();
        $email = strtolower(trim($email));

        $stmt = $pdo->prepare('
            SELECT c.id, c.cardholder_name, c.last4, c.card_brand, c.exp_month, c.exp_year, c.is_default
            FROM customer_cards c
            JOIN customers cust ON c.customer_id = cust.id
            WHERE cust.email = ?
            ORDER BY c.is_default DESC, c.id DESC
        ');
        $stmt->execute([$email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
