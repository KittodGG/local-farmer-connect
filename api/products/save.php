<?php
// api/products/save.php
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isFarmer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Verify store ownership
    $stmt = $conn->prepare("SELECT id FROM stores WHERE farmer_id = ? AND id = ?");
    $stmt->execute([$_SESSION['user_id'], $_POST['store_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Unauthorized store access');
    }

    // Validate required fields
    $required = ['store_id', 'name', 'category_id', 'price', 'stock', 'description', 'status'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Field {$field} is required");
        }
    }

    // Clean and validate data
    $data = [
        'store_id' => $_POST['store_id'],
        'name' => trim($_POST['name']),
        'category_id' => $_POST['category_id'],
        'price' => (float)$_POST['price'],
        'stock' => (int)$_POST['stock'],
        'description' => trim($_POST['description']),
        'status' => $_POST['status']
    ];

    // Validate numeric fields
    if ($data['price'] <= 0) {
        throw new Exception('Price must be greater than 0');
    }
    if ($data['stock'] < 0) {
        throw new Exception('Stock cannot be negative');
    }

    // Handle image upload if provided
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = 'uploads/products/'; // Relatif ke root project
        $image = uploadImage($_FILES['image'], $uploadDir);
        if (!$image) {
            throw new Exception('Failed to upload product image. Make sure it\'s a JPG or PNG file under 5MB.');
        }
    }

    // Jika update dan ada image baru
    if (!empty($_POST['product_id']) && $image) {
        // Get old image
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$_POST['product_id']]);
        $oldImage = $stmt->fetchColumn();

        // Delete old image if exists
        if ($oldImage) {
            $oldImagePath = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/uploads/products/' . $oldImage;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
    }

    $conn->beginTransaction();

    if (!empty($_POST['product_id'])) {
        // Update existing product
        // First verify product ownership
        $stmt = $conn->prepare("
            SELECT p.*, s.farmer_id 
            FROM products p 
            JOIN stores s ON p.store_id = s.id 
            WHERE p.id = ? AND s.farmer_id = ?
        ");
        $stmt->execute([$_POST['product_id'], $_SESSION['user_id']]);
        $existing_product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing_product) {
            throw new Exception('Product not found or unauthorized');
        }

        $sql = "UPDATE products SET 
                name = :name,
                category_id = :category_id,
                price = :price,
                stock = :stock,
                description = :description,
                status = :status";

        if ($image) {
            $sql .= ", image = :image";
            // Delete old image
            if ($existing_product['image'] && file_exists('../../uploads/products/' . $existing_product['image'])) {
                unlink('../../uploads/products/' . $existing_product['image']);
            }
        }

        $sql .= " WHERE id = :product_id AND store_id = :store_id";

        $stmt = $conn->prepare($sql);
        $params = $data;
        $params['product_id'] = $_POST['product_id'];
        if ($image) {
            $params['image'] = $image;
        }

        $stmt->execute($params);
        $message = 'Product updated successfully';
    } else {
        // Create new product
        $stmt = $conn->prepare("
            INSERT INTO products (
                store_id, category_id, name, description,
                price, stock, image, status
            ) VALUES (
                :store_id, :category_id, :name, :description,
                :price, :stock, :image, :status
            )
        ");

        $params = $data;
        $params['image'] = $image;

        $stmt->execute($params);
        $message = 'Product created successfully';
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $_FILES['image'] ?? null,
            'post' => $_POST,
            'error' => error_get_last()
        ]
    ]);
    exit;
}
