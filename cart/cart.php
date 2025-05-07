<?php
// cart/cart.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isCustomer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get cart items grouped by store
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Shopping Cart - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-8">Shopping Cart</h1>

        <?php if (empty($stores)): ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-md">
                <h3 class="text-xl text-accesibel-text-color-2 mb-4">Your cart is empty</h3>
                <p class="text-accesibel-text-color-3 mb-6">Add some products to your cart and they will appear here.</p>
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
                                        <?php if ($item['stock'] < $item['quantity']): ?>
                                            <p class="text-red-500 text-sm">
                                                Only <?php echo $item['stock']; ?> available
                                            </p>
                                        <?php endif; ?>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="flex justify-end pt-4">
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
                    <div>
                        <p class="text-accesibel-text-color-3">Total Items: 
                            <?php echo array_sum(array_map(function($store) {
                                return array_sum(array_column($store['items'], 'quantity'));
                            }, $stores)); ?>
                        </p>
                        <p class="text-xl font-semibold text-accesibel-text-color-2">Total Amount:</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-primary">
                            Rp <?php echo number_format(array_sum(array_column($stores, 'subtotal')), 0, ',', '.'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 text-right">
                    <a href="<?php echo BASE_URL; ?>/cart/checkout.php" 
                       class="bg-primary text-white px-8 py-3 rounded-md hover:bg-border-separator-3 inline-block">
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
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update quantity');
                }
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
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to remove item');
                }
            })
            .catch(error => {
                alert('An error occurred');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>