<?php
// api/products/delete.php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isFarmer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verify product ownership
    $stmt = $conn->prepare("
        SELECT p.*, s.farmer_id 
        FROM products p 
        JOIN stores s ON p.store_id = s.id 
        WHERE p.id = ? AND s.farmer_id = ?
    ");
    $stmt->execute([$data['product_id'], $_SESSION['user_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found or unauthorized');
    }

    // Check if product is in any orders
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM order_items 
        WHERE product_id = ?
    ");
    $stmt->execute([$data['product_id']]);
    $hasOrders = $stmt->fetchColumn() > 0;

    if ($hasOrders) {
        // If product has orders, just mark it as unavailable
        $stmt = $conn->prepare("
            UPDATE products 
            SET status = 'Out of Stock'
            WHERE id = ?
        ");
        $stmt->execute([$data['product_id']]);
        $message = 'Product marked as out of stock';
    } else {
        // If no orders, delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$data['product_id']]);

        // Delete product image if exists
        if ($product['image'] && file_exists('../../uploads/products/' . $product['image'])) {
            unlink('../../uploads/products/' . $product['image']);
        }
        $message = 'Product deleted successfully';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>