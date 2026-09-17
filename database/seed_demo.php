<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

use App\Database\Database;

$pdo = Database::getConnection();

// Create sample demo protected file in storage/uploads
$sampleFilePath = UPLOADS_PATH . '/demo_source_code_pack.zip';
if (!file_exists($sampleFilePath)) {
    file_put_contents($sampleFilePath, "DIGITAL_ASSET_CONTENT_DEMO_ZIP_ARCHIVE\nVersion: 1.0.0\nThank you for purchasing!");
}

// 1. Downloadable File Product
$stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
$stmt->execute(['premium-algo-bot-script']);
if ((int)$stmt->fetchColumn() === 0) {
    $ins = $pdo->prepare('
        INSERT INTO products (name, slug, description, price, currency, product_type, file_path, file_name, file_size, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ');
    $ins->execute([
        'Premium Algo Bot Source Code Pack',
        'premium-algo-bot-script',
        'Full source code of the high-speed algorithmic trading engine. Includes setup guide, sample configs, and unlimited lifetime updates.',
        29.99,
        'USD',
        'downloadable_file',
        $sampleFilePath,
        'algo_bot_v1.0.zip',
        filesize($sampleFilePath)
    ]);
    echo "✅ Seeded downloadable product: Premium Algo Bot Source Code Pack\n";
}

// 2. Card / License Key Product
$stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
$stmt->execute(['vpn-ultra-1-year-license']);
if ((int)$stmt->fetchColumn() === 0) {
    $ins = $pdo->prepare('
        INSERT INTO products (name, slug, description, price, currency, product_type, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ');
    $ins->execute([
        'VPN Ultra 1-Year Dedicated License Card',
        'vpn-ultra-1-year-license',
        'Single-user dedicated 1-year serial key for VPN Ultra service. WireGuard & OpenVPN high-speed endpoints.',
        14.99,
        'USD',
        'card_license'
    ]);
    $cardProdId = (int)$pdo->lastInsertId();
    echo "✅ Seeded card product: VPN Ultra 1-Year Dedicated License Card\n";

    // Seed 5 available license cards in cards_pool
    $cardStmt = $pdo->prepare('INSERT INTO cards_pool (product_id, card_data, status) VALUES (?, ?, "available")');
    for ($i = 1; $i <= 5; $i++) {
        $key = 'VPN-ULTRA-' . strtoupper(bin2hex(random_bytes(3))) . '-' . strtoupper(bin2hex(random_bytes(3)));
        $cardStmt->execute([$cardProdId, $key]);
    }
    echo "✅ Seeded 5 available serial keys into cards_pool\n";
}

echo "Database initialization and seeding completed successfully!\n";
