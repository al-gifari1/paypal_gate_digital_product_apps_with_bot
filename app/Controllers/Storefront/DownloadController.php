<?php
declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Database\Database;
use PDO;

class DownloadController
{
    public function download(array $params): void
    {
        $token = $params['token'] ?? '';

        if (empty($token) || strlen($token) < 32) {
            http_response_code(403);
            die('Invalid download token.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT dt.*, p.file_path, p.file_name, p.file_size
            FROM download_tokens dt
            JOIN products p ON dt.product_id = p.id
            WHERE dt.token_hash = ?
        ');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            die('Download link not found.');
        }

        // Check expiration
        if (strtotime($row['expires_at']) < time()) {
            http_response_code(410);
            die('This download link has expired. Please contact support if you need a new link.');
        }

        // Check download count
        if ($row['download_count'] >= $row['max_downloads']) {
            http_response_code(403);
            die('Maximum download attempts exceeded for this secure link.');
        }

        $filePath = $row['file_path'];
        if (empty($filePath) || !file_exists($filePath)) {
            http_response_code(404);
            die('Asset file is missing from server storage. Please contact administrator.');
        }

        // Increment download count
        $upd = $pdo->prepare('UPDATE download_tokens SET download_count = download_count + 1 WHERE id = ?');
        $upd->execute([$row['id']]);

        // Stream file safely
        $fileName = $row['file_name'] ?: basename($filePath);
        $fileSize = filesize($filePath);

        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);

        readfile($filePath);
        exit;
    }
}
