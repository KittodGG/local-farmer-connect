<?php
// reviews/create.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isCustomer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header('Location: ' . BASE_URL . '/orders/');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verify order and check if review already exists
$stmt = $conn->prepare("
    SELECT o.*, s.store_name, s.id as store_id,
           (SELECT COUNT(*) FROM reviews WHERE order_id = o.id) as has_review
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'Delivered'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || $order['has_review'] > 0) {
    header('Location: ' . BASE_URL . '/orders/');
    exit;
}

// Process review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);

        if ($rating < 1 || $rating > 5) {
            throw new Exception('Please select a valid rating');
        }

        if (empty($comment)) {
            throw new Exception('Please provide a review comment');
        }

        $stmt = $conn->prepare("
            INSERT INTO reviews (user_id, store_id, order_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $order['store_id'],
            $order_id,
            $rating,
            $comment
        ]);

        setFlashMessage('success', 'Thank you for your review!');
        header('Location: ' . BASE_URL . '/orders/detail.php?id=' . $order_id);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Write Review - Local Farmer Connect</title>
    <style>
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 0.5rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            color: #CBD5E0;
            font-size: 2rem;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #F59E0B;
        }
    </style>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-6">
                    Write a Review
                </h1>

                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <h2 class="text-lg font-medium text-accesibel-text-color-2">
                        <?php echo htmlspecialchars($order['store_name']); ?>
                    </h2>
                    <p class="text-accesibel-text-color-3">
                        Order #<?php echo $order['id']; ?>
                    </p>
                </div>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-4">
                            Your Rating
                        </label>
                        <div class="star-rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                       id="star<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                            Your Review
                        </label>
                        <textarea name="comment" rows="4" required
                                  placeholder="Share your experience with this store and their products..."
                                  class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:border-primary"
                        ></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="<?php echo BASE_URL; ?>/orders/detail.php?id=<?php echo $order_id; ?>"
                           class="px-4 py-2 border border-border-separator-1 rounded-md hover:bg-interactive-1">
                            Cancel
                        </a>
                        <button type="submit"
                                class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>