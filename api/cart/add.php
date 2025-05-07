<?php
// api/cart/add.php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isCustomer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please login as a customer to add items to cart']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Check if product exists and is in stock
    $stmt = $conn->prepare("SELECT stock, store_id FROM products WHERE id = ? AND status = 'Available'");
    $stmt->execute([$data['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not available');
    }

    if ($product['stock'] < $data['quantity']) {
        throw new Exception('Not enough stock available');
    }

    // Check if item already exists in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $data['product_id']]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    $conn->beginTransaction();

    if ($cartItem) {
        // Update existing cart item
        $newQuantity = $cartItem['quantity'] + $data['quantity'];
        if ($newQuantity > $product['stock']) {
            throw new Exception('Cannot add more items than available in stock');
        }

        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $cartItem['id']]);
    } else {
        // Add new cart item
        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $data['product_id'], $data['quantity']]);
    }

    $conn->commit();

    // Get updated cart count
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart successfully',
        'cartCount' => $cartCount
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>