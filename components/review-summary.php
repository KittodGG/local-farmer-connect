<?php
// components/review-summary.php

function renderReviewSummary($store_id) {
    $db = new Database();
    $conn = $db->getConnection();

    // Get rating summary
    $stmt = $conn->prepare("
        SELECT AVG(rating) as average, COUNT(*) as total
        FROM reviews
        WHERE store_id = ?
    ");
    $stmt->execute([$store_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($summary['total'] > 0):
    ?>
    <div class="bg-white rounded-lg shadow-md p-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-primary">
                        <?php echo number_format($summary['average'], 1); ?>
                    </span>
                    <div class="flex text-yellow-400 ml-2">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <svg class="h-5 w-5 <?php echo $i <= round($summary['average']) ? 'text-yellow-400' : 'text-gray-300'; ?>"
                                 fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="text-sm text-accesibel-text-color-3">
                    <?php echo number_format($summary['total']); ?> reviews
                </p>
            </div>
            <a href="<?php echo BASE_URL; ?>/reviews/list.php?store_id=<?php echo $store_id; ?>"
               class="text-primary hover:text-border-separator-3">
                View All Reviews
            </a>
        </div>

        <?php
        // Get recent reviews
        $stmt = $conn->prepare("
            SELECT r.*, u.full_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.store_id = ?
            ORDER BY r.created_at DESC
            LIMIT 2
        ");
        $stmt->execute([$store_id]);
        $recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($recent_reviews)):
        ?>
            <div class="space-y-4">
                <?php foreach ($recent_reviews as $review): ?>
                    <div class="border-t border-border-separator-1 pt-4">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-medium text-accesibel-text-color-2">
                                <?php echo htmlspecialchars($review['full_name']); ?>
                            </span>
                            <div class="flex text-yellow-400">
                                <?php for($i = 1; $i <= $review['rating']; $i++): ?>
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="text-sm text-accesibel-text-color-3">
                            <?php echo htmlspecialchars(substr($review['comment'], 0, 100)) . 
                                  (strlen($review['comment']) > 100 ? '...' : ''); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    endif;
}
?>