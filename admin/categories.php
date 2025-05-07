<?php
// admin/categories.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>'; print_r($_POST); echo '</pre>'; // Debug: Check what is being submitted

    try {
        if (isset($_POST['delete'])) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_POST['category_id']]);
            echo "Category deleted successfully"; // Debug message
        } else {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);

            // Validate input
            if (empty($name) || empty($description)) {
                throw new Exception('Name and description cannot be empty.');
            }

            if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
                // Update existing category
                $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $_POST['category_id']]);
                echo "Category updated successfully"; // Debug message
            } else {
                // Insert new category
                $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                echo "Category created successfully"; // Debug message
            }
        }
        header('Location: ' . BASE_URL . '/admin/categories.php');
        exit;
    } catch (Exception $e) {
        echo "Error: " . htmlspecialchars($e->getMessage()); // Output the error message
    }
}

// Get all categories with product counts
$stmt = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Category Management - Local Farmer Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
 <body class="bg-background font-sans min-h-screen">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-accesibel-text-color-2">Category Management</h1>
            <button onclick="openModal()"
                class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3 transition duration-300">
                Add New Category
            </button>
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-border-separator-1">
                <thead class="bg-interactive-1">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Products</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-accesibel-text-color-2 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border-separator-1">
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-accesibel-text-color-2">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-accesibel-text-color-3">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-accesibel-text-color-2">
                            <?php echo number_format($category['product_count']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                class="text-primary hover:text-border-separator-3 mr-3">Edit</button>
                            <button onclick="deleteCategory(<?php echo $category['id']; ?>)"
                                class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="categoryForm" method="POST">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 id="modalTitle" class="text-lg font-medium leading-6 text-accesibel-text-color-2 mb-4">Add New Category</h3>
                        <input type="hidden" name="category_id" id="categoryId">
                        <div class="mb-4">
                            <label for="categoryName" class="block text-sm font-medium text-accesibel-text-color-2 mb-2">Name</label>
                            <input type="text" name="name" id="name" required class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="mb-4">
                            <label for="categoryDescription" class="block text-sm font-medium text-accesibel-text-color-2 mb-2">Description
                            <textarea name="description" id="description" rows="3" required class="w-full border border-border-separator-1 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-border-separator-3 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                            Save Category
                        </button>
                        <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-accesibel-text-color-2 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('modalTitle').textContent = 'Add New Category';
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('categoryModal').classList.add('hidden');
    }

    function editCategory(category) {
        document.getElementById('modalTitle').textContent = 'Edit Category';
        document.getElementById('categoryId').value = category.id;
        document.getElementById('name').value = category.name; // Corrected ID
        document.getElementById('description').value = category.description; // Corrected ID
        document.getElementById('categoryModal').classList.remove('hidden');
    }

    function deleteCategory(categoryId) {
        if (confirm('Are you sure you want to delete this category?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="category_id" value="${categoryId}">
                <input type="hidden" name="delete" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>