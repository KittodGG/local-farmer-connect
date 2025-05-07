<?php
// admin/users.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get users with their stores (if any)
$stmt = $conn->query("
    SELECT u.*, 
           s.store_name,
           (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count
    FROM users u
    LEFT JOIN stores s ON u.id = s.farmer_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Management - Local Farmer Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
<body class="bg-background font-sans min-h-screen">
    <?php include '../includes/admin-nav.php'; ?>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-accesibel-text-color-2 mb-8">User Management</h1>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-border-separator-1">
                <thead class="bg-interactive-1">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Store</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Orders</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Joined</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border-separator-1">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img class="h-10 w-10 rounded-full" src="<?php echo BASE_URL; ?>/uploads/users/<?php echo $user['profile_picture'] ?: 'default.jpg'; ?>" alt="">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-accesibel-text-color-2">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </div>
                                        <div class="text-sm text-accesibel-text-color-3">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo match($user['role']) {
                                        'Admin' => 'bg-purple-100 text-purple-800',
                                        'Farmer' => 'bg-green-100 text-green-800',
                                        'Customer' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    }; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-3">
                                <?php echo htmlspecialchars($user['store_name'] ?: '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-2">
                                <?php echo number_format($user['order_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-3">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="viewUserDetails(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                        class="text-primary hover:text-border-separator-3">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-accesibel-text-color-2" id="modal-title">
                                User Details
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div class="flex items-center">
                                    <img id="userImage" src="" alt="Profile" class="w-20 h-20 rounded-full mr-4">
                                    <div>
                                        <h4 id="userName" class="text-lg font-medium text-accesibel-text-color-2"></h4>
                                        <p id="userEmail" class="text-accesibel-text-color-3"></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-accesibel-text-color-3">Phone</label>
                                        <p id="userPhone" class="text-accesibel-text-color-2"></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-accesibel-text-color-3">Role</label>
                                        <p id="userRole" class="text-accesibel-text-color-2"></p>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm text-accesibel-text-color-3">Address</label>
                                    <p id="userAddress" class="text-accesibel-text-color-2"></p>
                                </div>
                                <div id="storeDetails" class="border-t border-border-separator-1 pt-4 mt-4 hidden">
                                    <h4 class="font-medium text-accesibel-text-color-2 mb-2">Store Information</h4>
                                    <p id="storeName" class="text-accesibel-text-color-2"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-border-separator-3 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';

        function viewUserDetails(user) {
            document.getElementById('userName').textContent = user.full_name;
            document.getElementById('userEmail').textContent = user.email;
            document.getElementById('userPhone').textContent = user.phone_number || '-';
            document.getElementById('userRole').textContent = user.role;
            document.getElementById('userAddress').textContent = user.address || '-';
            document.getElementById('userImage').src = `${BASE_URL}/uploads/users/${user.profile_picture || 'default.jpg'}`;

            const storeDetails = document.getElementById('storeDetails');
            if (user.store_name) {
                storeDetails.classList.remove('hidden');
                document.getElementById('storeName').textContent = user.store_name;
            } else {
                storeDetails.classList.add('hidden');
            }

            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
    </script>
</body>
</html>