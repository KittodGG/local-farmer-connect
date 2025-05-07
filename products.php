<?php
// products.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';
require_once 'includes/header.php';

$db = new Database();
$conn = $db->getConnection();

// Get filters
$category_id = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? null;
$max_price = $_GET['max_price'] ?? null;
$page = $_GET['page'] ?? 1;
$per_page = 12;

// Base query
$query = "SELECT p.*, s.store_name, c.name as category_name 
          FROM products p 
          JOIN stores s ON p.store_id = s.id 
          JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'Available'";
$params = [];

// Apply filters
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($min_price) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
}

if ($max_price) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
}

// Get total count (adjusted to fix the ONLY_FULL_GROUP_BY error)
$count_query = "SELECT COUNT(*) 
                FROM products p 
                JOIN stores s ON p.store_id = s.id 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'Available'";
if ($category_id) {
    $count_query .= " AND p.category_id = ?";
}
if ($search) {
    $count_query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
}
if ($min_price) {
    $count_query .= " AND p.price >= ?";
}
if ($max_price) {
    $count_query .= " AND p.price <= ?";
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $per_page OFFSET $offset";

// Get products
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Products - Local Farmer Connect</title>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="<?php echo BASE_URL; ?>/products.php" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:border-primary">
                    </div>
                    
                    <!-- Category -->
                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">Category</label>
                        <select name="category" class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">Price Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price; ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                            <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price; ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-border-separator-3">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image'] ?: 'default.jpg'; ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-accesibel-text-color-2">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        <p class="text-accesibel-text-color-3 text-sm">
                            <?php echo htmlspecialchars($product['store_name']); ?>
                        </p>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-primary font-semibold">
                                Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                            </span>
                            <?php if (isCustomer()): ?>
                                <button onclick="addToCart(<?php echo $product['id']; ?>)"
                                        class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                                    Add to Cart
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-8">
                <div class="join">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo BASE_URL; ?>/products.php?page=<?php echo $i; ?>&<?php echo http_build_query([
                            'category' => $category_id,
                            'search' => $search,
                            'min_price' => $min_price,
                            'max_price' => $max_price
                        ]); ?>"
                           class="join-item btn <?php echo $page == $i ? 'btn-primary' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(productId) {
            fetch('<?php echo BASE_URL; ?>/api/cart/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                } else {
                    alert(data.message || 'Failed to add product to cart');
                }
            })
            .catch(error => {
                alert('An error occurred');
                console.error('Error:', error);
            });
        }
    </script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
