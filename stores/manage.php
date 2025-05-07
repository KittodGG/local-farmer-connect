<?php
// stores/manage.php
require_once '../config/database.php';

if (!isLoggedIn() || !isFarmer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get store information
$stmt = $conn->prepare("SELECT * FROM stores WHERE farmer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

// Get store statistics if store exists
if ($store) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(o.total_amount) as total_revenue,
            COUNT(DISTINCT CASE WHEN o.status = 'Pending' THEN o.id END) as pending_orders,
            AVG(r.rating) as average_rating,
            COUNT(r.id) as total_reviews,
            (SELECT COUNT(*) FROM products WHERE store_id = ? AND status = 'Available') as active_products
        FROM stores s
        LEFT JOIN orders o ON s.id = o.store_id
        LEFT JOIN reviews r ON s.id = r.store_id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$store['id'], $store['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent orders
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name as customer_name,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.store_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$store['id']]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle store update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $store_name = trim($_POST['store_name']);
        $description = trim($_POST['description']);
        $address = trim($_POST['address']);
        $phone_number = trim($_POST['phone_number']);

        // Validate inputs
        if (empty($store_name) || empty($description) || empty($address) || empty($phone_number)) {
            throw new Exception('All fields are required');
        }

        // Handle store image
        $profile_picture = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $profile_picture = uploadImage($_FILES['profile_picture'], 'uploads/stores/');
            if (!$profile_picture) {
                throw new Exception('Failed to upload store image');
            }
        }

        if ($store) {
            // Update existing store
            $sql = "UPDATE stores SET 
                    store_name = ?, description = ?, address = ?, phone_number = ?";
            $params = [$store_name, $description, $address, $phone_number];

            if ($profile_picture) {
                $sql .= ", profile_picture = ?";
                $params[] = $profile_picture;
                // Delete old image
                if ($store['profile_picture'] && file_exists('../uploads/stores/' . $store['profile_picture'])) {
                    unlink('../uploads/stores/' . $store['profile_picture']);
                }
            }

            $sql .= " WHERE farmer_id = ?";
            $params[] = $_SESSION['user_id'];

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            setFlashMessage('success', 'Store updated successfully');
        } else {
            // Create new store
            $stmt = $conn->prepare("
                INSERT INTO stores (farmer_id, store_name, description, address, phone_number, profile_picture)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $store_name,
                $description,
                $address,
                $phone_number,
                $profile_picture
            ]);

            setFlashMessage('success', 'Store created successfully');
        }

        header('Location: ' . BASE_URL . '/stores/manage.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Store Management - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-semibold text-accesibel-text-color-2">
                <?php echo $store ? 'Manage Your Store' : 'Create Your Store'; ?>
            </h1>
            <?php if ($store): ?>
                <a href="<?php echo BASE_URL; ?>/stores/view.php?id=<?php echo $store['id']; ?>" 
                   class="text-primary hover:text-border-separator-3">
                    View Store
                </a>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($store): ?>
            <!-- Store Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-accesibel-text-color-3 text-sm mb-2">Total Revenue</h3>
                    <p class="text-2xl font-semibold text-primary">
                        Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?>
                    </p>
                    <p class="text-sm text-accesibel-text-color-3 mt-1">
                        from <?php echo number_format($stats['total_orders']); ?> orders
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-accesibel-text-color-3 text-sm mb-2">Active Products</h3>
                    <p class="text-2xl font-semibold text-accesibel-text-color-2">
                        <?php echo number_format($stats['active_products']); ?>
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-accesibel-text-color-3 text-sm mb-2">Store Rating</h3>
                    <div class="flex items-center">
                        <p class="text-2xl font-semibold text-accesibel-text-color-2">
                            <?php echo number_format($stats['average_rating'] ?? 0, 1); ?>
                        </p>
                        <span class="text-sm text-accesibel-text-color-3 ml-2">
                            (<?php echo number_format($stats['total_reviews']); ?> reviews)
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-accesibel-text-color-3 text-sm mb-2">Pending Orders</h3>
                    <p class="text-2xl font-semibold text-yellow-600">
                        <?php echo number_format($stats['pending_orders']); ?>
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-accesibel-text-color-2 mb-4">Quick Actions</h3>
                    <div class="space-y-4">
                        <a href="<?php echo BASE_URL; ?>/stores/products.php" 
                           class="block bg-primary text-white text-center px-4 py-2 rounded-md hover:bg-border-separator-3">
                            Add New Product
                        </a>
                        <a href="<?php echo BASE_URL; ?>/stores/analytics.php" 
                           class="block bg-interactive-1 text-primary text-center px-4 py-2 rounded-md hover:bg-interactive-2">
                            View Analytics
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-accesibel-text-color-2 mb-4">Recent Orders</h3>
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-accesibel-text-color-3 text-center">No orders yet</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-accesibel-text-color-2">
                                            Order #<?php echo $order['id']; ?>
                                        </p>
                                        <p class="text-sm text-accesibel-text-color-3">
                                            by <?php echo htmlspecialchars($order['customer_name']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-primary font-medium">
                                            Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                        </p>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full
                                            <?php echo match($order['status']) {
                                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                                'Processing' => 'bg-blue-100 text-blue-800',
                                                'Shipped' => 'bg-purple-100 text-purple-800',
                                                'Delivered' => 'bg-green-100 text-green-800',
                                                'Cancelled' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            }; ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center pt-4">
                                <a href="<?php echo BASE_URL; ?>/orders/" class="text-primary hover:text-border-separator-3">
                                    View All Orders
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Store Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-accesibel-text-color-2 mb-6">
                <?php echo $store ? 'Store Information' : 'Create Store'; ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php if ($store && $store['profile_picture']): ?>
                    <div class="mb-6">
                        <img src="<?php echo BASE_URL; ?>/uploads/stores/<?php echo $store['profile_picture']; ?>"
                             alt="Store Profile" class="w-32 h-32 object-cover rounded-lg">
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                        Store Picture
                    </label>
                    <input type="file" name="profile_picture" accept="image/*"
                           class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                </div>

                <div>
                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                        Store Name
                    </label>
                    <input type="text" name="store_name" required
                           value="<?php echo htmlspecialchars($store['store_name'] ?? ''); ?>"
                           class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                </div>

                <div>
                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                        Description
                    </label>
                    <textarea name="description" rows="4" required
                              class="w-full border border-border-separator-1 rounded-md px-3 py-2"><?php 
                        echo htmlspecialchars($store['description'] ?? ''); 
                    ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                            Phone Number
                        </label>
                        <input type="tel" name="phone_number" required
                               value="<?php echo htmlspecialchars($store['phone_number'] ?? ''); ?>"
                               class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                            Store Address
                        </label>
                        <textarea name="address" rows="3" required
                                  class="w-full border border-border-separator-1 rounded-md px-3 py-2"><?php 
                            echo htmlspecialchars($store['address'] ?? ''); 
                        ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-primary text-white px-6 py-2 rounded-md hover:bg-border-separator-3">
                        <?php echo $store ? 'Update Store' : 'Create Store'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>