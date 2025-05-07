<?php
// stores/index.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';
require_once '../components/review-summary.php';

$db = new Database();
$conn = $db->getConnection();

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'rating'; // rating, products, newest
$page = $_GET['page'] ?? 1;
$per_page = 12;

$query = "
    SELECT s.*, 
           u.full_name as farmer_name,
           COUNT(DISTINCT p.id) as product_count,
           AVG(r.rating) as average_rating,
           COUNT(r.id) as review_count
    FROM stores s
    JOIN users u ON s.farmer_id = u.id
    LEFT JOIN products p ON s.id = p.store_id
    LEFT JOIN reviews r ON s.id = r.store_id
    WHERE 1=1
    GROUP BY s.id, u.full_name
";

$params = [];

if ($search) {
    $query .= " AND (s.store_name LIKE ? OR s.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND c.id = ?";
    $params[] = $category;
}

// Add sorting
$query .= match($sort) {
    'rating' => " ORDER BY IFNULL(AVG(r.rating), 0) DESC",
    'products' => " ORDER BY product_count DESC",
    'newest' => " ORDER BY s.created_at DESC",
    default => " ORDER BY IFNULL(AVG(r.rating), 0) DESC"
};

// Get total count for pagination
$count_stmt = $conn->prepare(str_replace("SELECT DISTINCT s.*", "SELECT COUNT(DISTINCT s.id)", $query));
$count_stmt->execute($params);
$total_stores = $count_stmt->fetchColumn();
$total_pages = ceil($total_stores / $per_page);

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $per_page OFFSET $offset";

// Get stores
$stmt = $conn->prepare($query);
$stmt->execute($params);
$stores = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get categories for filter
$categories = $conn->query("
    SELECT c.*, COUNT(DISTINCT p.store_id) as store_count
    FROM categories c
    JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    HAVING store_count > 0
    ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Local Farmers - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-semibold text-accesibel-text-color-2">Local Farmers</h1>
            <?php if (isFarmer()): ?>
                <a href="<?php echo BASE_URL; ?>/store/manage.php" 
                   class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                    Manage Your Store
                </a>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                        Search Stores
                    </label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by name or description"
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
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?> 
                                (<?php echo $cat['store_count']; ?>)
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
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>
                            Top Rated
                        </option>
                        <option value="products" <?php echo $sort === 'products' ? 'selected' : ''; ?>>
                            Most Products
                        </option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                            Newest
                        </option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" 
                            class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3 w-full">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Stores Grid -->
        <?php if (empty($stores)): ?>
            <div class="text-center py-12 bg-white rounded-lg shadow-md">
                <h3 class="text-xl text-accesibel-text-color-2 mb-4">No stores found</h3>
                <p class="text-accesibel-text-color-3">
                    Try adjusting your filters or search terms
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($stores as $store): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <a href="<?php echo BASE_URL; ?>/stores/view.php?id=<?php echo $store['id']; ?>">
                            <img src="<?php echo BASE_URL; ?>/uploads/stores/<?php echo $store['profile_picture'] ?: 'default-store.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($store['store_name']); ?>"
                                 class="w-full h-48 object-cover">
                        </a>
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-accesibel-text-color-2 mb-2">
                                <a href="<?php echo BASE_URL; ?>/stores/view.php?id=<?php echo $store['id']; ?>"
                                   class="hover:text-primary">
                                    <?php echo htmlspecialchars($store['store_name']); ?>
                                </a>
                            </h2>
                            <p class="text-accesibel-text-color-3 mb-4">
                                by <?php echo htmlspecialchars($store['farmer_name']); ?>
                            </p>
                            
                            <div class="flex items-center mb-4">
                                <div class="flex text-yellow-400">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <svg class="h-5 w-5 <?php echo $i <= ($store['average_rating'] ?? 0) ? 
                                                           'text-yellow-400' : 'text-gray-300'; ?>"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-accesibel-text-color-3">
                                        (<?php echo $store['review_count'] ?? 0; ?>)
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-accesibel-text-color-3">
                                    <?php echo $store['product_count']; ?> Products
                                </span>
                                <a href="<?php echo BASE_URL; ?>/stores/view.php?id=<?php echo $store['id']; ?>"
                                   class="text-primary hover:text-border-separator-3">
                                    View Store →
                                </a>
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
</body>
</html>