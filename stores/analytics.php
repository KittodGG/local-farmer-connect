<?php
// store/analytics.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isFarmer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get store info
$stmt = $conn->prepare("SELECT * FROM stores WHERE farmer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header('Location: ' . BASE_URL . '/store/manage.php');
    exit;
}

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Sales Overview
$stmt = $conn->prepare("
    SELECT 
        DATE(o.created_at) as date,
        COUNT(DISTINCT o.id) as order_count,
        SUM(o.total_amount) as revenue
    FROM orders o
    WHERE o.store_id = ? 
    AND o.status != 'Cancelled'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY date
");
$stmt->execute([$store['id'], $start_date, $end_date]);
$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Products
$stmt = $conn->prepare("
    SELECT 
        p.name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.subtotal) as total_revenue,
        COUNT(DISTINCT o.id) as order_count
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE p.store_id = ? 
    AND o.status != 'Cancelled'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 5
");
$stmt->execute([$store['id'], $start_date, $end_date]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rating Analysis
$stmt = $conn->prepare("
    SELECT 
        rating,
        COUNT(*) as count
    FROM reviews
    WHERE store_id = ?
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY rating
    ORDER BY rating DESC
");
$stmt->execute([$store['id'], $start_date, $end_date]);
$ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overall Statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(o.total_amount) as total_revenue,
        COUNT(DISTINCT o.user_id) as unique_customers,
        AVG(o.total_amount) as average_order_value
    FROM orders o
    WHERE o.store_id = ? 
    AND o.status != 'Cancelled'
    AND DATE(o.created_at) BETWEEN ? AND ?
");
$stmt->execute([$store['id'], $start_date, $end_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Store Analytics - Local Farmer Connect</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-semibold text-accesibel-text-color-2">Store Analytics</h1>
            
            <!-- Date Range Selector -->
            <form method="GET" class="flex items-center space-x-4">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                       class="border border-border-separator-1 rounded-md px-3 py-2">
                <span class="text-accesibel-text-color-3">to</span>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                       class="border border-border-separator-1 rounded-md px-3 py-2">
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                    Apply
                </button>
            </form>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Total Orders</h3>
                <p class="text-2xl font-semibold text-accesibel-text-color-2">
                    <?php echo number_format($stats['total_orders']); ?>
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Total Revenue</h3>
                <p class="text-2xl font-semibold text-primary">
                    Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?>
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Unique Customers</h3>
                <p class="text-2xl font-semibold text-accesibel-text-color-2">
                    <?php echo number_format($stats['unique_customers']); ?>
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Average Order Value</h3>
                <p class="text-2xl font-semibold text-primary">
                    Rp <?php echo number_format($stats['average_order_value'], 0, ',', '.'); ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Sales Chart -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">Daily Sales</h2>
                <canvas id="salesChart"></canvas>
            </div>

            <!-- Ratings Distribution -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">Rating Distribution</h2>
                <canvas id="ratingsChart"></canvas>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h2 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">Top Performing Products</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-interactive-1">
                        <tr>
                            <th class="px-6 py-3 text-left text-accesibel-text-color-2">Product</th>
                            <th class="px-6 py-3 text-right text-accesibel-text-color-2">Orders</th>
                            <th class="px-6 py-3 text-right text-accesibel-text-color-2">Units Sold</th>
                            <th class="px-6 py-3 text-right text-accesibel-text-color-2">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr class="border-b border-border-separator-1">
                                <td class="px-6 py-4 text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </td>
                                <td class="px-6 py-4 text-right text-accesibel-text-color-2">
                                    <?php echo number_format($product['order_count']); ?>
                                </td>
                                <td class="px-6 py-4 text-right text-accesibel-text-color-2">
                                    <?php echo number_format($product['total_quantity']); ?>
                                </td>
                                <td class="px-6 py-4 text-right text-primary font-medium">
                                    Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($daily_sales, 'revenue')); ?>,
                    borderColor: '#52A447',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'Rp ' + value.toLocaleString()
                        }
                    }
                }
            }
        });

        // Ratings Chart
        const ratingsCtx = document.getElementById('ratingsChart').getContext('2d');
        new Chart(ratingsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($ratings, 'rating')); ?>,
                datasets: [{
                    label: 'Number of Reviews',
                    data: <?php echo json_encode(array_column($ratings, 'count')); ?>,
                    backgroundColor: '#52A447'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>