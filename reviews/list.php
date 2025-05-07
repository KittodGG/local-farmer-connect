<?php
// reviews/list.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

$store_id = $_GET['store_id'] ?? null;
if (!$store_id) {
    header('Location: ' . BASE_URL . '/stores/');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get store information
$stmt = $conn->prepare("
    SELECT s.*, u.full_name as farmer_name,
           (SELECT COUNT(*) FROM products WHERE store_id = s.id) as product_count,
           (SELECT AVG(rating) FROM reviews WHERE store_id = s.id) as average_rating,
           (SELECT COUNT(*) FROM reviews WHERE store_id = s.id) as review_count
    FROM stores s
    JOIN users u ON s.farmer_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$store_id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header('Location: ' . BASE_URL . '/stores/');
    exit;
}

// Get rating statistics
$stmt = $conn->prepare("
    SELECT rating, COUNT(*) as count
    FROM reviews
    WHERE store_id = ?
    GROUP BY rating
    ORDER BY rating DESC
");
$stmt->execute([$store_id]);
$ratings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total_reviews = array_sum($ratings);
$rating_percentages = array_map(function($count) use ($total_reviews) {
    return ($count / $total_reviews) * 100;
}, $ratings);

// Get reviews with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("
    SELECT r.*, u.full_name, u.profile_picture
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.store_id = :store_id
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':store_id', $store_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute(); // No arguments passed here
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Get total pages
$stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE store_id = ?");
$stmt->execute([$store_id]);
$total_reviews = $stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($store['store_name']); ?> Reviews - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Store Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-accesibel-text-color-2">
                        <?php echo htmlspecialchars($store['store_name']); ?>
                    </h1>
                    <p class="text-accesibel-text-color-3">
                        by <?php echo htmlspecialchars($store['farmer_name']); ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="flex items-center">
                        <span class="text-2xl font-bold text-primary">
                            <?php echo number_format($store['average_rating'], 1); ?>
                        </span>
                        <span class="text-accesibel-text-color-3 ml-2">
                            out of 5
                        </span>
                    </div>
                    <p class="text-accesibel-text-color-3">
                        <?php echo number_format($store['review_count']); ?> reviews
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Rating Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">
                        Rating Summary
                    </h2>
                    <div class="space-y-3">
                        <?php for($i = 5; $i >= 1; $i--): ?>
                            <div class="flex items-center">
                                <span class="w-12 text-accesibel-text-color-2">
                                    <?php echo $i; ?> ★
                                </span>
                                <div class="flex-1 mx-4">
                                    <div class="h-2 bg-border-separator-1 rounded-full">
                                        <div class="h-2 bg-primary rounded-full"
                                             style="width: <?php echo $rating_percentages[$i] ?? 0; ?>%"></div>
                                    </div>
                                </div>
                                <span class="w-12 text-right text-accesibel-text-color-3">
                                    <?php echo $ratings[$i] ?? 0; ?>
                                </span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-6">
                        Customer Reviews
                    </h2>

                    <?php if (empty($reviews)): ?>
                        <p class="text-center text-accesibel-text-color-3 py-8">
                            No reviews yet.
                        </p>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b border-border-separator-1 last:border-0 pb-6 last:pb-0">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center">
                                            <img src="<?php echo BASE_URL; ?>/uploads/users/<?php echo $review['profile_picture'] ?: 'default.jpg'; ?>"
                                                 alt="Profile" class="w-10 h-10 rounded-full mr-4">
                                            <div>
                                                <h3 class="font-medium text-accesibel-text-color-2">
                                                    <?php echo htmlspecialchars($review['full_name']); ?>
                                                </h3>
                                                <p class="text-sm text-accesibel-text-color-3">
                                                    <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex text-yellow-400">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <svg class="h-5 w-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-accesibel-text-color-2">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="flex justify-center mt-6">
                                <div class="join">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?store_id=<?php echo $store_id; ?>&page=<?php echo $i; ?>"
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
    </div>
</body>
</html>