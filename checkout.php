<?php
// checkout.php
require_once 'config/database.php';

if (!isLoggedIn() || !isCustomer()) {
    header('Location: /auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get cart items grouped by store
$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.image, p.stock, s.store_name, s.id as store_id,
           u.address as user_address, u.phone_number as user_phone
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    JOIN stores s ON p.store_id = s.id
    JOIN users u ON ci.user_id = u.id
    WHERE ci.user_id = ?
    ORDER BY s.store_name
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

// Group items by store
$stores = [];
foreach ($cart_items as $item) {
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

        $shipping_address = $_POST['shipping_address'];
        $phone_number = $_POST['phone_number'];

        // Create separate orders for each store
        foreach ($stores as $store_id => $store) {
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, store_id, total_amount, shipping_address, phone_number)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $store_id,
                $store['subtotal'],
                $shipping_address,
                $phone_number
            ]);
            $order_id = $conn->lastInsertId();

            // Add order items
            foreach ($store['items'] as $item) {
                $stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, subtotal)
                    VALUES (?, ?, ?, ?, ?)
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
                    WHERE id = ? AND stock >= ?
                ");
                $result = $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);

                if (!$result || $stmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for product: " . $item['name']);
                }
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
        header('Location: ' . BASE_URL . '/orders');
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL;?>/dist/output.css">
    <?php include 'includes/header.php'; ?>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-8">Checkout</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error mb-6">
                <?php echo htmlspecialchars($error); ?>
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
                                            <img src="<?php echo $item['image'] ? BASE_URL . '/uploads/products/' . $item['image'] : '/images/default-product.jpg'; ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="w-16 h-16 object-cover rounded-md">
                                            <div>
                                                <h4 class="text-accesibel-text-color-2">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </h4>
                                                <p class="text-accesibel-text-color-3 text-sm">
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

            <!-- Checkout Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-accesibel-text-color-2 mb-4">Shipping Information</h2>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Shipping Address
                            </label>
                            <textarea name="shipping_address" rows="3" required
                                      class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:border-primary"
                            ><?php echo htmlspecialchars($cart_items[0]['user_address']); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Phone Number
                            </label>
                            <input type="tel" name="phone_number" required
                                   value="<?php echo htmlspecialchars($cart_items[0]['user_phone']); ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:border-primary">
                        </div>
                        
                        <div class="border-t border-border-separator-1 pt-4 mt-4">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-accesibel-text-color-2 font-medium">Total Amount:</span>
                                <span class="text-xl font-bold text-primary">
                                    Rp <?php echo number_format(array_sum(array_column($stores, 'subtotal')), 0, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <button type="submit" class="w-full bg-primary text-white py-3 rounded-md hover:bg-border-separator-3 transition-colors">
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