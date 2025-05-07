<?php
// admin/orders.php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_start = $_GET['date_start'] ?? date('Y-m-d', strtotime('-30 days'));
$date_end = $_GET['date_end'] ?? date('Y-m-d');
$page = $_GET['page'] ?? 1;
$per_page = 10;

// Query utama untuk data orders
$params = [];
$query = "
    SELECT o.id, 
           o.total_amount,
           o.status,
           o.created_at,
           o.shipping_address,
           o.phone_number,
           u.full_name as customer_name,
           s.store_name,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN stores s ON o.store_id = s.id
    WHERE 1=1
";

// Tambahkan filter status jika ada
if ($status !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

// Tambahkan filter pencarian jika ada
if ($search) {
    $query .= " AND (o.id LIKE ? OR u.full_name LIKE ? OR s.store_name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

// Filter tanggal
$query .= " AND DATE(o.created_at) BETWEEN ? AND ?";
$params[] = $date_start;
$params[] = $date_end;

// Order dan pagination
$query .= " ORDER BY o.created_at DESC";
$query .= " LIMIT " . (int)$per_page . " OFFSET " . (int)(($page - 1) * $per_page);

// Query total untuk pagination
$totalQuery = "
    SELECT COUNT(*) as total 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN stores s ON o.store_id = s.id
    WHERE 1=1
";
$totalParams = [];

// Tambahkan filter ke query total
if ($status !== 'all') {
    $totalQuery .= " AND o.status = ?";
    $totalParams[] = $status;
}

if ($search) {
    $totalQuery .= " AND (o.id LIKE ? OR u.full_name LIKE ? OR s.store_name LIKE ?)";
    $totalParams = array_merge($totalParams, ["%$search%", "%$search%", "%$search%"]);
}

$totalQuery .= " AND DATE(o.created_at) BETWEEN ? AND ?";
$totalParams[] = $date_start;
$totalParams[] = $date_end;

try {
    // Debug query dan parameter
    echo "<!-- Query: " . $query . " -->\n";
    echo "<!-- Params: " . print_r($params, true) . " -->\n";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Eksekusi query total
    $totalStmt = $conn->prepare($totalQuery);
    $totalStmt->execute($totalParams);
    $totalOrders = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($totalOrders / $per_page);
} catch (PDOException $e) {
    $total_pages = 1; // Default jika terjadi error
    echo "Error: " . $e->getMessage();
    echo "<pre>";
    print_r([
        'query' => $query,
        'params' => $params,
        'error' => $e->getMessage()
    ]);
    echo "</pre>";
    die();
}

// Statistik sederhana
$statsQuery = "
    SELECT 
        COUNT(id) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
";

try {
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->execute([$date_start, $date_end]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'shipped_orders' => 0,
        'delivered_orders' => 0,
        'cancelled_orders' => 0
    ];
}
?>


<!-- Debug info di halaman -->
<?php if (isset($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <p><?php echo $error; ?></p>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Management - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
<body class="bg-background min-h-screen">
    <?php require_once '../includes/admin-nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-semibold text-accesibel-text-color-2">Order Management</h1>
            
            <!-- Date Range Selector -->
            <form method="GET" class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <input type="date" name="date_start" value="<?php echo $date_start; ?>"
                           class="border border-border-separator-1 rounded-md px-3 py-2">
                    <span class="text-accesibel-text-color-3">to</span>
                    <input type="date" name="date_end" value="<?php echo $date_end; ?>"
                           class="border border-border-separator-1 rounded-md px-3 py-2">
                </div>
                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                    Apply
                </button>
            </form>
        </div>

        <!-- Order Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Total Orders</h3>
                <p class="text-2xl font-semibold text-accesibel-text-color-2">
                    <?php echo number_format($stats['total_orders']); ?>
                </p>
                <p class="text-primary font-medium mt-1">
                    Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Pending</h3>
                <p class="text-2xl font-semibold text-yellow-600">
                    <?php echo number_format($stats['pending_orders'] ?? 0); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Processing</h3>
                <p class="text-2xl font-semibold text-blue-600">
                    <?php echo number_format($stats['processing_orders'] ?? 0); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Shipped</h3>
                <p class="text-2xl font-semibold text-purple-600">
                    <?php echo number_format($stats['shipped_orders'] ?? 0); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Delivered</h3>
                <p class="text-2xl font-semibold text-green-600">
                    <?php echo number_format($stats['delivered_orders'] ?? 0); ?>
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="hidden" name="date_start" value="<?php echo $date_start; ?>">
                <input type="hidden" name="date_end" value="<?php echo $date_end; ?>">
                
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by order ID, customer, or store"
                           class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                </div>

                <div>
                    <select name="status" 
                            class="border border-border-separator-1 rounded-md px-3 py-2">
                        <option value="all">All Status</option>
                        <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="Shipped" <?php echo $status === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="Delivered" <?php echo $status === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                    Filter
                </button>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-border-separator-1">
                <thead class="bg-interactive-1">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Order ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Customer
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Store
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Items
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Total
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border-separator-1">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-accesibel-text-color-2">
                                    #<?php echo $order['id']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['store_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-accesibel-text-color-2">
                                    <?php echo number_format($order['item_count']); ?> items
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-primary">
                                    Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-accesibel-text-color-3">
                                    <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="<?php echo BASE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>"
                                   class="text-primary hover:text-border-separator-3">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-accesibel-text-color-3">
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center py-4 border-t border-border-separator-1">
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
        </div>
    </div>
</body>
</html>