<?php
// api/cart/remove.php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isCustomer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart_item_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing cart_item_id']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['cart_item_id'], $_SESSION['user_id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Cart item not found');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>