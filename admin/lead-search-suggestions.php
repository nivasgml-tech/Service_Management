
<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once "../config/database.php";

$term = trim($_GET['q'] ?? '');

if ($term === '' || mb_strlen($term, 'UTF-8') < 2) {
    echo json_encode([]);
    exit;
}

try {
    $searchTerm = $term . '%';

    $sql = "
        SELECT
            id,
            name,
            mobile,
            email
        FROM leads
        WHERE
            name LIKE ?
            OR mobile LIKE ?
            OR email LIKE ?
        ORDER BY name ASC
        LIMIT 8
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(
        $results,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

} catch (Throwable $e) {
    error_log("Lead autocomplete error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([]);
}
