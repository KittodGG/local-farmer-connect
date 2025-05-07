<?php
// api/reviews/submit.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isCustomer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($data['order_id'], $data['rating'], $data['comment'])) {
        throw new Exception('Missing required fields');
    }

    $order_id = $data['order_id'];
    $rating = (int)$data['rating'];
    $comment = trim($data['comment']);

    if ($rating < 1 || $rating > 5) {
        throw new Exception('Invalid rating value');
    }

    // Verify order belongs to user and is delivered
    $stmt = $conn->prepare("
        SELECT o.store_id 
        FROM orders o 
        WHERE o.id = ? AND o.user_id = ? AND o.status = 'Delivered'
        AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_id = o.id)
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Invalid order or review already exists');
    }

    // Create review
    $stmt = $conn->prepare("
        INSERT INTO reviews (user_id, store_id, order_id, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $order['store_id'],
        $order_id,
        $rating,
        $comment
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>