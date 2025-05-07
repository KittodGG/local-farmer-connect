<?php
// auth/login.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];

            switch ($user['role']) {
                case 'Admin':
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    break;
                case 'Farmer':
                    header('Location: ' . BASE_URL . '/stores/manage.php');
                    break;
                default:
                    header('Location: ' . BASE_URL . '/');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Local Farmer Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>

<body class="bg-background font-sans min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-3xl font-semibold text-primary mb-6 text-center">Sign In</h2>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
                <span class="absolute top-0 right-0 inline-block w-5 h-5 ml-auto -mr-2"></span>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-accesibel-text-color-4 mb-2">Email</label>
                <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter your email">
            </div>

            <div>
                <label for="password" class="block text-accesibel-text-color-4 mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-border-separator-1 rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter your password">
            </div>

            <button type="submit" class="w-full bg-primary text-white py-2 rounded-md hover:bg-interactive-2 transition duration-300">Sign In</button>
        </form>

        <p class="mt-6 text-center text-accesibel-text-color-3">
            Don't have an account?
            <a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-primary hover:underline">Sign up</a>
        </p>
    </div>
</body>

</html>