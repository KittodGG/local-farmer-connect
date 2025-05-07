<?php
// includes/header.php
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Farmer Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>

<body class="bg-gray-100 font-sans">
    <nav class="bg-gray-100 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo BASE_URL; ?>/" class="text-primary text-xl font-bold">Local Farmer Connect</a>
                    </div>
                    <div class="hidden md:flex ml-10 items-baseline space-x-4">
                        <a href="<?php echo BASE_URL; ?>/" class="text-accesibel-text-color-2 hover:bg-interactive-3 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                        <a href="<?php echo BASE_URL; ?>/products.php" class="text-accesibel-text-color-2 hover:bg-interactive-3 px-3 py-2 rounded-md text-sm font-medium">Products</a>
                        <a href="<?php echo BASE_URL; ?>/stores" class="text-accesibel-text-color-2 hover:bg-interactive-3 px-3 py-2 rounded-md text-sm font-medium">Stores</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isCustomer()): ?>
                            <a href="<?php echo BASE_URL; ?>/cart.php" class="text-accesibel-text-color-2 hover:interactive-3 px-3 py-2 rounded-md text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Cart
                            </a>
                        <?php endif; ?>
                        <div class="ml-3 relative" x-data="{ open: false }">
                            <div class="relative inline-block text-left">
                                <div>
                                    <button @click="open = !open"
                                        type="button"
                                        class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-green-500"
                                        id="user-menu"
                                        aria-haspopup="true"
                                        aria-expanded="true">
                                        <img class="h-8 w-8 rounded-full" src="<?php echo BASE_URL; ?>/uploads/users/<?php echo $_SESSION['user_profile_picture'] ?? 'default.jpg'; ?>" alt="">
                                    </button>
                                </div>
                                <div x-show="open"
                                    @click.away="open = false"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-10"
                                    role="menu"
                                    aria-orientation="vertical"
                                    aria-labelledby="user-menu">
                                    <div class="py-1" role="none">
                                        <a href="<?php echo BASE_URL; ?>/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">Profil</a>
                                        <a href="<?php echo BASE_URL; ?>/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">Pesanan Saya</a>
                                        <?php if (isFarmer()): ?>
                                            <a href="<?php echo BASE_URL; ?>/stores/manage.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">Kelola Toko</a>
                                        <?php endif; ?>
                                        <?php if (isAdmin()): ?>
                                            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">Dashboard Admin</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="py-1" role="none">
                                        <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">Keluar</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-gray-700 hover:bg-interactive-3 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="<?php echo BASE_URL; ?>/auth/register.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-600 bg-white hover:bg-green-50">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md p-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                <p><?php echo $flash['message']; ?></p>
            </div>
        </div>
    <?php endif; ?>

        <!-- Toast Container -->
        <div id="toast-container" class="fixed bottom-4 right-4 z-50"></div>

        <script>
            const BASE_URL = '<?php echo BASE_URL; ?>';

            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `mb-4 p-4 rounded-lg text-white ${
                    type === 'success' ? 'bg-primary' : 'bg-red-500'
                } animate-fade-in-up`;
                
                toast.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            ${type === 'success' 
                                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
                            }
                        </svg>
                        <span>${message}</span>
                    </div>
                `;

                const container = document.getElementById('toast-container');
                container.appendChild(toast);

                // Add fade out animation
                setTimeout(() => {
                    toast.classList.add('animate-fade-out-down');
                    setTimeout(() => {
                        toast.remove();
                    }, 300);
                }, 3000);
            }
        </script>

        <!-- Add animation styles -->
        <style>
            .animate-fade-in-up {
                animation: fadeInUp 0.3s ease-out;
            }

            .animate-fade-out-down {
                animation: fadeOutDown 0.3s ease-out;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeOutDown {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(20px);
                }
            }
        </style>
    </body>
</html>