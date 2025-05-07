<?php
// profile.php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updates = [
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'phone_number' => trim($_POST['phone_number']),
            'address' => trim($_POST['address'])
        ];

        // Check if email is unique
        if ($updates['email'] !== $user['email']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$updates['email'], $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Email already in use');
            }
        }

        // Handle password update if provided
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                throw new Exception('Current password is required to set new password');
            }

            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            if (strlen($_POST['new_password']) < 6) {
                throw new Exception('New password must be at least 6 characters long');
            }

            $updates['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $profile_picture = uploadImage($_FILES['profile_picture'], 'uploads/users/');
            if ($profile_picture) {
                $updates['profile_picture'] = $profile_picture;
                // Delete old profile picture if exists
                if ($user['profile_picture'] && file_exists('uploads/users/' . $user['profile_picture'])) {
                    unlink('uploads/users/' . $user['profile_picture']);
                }
            }
        }

        // Build update query
        $sql = "UPDATE users SET " . 
               implode(', ', array_map(fn($key) => "$key = ?", array_keys($updates))) .
               " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([...array_values($updates), $_SESSION['user_id']]);

        setFlashMessage('success', 'Profile updated successfully');
        header('Location: ' . BASE_URL . '/profile.php');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once 'includes/header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="md:flex">
                <!-- Profile Sidebar -->
                <div class="md:w-1/3 bg-interactive-1 p-6">
                    <div class="text-center">
                        <div class="relative inline-block">
                            <img src="<?php echo BASE_URL; ?>/uploads/users/<?php echo $user['profile_picture'] ?: 'default.jpg'; ?>"
                                 alt="Profile Picture"
                                 class="w-32 h-32 rounded-full object-cover border-4 border-white">
                            <label for="profile_picture" 
                                   class="absolute bottom-0 right-0 bg-primary text-white rounded-full p-2 cursor-pointer hover:bg-border-separator-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </label>
                        </div>
                        <h2 class="mt-4 text-xl font-semibold text-accesibel-text-color-2">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </h2>
                        <p class="text-accesibel-text-color-3">
                            <?php echo $user['role']; ?>
                        </p>
                        <p class="text-sm text-accesibel-text-color-3 mt-2">
                            Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>

                    <?php if (isFarmer()): ?>
                        <div class="mt-6 pt-6 border-t border-border-separator-1">
                            <a href="<?php echo BASE_URL; ?>/stores/manage.php"
                               class="block text-center bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                                Manage Store
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Profile Form -->
                <div class="md:w-2/3 p-6">
                    <h3 class="text-xl font-semibold text-accesibel-text-color-2 mb-6">Edit Profile</h3>

                    <?php if (isset($error)): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                            <p class="text-red-700"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden"
                               onchange="document.querySelector('img').src = window.URL.createObjectURL(this.files[0])">

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Full Name
                            </label>
                            <input type="text" name="full_name" required
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Email Address
                            </label>
                            <input type="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Phone Number
                            </label>
                            <input type="tel" name="phone_number" required
                                   value="<?php echo htmlspecialchars($user['phone_number']); ?>"
                                   class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                Address
                            </label>
                            <textarea name="address" rows="3" required
                                      class="w-full border border-border-separator-1 rounded-md px-3 py-2"
                            ><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <div class="border-t border-border-separator-1 pt-6">
                            <h4 class="text-lg font-medium text-accesibel-text-color-2 mb-4">Change Password</h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                        Current Password
                                    </label>
                                    <input type="password" name="current_password"
                                           class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                                </div>

                                <div>
                                    <label class="block text-accesibel-text-color-2 text-sm font-medium mb-2">
                                        New Password
                                    </label>
                                    <input type="password" name="new_password"
                                           class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                                    <p class="text-sm text-accesibel-text-color-3 mt-1">
                                        Leave blank to keep current password
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="bg-primary text-white px-6 py-2 rounded-md hover:bg-border-separator-3">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Additional Sections based on User Role -->
        <?php if (isCustomer()): ?>
            <div class="mt-8 bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold text-accesibel-text-color-2 mb-6">Order History</h3>
                <?php
                $stmt = $conn->prepare("
                    SELECT o.*, s.store_name,
                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                    FROM orders o
                    JOIN stores s ON o.store_id = s.id
                    WHERE o.user_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (empty($recent_orders)): ?>
                    <p class="text-accesibel-text-color-3 text-center py-4">
                        No orders yet
                    </p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="flex justify-between items-center border-b border-border-separator-1 last:border-0 pb-4 last:pb-0">
                                <div>
                                    <p class="font-medium text-accesibel-text-color-2">
                                        Order #<?php echo $order['id']; ?>
                                    </p>
                                    <p class="text-sm text-accesibel-text-color-3">
                                        from <?php echo htmlspecialchars($order['store_name']); ?>
                                    </p>
                                    <p class="text-sm text-accesibel-text-color-3">
                                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-primary">
                                        Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                    </p>
                                    <span class="inline-block px-2 py-1 text-xs rounded-full
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
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="<?php echo BASE_URL; ?>/orders/" 
                           class="text-primary hover:text-border-separator-3">
                            View All Orders
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>