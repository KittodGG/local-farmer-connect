<?php
// products/detail.php
require_once '../config/database.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get product with store and category information
$stmt = $conn->prepare("
    SELECT p.*, 
           c.name as category_name,
           s.store_name, s.id as store_id, s.address as store_address,
           u.full_name as farmer_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN stores s ON p.store_id = s.id
    JOIN users u ON s.farmer_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

// Get other products from same store
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.store_id = ? 
    AND p.id != ? 
    AND p.status = 'Available'
    LIMIT 4
");
$stmt->execute([$product['store_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get store rating
$stmt = $conn->prepare("
    SELECT AVG(rating) as average_rating, COUNT(*) as review_count
    FROM reviews
    WHERE store_id = ?
");
$stmt->execute([$product['store_id']]);
$store_rating = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2">
                <li><a href="<?php echo BASE_URL; ?>/" class="text-accesibel-text-color-3 hover:text-primary">Home</a></li>
                <li class="text-accesibel-text-color-3">/</li>
                <li><a href="<?php echo BASE_URL; ?>/products.php" class="text-accesibel-text-color-3 hover:text-primary">Products</a></li>
                <li class="text-accesibel-text-color-3">/</li>
                <li class="text-accesibel-text-color-2"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Image -->
            <div>
                <div class="bg-white rounded-lg overflow-hidden shadow-md">
                    <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image'] ?: 'default.jpg'; ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-96 object-cover">
                </div>
            </div>

            <!-- Product Info -->
            <div class="space-y-6">
                <div>
                    <h1 class="text-3xl font-semibold text-accesibel-text-color-2">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h1>
                    <p class="text-accesibel-text-color-3 mt-2">
                        Category: <?php echo htmlspecialchars($product['category_name']); ?>
                    </p>
                </div>

                <div>
                    <p class="text-3xl font-bold text-primary">
                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                    </p>
                    <p class="text-accesibel-text-color-3 mt-1">
                        Stock: <?php echo number_format($product['stock']); ?> available
                    </p>
                </div>

                <div class="border-t border-b border-border-separator-1 py-6">
                    <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-2">Description</h2>
                    <p class="text-accesibel-text-color-3">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>

                <?php if (isCustomer() && $product['status'] === 'Available'): ?>
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Quantity
                            </label>
                            <input type="number" id="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1"
                                   class="w-24 border border-border-separator-1 rounded-md px-3 py-2">
                        </div>
                        <button onclick="addToCart(<?php echo $product['id']; ?>)"
                                class="flex-1 bg-primary text-white px-6 py-3 rounded-md hover:bg-border-separator-3">
                            Add to Cart
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Store Info -->
                <div class="bg-interactive-1 rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-accesibel-text-color-2">
                                Sold by <?php echo htmlspecialchars($product['store_name']); ?>
                            </h3>
                            <p class="text-accesibel-text-color-3">
                                <?php echo htmlspecialchars($product['farmer_name']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center">
                                <div class="flex text-yellow-400">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <svg class="h-5 w-5 <?php echo $i <= ($store_rating['average_rating'] ?? 0) ? 
                                                          'text-yellow-400' : 'text-gray-300'; ?>"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <span class="ml-2 text-accesibel-text-color-3">
                                    (<?php echo number_format($store_rating['review_count'] ?? 0); ?> reviews)
                                </span>
                            </div>
                        </div>
                    </div>
                    <p class="text-accesibel-text-color-3 mb-4">
                        <?php echo htmlspecialchars($product['store_address']); ?>
                    </p>
                    <a href="<?php echo BASE_URL; ?>/stores/view.php?id=<?php echo $product['store_id']; ?>"
                       class="inline-block text-primary hover:text-border-separator-3">
                        Visit Store →
                    </a>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-semibold text-accesibel-text-color-2 mb-6">
                    More Products from this Store
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($related_products as $related): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <a href="<?php echo BASE_URL; ?>/products/detail.php?id=<?php echo $related['id']; ?>">
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $related['image'] ?: 'default.jpg'; ?>"
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     class="w-full h-48 object-cover">
                            </a>
                            <div class="p-4">
                                <h3 class="font-medium text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </h3>
                                <p class="text-primary font-medium mt-2">
                                    Rp <?php echo number_format($related['price'], 0, ',', '.'); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(productId) {
            const quantity = parseInt(document.getElementById('quantity').value);
            
            fetch(`${BASE_URL}/api/cart/add.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Added to cart successfully');
                    // Optional: Update cart counter in header if you have one
                } else {
                    showToast(data.message || 'Failed to add to cart', 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>