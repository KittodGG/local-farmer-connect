<?php
// orders.php
require_once 'config/database.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get orders
$stmt = $conn->prepare("
    SELECT o.*, s.store_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order items when needed
function getOrderItems($conn, $order_id) {
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    
    try {
        $conn->beginTransaction();
        
        // Check if order can be cancelled
        $stmt = $conn->prepare("
            SELECT status 
            FROM orders 
            WHERE id = ? AND user_id = ? AND status IN ('Pending', 'Processing')
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Order cannot be cancelled");
        }
        
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'Cancelled' 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        
        // Restore product stock
        $stmt = $conn->prepare("
            UPDATE products p
            JOIN order_items oi ON p.id = oi.product_id
            SET p.stock = p.stock + oi.quantity
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        
        $conn->commit();
        setFlashMessage('success', 'Order cancelled successfully');
        header('Location: /orders.php');
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
    <title>My Orders - Local Farmer Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-8">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="text-center py-12">
                <h3 class="text-xl text-accesibel-text-color-2 mb-2">No orders found</h3>
                <p class="text-accesibel-text-color-3 mb-4">You haven't placed any orders yet</p>
                <a href="/products.php" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-border-separator-3 transition-colors">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Order Header -->
                        <div class="p-6 border-b border-border-separator-1">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-accesibel-text-color-2">
                                        Order #<?php echo $order['id']; ?>
                                    </h3>
                                    <p class="text-accesibel-text-color-3">
                                        <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm
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
                            
                            <div class="flex justify-between items-center">
                                <p class="text-accesibel-text-color-3">
                                    Seller:
                                    <span class="text-accesibel-text-color-2 font-medium">
                                        <?php echo htmlspecialchars($order['store_name']); ?>
                                    </span>
                                </p>
                                <p class="text-lg font-semibold text-primary">
                                    Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach (getOrderItems($conn, $order['id']) as $item): ?>
                                    <div class="flex items-center space-x-4">
                                        <img src="<?php echo $item['image'] ? '/uploads/products/' . $item['image'] : '/images/default-product.jpg'; ?>"
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-16 h-16 object-cover rounded-md">
                                        <div class="flex-1">
                                            <h4 class="text-accesibel-text-color-2 font-medium">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h4>
                                            <p class="text-accesibel-text-color-3 text-sm">
                                                Quantity: <?php echo $item['quantity']; ?> × 
                                                Rp <?php echo number_format($item['price_per_unit'], 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-accesibel-text-color-2 font-medium">
                                                Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Shipping Information -->
                            <div class="mt-6 pt-6 border-t border-border-separator-1">
                                <h4 class="text-accesibel-text-color-2 font-medium mb-2">Shipping Information</h4>
                                <p class="text-accesibel-text-color-3">
                                    Address: <?php echo htmlspecialchars($order['shipping_address']); ?>
                                </p>
                                <p class="text-accesibel-text-color-3">
                                    Phone: <?php echo htmlspecialchars($order['phone_number']); ?>
                                </p>
                            </div>

                            <!-- Order Actions -->
                            <div class="mt-6 flex justify-between items-center">
                                <?php if ($order['status'] === 'Delivered'): ?>
                                    <?php
                                    // Check if review exists
                                    $stmt = $conn->prepare("SELECT id FROM reviews WHERE order_id = ?");
                                    $stmt->execute([$order['id']]);
                                    $hasReview = $stmt->fetch();
                                    ?>
                                    <?php if (!$hasReview): ?>
                                        <button onclick="openReviewModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['store_name']); ?>')" 
                                                class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3 transition-colors">
                                            Write Review
                                        </button>
                                    <?php else: ?>
                                        <span class="text-accesibel-text-color-3">Review submitted</span>
                                    <?php endif; ?>
                                <?php elseif (in_array($order['status'], ['Pending', 'Processing'])): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="cancel_order" 
                                                class="text-red-500 hover:text-red-600">
                                            Cancel Order
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'Shipped'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="confirm_delivery" 
                                                class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3 transition-colors">
                                            Confirm Delivery
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-box">
            <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">Write a Review</h3>
            <form id="reviewForm" method="POST" action="/api/reviews/create.php">
                <input type="hidden" name="order_id" id="reviewOrderId">
                <input type="hidden" name="store_id" id="reviewStoreId">
                
                <div class="mb-4">
                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                        Rating
                    </label>
                    <div class="flex space-x-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                   class="star-rating" required>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                        Comment
                    </label>
                    <textarea name="comment" rows="4" required
                              class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:border-primary"></textarea>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeReviewModal()"
                            class="px-4 py-2 border border-border-separator-1 rounded-md hover:bg-interactive-1">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3 transition-colors">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(orderId, storeName) {
            document.getElementById('reviewOrderId').value = orderId;
            document.getElementById('reviewModal').classList.add('modal-open');
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('modal-open');
        }

        // Handle review form submission
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Review submitted successfully', 'success');
                    closeReviewModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Failed to submit review', 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>