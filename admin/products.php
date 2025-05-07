<?php
// admin/products.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get categories for form
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get products with related info
$stmt = $conn->query("
    SELECT p.*, c.name as category_name, s.store_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN stores s ON p.store_id = s.id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Product Management - Local Farmer Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
<body class="bg-background font-sans min-h-screen">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-accesibel-text-color-2 mb-8">Product Management</h1>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-border-separator-1">
                <thead class="bg-interactive-1">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Store</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Stock</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border-separator-1">
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img class="h-10 w-10 rounded-full object-cover" src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image'] ?: 'default.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-accesibel-text-color-2">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-3">
                                <?php echo htmlspecialchars($product['store_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-3">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-2">
                                Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-2">
                                <?php echo number_format($product['stock']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $product['status'] === 'Available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $product['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                        class="text-primary hover:text-border-separator-3 mr-3">
                                    Edit
                                </button>
                                <button onclick="deleteProduct(<?php echo $product['id']; ?>)"
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 id="modalTitle" class="text-lg font-medium leading-6 text-accesibel-text-color-2 mb-4">Edit Product</h3>
                        <input type="hidden" name="product_id" id="productId">
                        
                        <div class="space-y-4">
                            <div>
                                <label for="productName" class="block text-sm font-medium text-accesibel-text-color-2">Name</label>
                                <input type="text" name="name" id="productName" required
                                       class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            </div>

                            <div>
                                <label for="productCategory" class="block text-sm font-medium text-accesibel-text-color-2">Category</label>
                                <select name="category_id" id="productCategory" required
                                        class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="productPrice" class="block text-sm font-medium text-accesibel-text-color-2">Price</label>
                                    <input type="number" name="price" id="productPrice" required min="0"
                                           class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                                <div>
                                    <label for="productStock" class="block text-sm font-medium text-accesibel-text-color-2">Stock</label>
                                    <input type="number" name="stock" id="productStock" required min="0"
                                           class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                </div>
                            </div>

                            <div>
                                <label for="productDescription" class="block text-sm font-medium text-accesibel-text-color-2">Description</label>
                                <textarea name="description" id="productDescription" rows="3" required
                                          class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                            </div>

                            <div>
                                <label for="productStatus" class="block text-sm font-medium text-accesibel-text-color-2">Status</label>
                                <select name="status" id="productStatus" required
                                        class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                    <option value="Available">Available</option>
                                    <option value="Out of Stock">Out of Stock</option>
                                </select>
                            </div>

                            <div>
                                <label for="productImage" class="block text-sm font-medium text-accesibel-text-color-2">Image</label>
                                <input type="file" name="image" id="productImage" accept="image/*"
                                       class="mt-1 block w-full border border-border-separator-1 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                <div id="currentImage" class="mt-2 hidden">
                                    <img src="" alt="Current product image" class="w-20 h-20 object-cover rounded-md">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-border-separator-3 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                            Save Product
                        </button>
                        <button type="button" onclick="closeModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-accesibel-text-color-2 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCategory').value = product.category_id;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStock').value = product.stock;
            document.getElementById('productDescription').value = product.description;
            document.getElementById('productStatus').value = product.status;

            const currentImage = document.getElementById('currentImage');
            if (product.image) {
                currentImage.classList.remove('hidden');
                currentImage.querySelector('img').src = `${BASE_URL}/uploads/products/${product.image}`;
            } else {
                currentImage.classList.add('hidden');
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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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

        // Handle form submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(`${BASE_URL}/api/products/save.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
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