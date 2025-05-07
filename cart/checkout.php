<?php
// cart/checkout.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isCustomer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.image, p.stock, s.store_name, s.id as store_id
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    JOIN stores s ON p.store_id = s.id
    WHERE ci.user_id = ?
    ORDER BY s.store_name
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    header('Location: ' . BASE_URL . '/cart/cart.php');
    exit;
}

// Group items by store
$stores = [];
foreach ($items as $item) {
    if (!isset($stores[$item['store_id']])) {
        $stores[$item['store_id']] = [
            'store_name' => $item['store_name'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $stores[$item['store_id']]['items'][] = $item;
    $stores[$item['store_id']]['subtotal'] += $item['price'] * $item['quantity'];
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $shipping_address = trim($_POST['shipping_address']);
        $phone_number = trim($_POST['phone_number']);
        $notes = trim($_POST['notes']);

        // Validate inputs
        if (empty($shipping_address) || empty($phone_number)) {
            throw new Exception('Please fill in all required fields');
        }

        // Create orders for each store
        foreach ($stores as $store_id => $store) {
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    user_id, store_id, total_amount, 
                    shipping_address, phone_number, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $store_id,
                $store['subtotal'],
                $shipping_address,
                $phone_number,
                $notes
            ]);
            $order_id = $conn->lastInsertId();

            // Add order items
            foreach ($store['items'] as $item) {
                // Verify stock
                $stmt = $conn->prepare("
                    SELECT stock FROM products WHERE id = ? AND stock >= ?
                ");
                $stmt->execute([$item['product_id'], $item['quantity']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Insufficient stock for product: " . $item['name']);
                }

                // Add order item
                $stmt = $conn->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, quantity, 
                        price_per_unit, subtotal
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['price'] * $item['quantity']
                ]);

                // Update product stock
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET stock = stock - ?
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart items for this store
            $stmt = $conn->prepare("
                DELETE FROM cart_items 
                WHERE user_id = ? AND product_id IN (
                    SELECT id FROM products WHERE store_id = ?
                )
            ");
            $stmt->execute([$_SESSION['user_id'], $store_id]);
        }

        $conn->commit();
        setFlashMessage('success', 'Orders placed successfully!');
        header('Location: ' . BASE_URL . '/orders/');
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-8">Checkout</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Summary -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-accesibel-text-color-2 mb-4">Order Summary</h2>
                    
                    <?php foreach ($stores as $store): ?>
                        <div class="mb-6 pb-6 border-b border-border-separator-1 last:border-0 last:pb-0 last:mb-0">
                            <h3 class="text-lg font-medium text-accesibel-text-color-2 mb-4">
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </h3>
                            
                            <div class="space-y-4">
                                <?php foreach ($store['items'] as $item): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $item['image'] ?: 'default.jpg'; ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="w-16 h-16 object-cover rounded-md">
                                            <div>
                                                <h4 class="text-accesibel-text-color-2">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </h4>
                                                <p class="text-sm text-accesibel-text-color-3">
                                                    Quantity: <?php echo $item['quantity']; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <p class="text-accesibel-text-color-2 font-medium">
                                            Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4 flex justify-end">
                                <div class="text-right">
                                    <p class="text-accesibel-text-color-3">Subtotal:</p>
                                    <p class="text-lg font-semibold text-accesibel-text-color-2">
                                        Rp <?php echo number_format($store['subtotal'], 0, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-accesibel-text-color-2 mb-4">Shipping Information</h2>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Shipping Address
                            </label>
                            <textarea name="shipping_address" rows="3" required
                                      class="w-full border border-border-separator-1 rounded-md px-3 py-2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Phone Number
                            </label>
                            <input type="tel" name="phone_number" required
                                   value="<?php echo htmlspecialchars($user['phone_number']); ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Order Notes (Optional)
                            </label>
                            <textarea name="notes" rows="2"
                                      class="w-full border border-border-separator-1 rounded-md px-3 py-2"
                                      placeholder="Any special instructions for delivery"></textarea>
                        </div>
                        
                        <div class="border-t border-border-separator-1 pt-4 mt-4">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-accesibel-text-color-2 font-medium">Total Amount:</span>
                                <span class="text-xl font-bold text-primary">
                                    Rp <?php echo number_format(array_sum(array_column($stores, 'subtotal')), 0, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-primary text-white py-3 rounded-md hover:bg-border-separator-3">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>