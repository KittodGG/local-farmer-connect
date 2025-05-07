<?php
// api/reviews/get.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

header('Content-Type: application/json');

try {
    $store_id = $_GET['store_id'] ?? null;
    if (!$store_id) {
        throw new Exception('Store ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get store rating summary
    $stmt = $conn->prepare("
        SELECT 
            AVG(rating) as average_rating,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        FROM reviews
        WHERE store_id = ?
    ");
    $stmt->execute([$store_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent reviews
    $stmt = $conn->prepare("
        SELECT r.*, u.full_name, u.profile_picture
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.store_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$store_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'recent_reviews' => $reviews
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>