<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$error = '';
$success = '';

$username = isset($_POST['username']) ? trim($_POST['username']) : '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    if (empty($username)) {
        $error = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = 'Username must be between 3 and 20 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores.';
    } elseif (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // Check if email or username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'Email or username is already taken.';
        } else {
            try {
                $profile_picture = null;
                if (!empty($_FILES['profile_picture']['name'])) {
                    $profile_picture = uploadImage($_FILES['profile_picture'], 'uploads/users/');
                }

                $stmt = $conn->prepare("
                    INSERT INTO users (username, full_name, email, password, role, phone_number, address, profile_picture)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt->execute([
                    $username,
                    $full_name,
                    $email,
                    $hashed_password,
                    $role,
                    $phone_number,
                    $address,
                    $profile_picture
                ]);

                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['user_role'] = $role;
                $_SESSION['user_name'] = $full_name;

                switch ($role) {
                    case 'Farmer':
                        header('Location: ' . BASE_URL . '/stores/manage.php');
                        break;
                    case 'Admin':
                        header('Location: ' . BASE_URL . '/admin/users.php');
                        break;
                    default:
                        header('Location: ' . BASE_URL . '/');
                }
                exit;
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Local Farmer Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>

<body class="bg-background font-sans min-h-screen flex items-center justify-center py-12">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-3xl font-semibold text-primary mb-6 text-center">Create Account</h2>
        <?php if ($error): ?>
            <div class="alert alert-error mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label for="username" class="block text-accesibel-text-color-4 mb-2">Username</label>
                <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Choose a username">
            </div>

            <div>
                <label for="full_name" class="block text-accesibel-text-color-4 mb-2">Full Name</label>
                <input type="text" id="full_name" name="full_name" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter your full name">
            </div>

            <div>
                <label for="email" class="block text-accesibel-text-color-4 mb-2">Email</label>
                <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter your email">
            </div>

            <div>
                <label for="role" class="block text-accesibel-text-color-4 mb-2">Register as</label>
                <select id="role" name="role" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Select role</option>
                    <option value="Customer">Customer</option>
                    <option value="Farmer">Farmer</option>
                </select>
            </div>

            <div>
                <label for="phone_number" class="block text-accesibel-text-color-4 mb-2">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter your phone number">
            </div>

            <div>
                <label for="address" class="block text-accesibel-text-color-4 mb-2">Address</label>
                <textarea id="address" name="address" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter your address" rows="3"></textarea>
            </div>

            <div>
                <label for="profile_picture" class="block text-accesibel-text-color-4 mb-2">Profile Picture (Optional)</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
            </div>

            <div>
                <label for="password" class="block text-accesibel-text-color-4 mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Choose a password" minlength="6">
            </div>

            <div>
                <label for="confirm_password" class="block text-accesibel-text-color-4 mb-2">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Confirm your password">
            </div>

            <button type="submit" class="w-full bg-primary text-white py-2 rounded-md hover:bg-primary/90 transition duration-300">Create Account</button>
        </form>

        <p class="mt-6 text-center text-accesibel-text-color-3">
            Already have an account?
            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-primary hover:underline">Sign in</a>
        </p>
    </div>
</body>

</html>