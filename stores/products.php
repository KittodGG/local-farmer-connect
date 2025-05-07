<?php
// stores/products.php
require_once '../config/database.php';

if (!isLoggedIn() || !isFarmer()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get store information
$stmt = $conn->prepare("SELECT * FROM stores WHERE farmer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header('Location: ' . BASE_URL . '/stores/manage.php');
    exit;
}

// Get categories for product form
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get store products with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total first
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE store_id = ?");
$stmt->execute([$store['id']]);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Then get paginated products
$stmt = $conn->prepare(
    "
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.store_id = ?
    ORDER BY p.created_at DESC
    LIMIT " . (int)$per_page . " OFFSET " . (int)$offset
);
$stmt->execute([$store['id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-background">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - <?php echo htmlspecialchars($store['store_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.1.0/dist/full.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-background font-sans">
    <div class="min-h-full">
        <?php require_once '../includes/header.php'; ?>

        <div class="flex">
            <!-- Sidebar -->
            <div class="hidden md:flex md:w-64 md:flex-col">
                <div class="flex flex-col flex-grow pt-5 bg-white overflow-y-auto">
                    <div class="flex items-center flex-shrink-0 px-4">
                        <img class="h-8 w-auto" src="<?php echo BASE_URL; ?>/images/logo.png" alt="Local Farmer Connect">
                    </div>
                    <div class="mt-5 flex-1 flex flex-col">
                        <nav class="flex-1 px-2 pb-4 space-y-1">
                            <a href="#" class="bg-interactive-1 text-accesibel-text-color-1 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                Products
                            </a>
                            <a href="#" class="text-accesibel-text-color-3 hover:bg-interactive-1 hover:text-accesibel-text-color-1 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                Orders
                            </a>
                            <a href="#" class="text-accesibel-text-color-3 hover:bg-interactive-1 hover:text-accesibel-text-color-1 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                                Analytics
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="flex-1">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <h1 class="text-2xl font-semibold text-accesibel-text-color-2">Manage Products</h1>
                    </div>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <div class="py-4">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-lg font-medium leading-6 text-accesibel-text-color-2 sm:truncate">
                                        <?php echo htmlspecialchars($store['store_name']); ?> Products
                                    </h2>
                                </div>
                                <div class="mt-4 flex md:mt-0 md:ml-4">
                                    <button type="button" onclick="openProductModal()" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-border-separator-3 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                        Add New Product
                                    </button>
                                </div>
                            </div>

                            <!-- Products List -->
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul role="list" class="divide-y divide-border-separator-1">
                                    <?php foreach ($products as $product): ?>
                                        <li>
                                            <div class="px-4 py-4 sm:px-6">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <img class="h-10 w-10 rounded-full" src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image'] ? htmlspecialchars($product['image']) : 'default.jpg'; ?>" alt="">
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-accesibel-text-color-2">
                                                                <?php echo htmlspecialchars($product['name']); ?>
                                                            </div>
                                                            <div class="text-sm text-accesibel-text-color-3">
                                                                <?php echo htmlspecialchars($product['category_name']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <div class="text-sm font-medium text-accesibel-text-color-2 mr-4">
                                                            Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                                                        </div>
                                                        <div class="text-sm text-accesibel-text-color-3 mr-4">
                                                            Stock: <?php echo number_format($product['stock']); ?>
                                                        </div>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $product['status'] === 'Available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                            <?php echo $product['status']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 sm:flex sm:justify-between">
                                                    <div class="sm:flex">
                                                        <div class="mt-2 flex items-center text-sm text-accesibel-text-color-3 sm:mt-0">
                                                            <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="text-primary hover:text-border-separator-3 mr-3">
                                                                Edit
                                                            </button>
                                                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="text-red-500 hover:text-red-600">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($products)): ?>
                                        <li>
                                            <div class="px-4 py-4 sm:px-6 text-center text-accesibel-text-color-3">
                                                No products found. Add your first product!
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-border-separator-1 sm:px-6">
                                    <div class="flex-1 flex justify-between sm:hidden">
                                        <a href="?page=<?php echo max($page - 1, 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-border-separator-1 text-sm font-medium rounded-md text-accesibel-text-color-2 bg-white hover:bg-interactive-1">
                                            Previous
                                        </a>
                                        <a href="?page=<?php echo min($page + 1, $total_pages); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-border-separator-1 text-sm font-medium rounded-md text-accesibel-text-color-2 bg-white hover:bg-interactive-1">
                                            Next
                                        </a>
                                    </div>
                                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm text-accesibel-text-color-3">
                                                Showing <span class="font-medium"><?php echo ($page - 1) * $per_page + 1; ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_products); ?></span> of <span class="font-medium"><?php echo $total_products; ?></span> results
                                            </p>
                                        </div>
                                        <div>
                                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-border-separator-1 bg-white text-sm font-medium text-accesibel-text-color-2 hover:bg-interactive-1 <?php echo $page == $i ? 'bg-interactive-1' : ''; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                <?php endfor; ?>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 id="modalTitle" class="text-lg font-medium leading-6 text-accesibel-text-color-2 mb-4">Add New Product</h3>
                    <form id="productForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="product_id" id="productId">
                        <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">

                        <div>
                            <label for="productName" class="block text-sm font-medium text-accesibel-text-color-2">Product Name</label>
                            <input type="text" name="name" id="productName" required class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-border-separator-1 rounded-md">
                        </div>

                        <div>
                            <label for="productCategory" class="block text-sm font-medium text-accesibel-text-color-2">Category</label>
                            <select name="category_id" id="productCategory" required class="mt-1 block w-full py-2 px-3 border border-border-separator-1 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="productPrice" class="block text-sm font-medium text-accesibel-text-color-2">Price (Rp)</label>
                                <input type="number" name="price" id="productPrice" required min="0" class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-border-separator-1 rounded-md">
                            </div>
                            <div>
                                <label for="productStock" class="block text-sm font-medium text-accesibel-text-color-2">Stock</label>
                                <input type="number" name="stock" id="productStock" required min="0" class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-border-separator-1 rounded-md">
                            </div>
                        </div>

                        <div>
                            <label for="productDescription" class="block text-sm font-medium text-accesibel-text-color-2">Description</label>
                            <textarea name="description" id="productDescription" rows="3" required class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-border-separator-1 rounded-md"></textarea>
                        </div>

                        <div>
                            <label for="productImage" class="block text-sm font-medium text-accesibel-text-color-2">Product Image</label>
                            <input type="file" name="image" id="productImage" accept="image/*" class="mt-1 focus:ring-primary focus:border-primary block w-full shadow-sm sm:text-sm border-border-separator-1 rounded-md">
                            <div id="currentImage" class="mt-2 hidden">
                                <img src="" alt="Current product image" class="w-20 h-20 object-cover rounded-md">
                                <p class="text-sm text-accesibel-text-color-3 mt-1">Current image (upload new one to replace)</p>
                            </div>
                        </div>

                        <div>
                            <label for="productStatus" class="block text-sm font-medium text-accesibel-text-color-2">Status</label>
                            <select name="status" id="productStatus" required class="mt-1 block w-full py-2 px-3 border border-border-separator-1 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                <option value="Available">Available</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="productForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-border-separator-3 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                        <span id="submitText">Add Product</span>
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-border-separator-1 shadow-sm px-4 py-2 bg-white text-base font-medium text-accesibel-text-color-2 hover:bg-interactive-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openProductModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('submitText').textContent = 'Add Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('currentImage').classList.add('hidden');
            document.getElementById('productModal').classList.remove('hidden');
        }

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('submitText').textContent = 'Update Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCategory').value = product.category_id;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStock').value = product.stock;
            document.getElementById('productDescription').value = product.description;
            document.getElementById('productStatus').value = product.status;

            if (product.image) {
                document.getElementById('currentImage').classList.remove('hidden');
                document.getElementById('currentImage').querySelector('img').src = `${BASE_URL}/uploads/products/${product.image}`;
            } else {
                document.getElementById('currentImage').classList.add('hidden');
            }

            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                fetch(`${BASE_URL}/api/products/delete.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product deleted successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to delete product');
                    }
                })
                .catch(error => {
                    alert('An error occurred');
                    console.error('Error:', error);
                });
            }
        }

        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(`${BASE_URL}/api/products/save.php`, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product saved successfully');
                    closeModal();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to save product');
                }
            })
            .catch(error => {
                alert('An error occurred');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>