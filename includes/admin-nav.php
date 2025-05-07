<nav class="bg-white shadow-md mb-6">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo BASE_URL; ?>/" class="text-primary text-xl font-bold">Local Farmer Connect</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'text-primary bg-interactive-1' : 'text-accesibel-text-color-2 hover:text-primary hover:bg-interactive-1'; ?>">Dashboard</a>
                        <a href="<?php echo BASE_URL; ?>/admin/users.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo strpos($_SERVER['REQUEST_URI'], 'users.php') !== false ? 'text-primary bg-interactive-1' : 'text-accesibel-text-color-2 hover:text-primary hover:bg-interactive-1'; ?>">Users</a>
                        <a href="<?php echo BASE_URL; ?>/admin/categories.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo strpos($_SERVER['REQUEST_URI'], 'categories.php') !== false ? 'text-primary bg-interactive-1' : 'text-accesibel-text-color-2 hover:text-primary hover:bg-interactive-1'; ?>">Categories</a>
                        <a href="<?php echo BASE_URL; ?>/admin/products.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo strpos($_SERVER['REQUEST_URI'], 'products.php') !== false ? 'text-primary bg-interactive-1' : 'text-accesibel-text-color-2 hover:text-primary hover:bg-interactive-1'; ?>">Products</a>
                        <a href="<?php echo BASE_URL; ?>/admin/orders.php" class="px-3 py-2 rounded-md text-sm font-medium <?php echo strpos($_SERVER['REQUEST_URI'], 'orders.php') !== false ? 'text-primary bg-interactive-1' : 'text-accesibel-text-color-2 hover:text-primary hover:bg-interactive-1'; ?>">Orders</a>
                    </div>
                </div>
            </div>
            <div class="hidden md:block">
                <div class="ml-4 flex items-center md:ml-6">
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="text-accesibel-text-color-2 hover:text-primary px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>