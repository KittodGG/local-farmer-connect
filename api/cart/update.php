<?php
// api/cart/update.php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isCustomer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart_item_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Get current cart item
    $stmt = $conn->prepare("
        SELECT ci.*, p.stock 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.id = ? AND ci.user_id = ?
    ");
    $stmt->execute([$data['cart_item_id'], $_SESSION['user_id']]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        throw new Exception('Cart item not found');
    }

    $newQuantity = $cartItem['quantity'];

    switch ($data['action']) {
        case 'increase':
            $newQuantity++;
            break;
        case 'decrease':
            $newQuantity--;
            break;
        case 'set':
            if (!isset($data['quantity'])) {
                throw new Exception('Quantity is required for set action');
            }
            $newQuantity = $data['quantity'];
            break;
        default:
            throw new Exception('Invalid action');
    }

    // Validate quantity
    if ($newQuantity < 1) {
        throw new Exception('Quantity cannot be less than 1');
    }

    if ($newQuantity > $cartItem['stock']) {
        throw new Exception('Cannot add more items than available in stock');
    }

    // Update quantity
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$newQuantity, $data['cart_item_id'], $_SESSION['user_id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>