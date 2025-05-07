<?php
// stores/view.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';
require_once '../components/review-summary.php';
require_once '../components/product-card.php';

// Validasi dan sanitasi input
$store_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$store_id) {
    header('Location: ' . BASE_URL . '/stores/');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die('Database connection error: ' . $e->getMessage());
}

// Get store information with optimized query
try {
    $stmt = $conn->prepare("
        SELECT s.*, 
               u.full_name as farmer_name,
               u.phone_number as farmer_phone,
               u.email as farmer_email,
               COUNT(p.id) as product_count,
               AVG(r.rating) as average_rating,
               COUNT(r.id) as review_count
        FROM stores s
        LEFT JOIN users u ON s.farmer_id = u.id
        LEFT JOIN products p ON p.store_id = s.id AND p.status = 'Available'
        LEFT JOIN reviews r ON r.store_id = s.id
        WHERE s.id = ?
        GROUP BY s.id, u.id
    ");
    $stmt->execute([$store_id]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        header('Location: ' . BASE_URL . '/stores/');
        exit;
    }
} catch (Exception $e) {
    die('Error fetching store data: ' . $e->getMessage());
}

// Inisialisasi variables di awal file, setelah koneksi database
$category_id = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = $_GET['page'] ?? 1;
$per_page = 12;  // Items per page

// Modifikasi query untuk menghitung total products dulu
$countQuery = "
    SELECT COUNT(*) as total
    FROM products p
    WHERE p.store_id = ? 
    AND p.status = 'Available'
";
$countParams = [$store_id];

if ($category_id) {
    $countQuery .= " AND p.category_id = ?";
    $countParams[] = $category_id;
}

if ($search) {
    $countQuery .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

$stmt = $conn->prepare($countQuery);
$stmt->execute($countParams);
$total_products = $stmt->fetchColumn();

// Hitung total pages, tambahkan pengecekan untuk menghindari division by zero
$total_pages = $total_products > 0 ? ceil($total_products / $per_page) : 1;

// Calculate offset
$offset = ($page - 1) * $per_page;

// Main query untuk products
$query = "
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.store_id = ? 
    AND p.status = 'Available'
";
$params = [$store_id];

if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add sorting
$query .= match($sort) {
    'price_low' => " ORDER BY p.price ASC",
    'price_high' => " ORDER BY p.price DESC",
    default => " ORDER BY p.created_at DESC"
};

// Add pagination
$query .= " LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo htmlspecialchars($store['store_name']); ?> - Local Farmer Connect</title>
</head>

<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Store Header -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="h-64 bg-cover bg-center"
                style="background-image: url('<?php echo BASE_URL; ?>/uploads/stores/<?php echo $store['profile_picture'] ?: 'default-store.jpg'; ?>');">
            </div>
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-semibold text-accesibel-text-color-2">
                            <?php echo htmlspecialchars($store['store_name']); ?>
                        </h1>
                        <p class="text-accesibel-text-color-3 mt-2">
                            by <?php echo htmlspecialchars($store['farmer_name']); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="h-5 w-5 <?php echo $i <= ($store['average_rating'] ?? 0) ?
                                                            'text-yellow-400' : 'text-gray-300'; ?>"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="ml-2 text-accesibel-text-color-3">
                                <?php echo number_format($store['average_rating'] ?? 0, 1); ?>
                                (<?php echo $store['review_count']; ?> reviews)
                            </span>
                        </div>
                        <p class="text-accesibel-text-color-3 mt-1">
                            <?php echo $store['product_count']; ?> products available
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-lg font-medium text-accesibel-text-color-2 mb-2">About the Store</h2>
                    <p class="text-accesibel-text-color-3">
                        <?php echo nl2br(htmlspecialchars($store['description'])); ?>
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap gap-4">
                    <div>
                        <h3 class="text-sm text-accesibel-text-color-3 mb-1">Location</h3>
                        <p class="text-accesibel-text-color-2">
                            <?php echo htmlspecialchars($store['address']); ?>
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm text-accesibel-text-color-3 mb-1">Contact</h3>
                        <p class="text-accesibel-text-color-2">
                            <?php echo htmlspecialchars($store['phone_number']); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-medium text-accesibel-text-color-2 mb-4">Filters</h2>

                    <form method="GET" class="space-y-4">
                        <input type="hidden" name="id" value="<?php echo $store_id; ?>">

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Search Products
                            </label>
                            <input type="text" name="search"
                                value="<?php echo htmlspecialchars($search); ?>"
                                class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Category
                            </label>
                            <select name="category"
                                class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                        (<?php echo $cat['product_count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Sort By
                            </label>
                            <select name="sort"
                                class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                                    Newest
                                </option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>
                                    Price: Low to High
                                </option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>
                                    Price: High to Low
                                </option>
                            </select>
                        </div>

                        <button type="submit"
                            class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                            Apply Filters
                        </button>
                    </form>
                </div>

                <!-- Store Reviews Summary -->
                <?php renderReviewSummary($store_id); ?>
            </div>

            <!-- Products Grid -->
            <div class="lg:col-span-3">
                <?php if (empty($products)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <h3 class="text-xl text-accesibel-text-color-2 mb-2">No products found</h3>
                        <p class="text-accesibel-text-color-3">
                            Try adjusting your filters or search terms
                        </p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($products as $product): ?>
                            <?php renderProductCard2($product, $store); ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center mt-8">
                            <div class="join">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                        class="join-item btn <?php echo $page == $i ? 'btn-primary' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>