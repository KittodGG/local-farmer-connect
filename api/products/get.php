<?php
// api/products/get.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();

    $product_id = $_GET['id'] ?? null;
    if (!$product_id) {
        throw new Exception('Product ID is required');
    }

    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, s.store_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN stores s ON p.store_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception('Product not found');
    }

    echo json_encode(['success' => true, 'data' => $product]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>