<?php
// cart.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';
require_once 'includes/header.php';

if (!isLoggedIn() || !isCustomer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.image, p.stock, s.store_name, s.id as store_id
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    JOIN stores s ON p.store_id = s.id
    WHERE ci.user_id = ?
    ORDER BY s.store_name
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by store
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Shopping Cart - Local Farmer Connect</title>
</head>
<body>
    <div class="container mx-auto px-4 py-8 h-screen">
        <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-8">Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-12">
                <h3 class="text-xl text-accesibel-text-color-2 mb-4">Your cart is empty</h3>
                <a href="<?php echo BASE_URL; ?>/products.php" 
                   class="bg-primary text-white px-6 py-2 rounded-md hover:bg-border-separator-3">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($stores as $store_id => $store): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">
                        <?php echo htmlspecialchars($store['store_name']); ?>
                    </h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($store['items'] as $item): ?>
                            <div class="flex items-center justify-between border-b border-border-separator-1 pb-4">
                                <div class="flex items-center space-x-4">
                                    <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $item['image'] ?: 'default.jpg'; ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="w-20 h-20 object-cover rounded-md">
                                    
                                    <div>
                                        <h3 class="text-accesibel-text-color-2 font-medium">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h3>
                                        <p class="text-primary font-medium">
                                            Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center border border-border-separator-1 rounded-md">
                                        <button onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')"
                                                class="px-3 py-1 text-accesibel-text-color-2 hover:bg-interactive-1">-</button>
                                        <input type="number" value="<?php echo $item['quantity']; ?>"
                                               min="1" max="<?php echo $item['stock']; ?>"
                                               onchange="updateQuantity(<?php echo $item['id']; ?>, 'set', this.value)"
                                               class="w-16 text-center border-x border-border-separator-1 py-1">
                                        <button onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')"
                                                class="px-3 py-1 text-accesibel-text-color-2 hover:bg-interactive-1">+</button>
                                    </div>
                                    
                                    <button onclick="removeItem(<?php echo $item['id']; ?>)"
                                            class="text-red-500 hover:text-red-600">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="flex justify-end">
                            <div class="text-right">
                                <p class="text-accesibel-text-color-3">Subtotal:</p>
                                <p class="text-xl font-semibold text-primary">
                                    Rp <?php echo number_format($store['subtotal'], 0, ',', '.'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <div class="flex justify-between items-center">
                    <p class="text-xl font-semibold text-accesibel-text-color-2">Total:</p>
                    <p class="text-2xl font-bold text-primary">
                        Rp <?php echo number_format(array_sum(array_column($stores, 'subtotal')), 0, ',', '.'); ?>
                    </p>
                </div>
                
                <div class="mt-6 text-right">
                    <a href="<?php echo BASE_URL; ?>/checkout.php" 
                       class="bg-primary text-white px-8 py-3 rounded-md hover:bg-border-separator-3">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(cartItemId, action, value = null) {
            fetch('<?php echo BASE_URL; ?>/api/cart/update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_item_id: cartItemId, action, quantity: value })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Failed to update quantity');
            })
            .catch(error => {
                alert('An error occurred');
                console.error('Error:', error);
            });
        }

        function removeItem(cartItemId) {
            if (!confirm('Remove this item from cart?')) return;
            
            fetch('<?php echo BASE_URL; ?>/api/cart/remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cart_item_id: cartItemId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Failed to remove item');
            })
            .catch(error => {
                alert('An error occurred');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>