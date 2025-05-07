<?php
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'components/product-card.php';
require_once 'config/constants.php';
require_once 'includes/helpers.php';

$db = new Database();
$conn = $db->getConnection();

$featuredQuery = "SELECT p.*, s.store_name, c.name as category_name 
                 FROM products p 
                 JOIN stores s ON p.store_id = s.id 
                 JOIN categories c ON p.category_id = c.id 
                 WHERE p.status = 'Available' 
                 ORDER BY RAND() 
                 LIMIT 8";
$featuredProducts = $conn->query($featuredQuery)->fetchAll(PDO::FETCH_ASSOC);

$storesQuery = "SELECT s.*, COUNT(p.id) as product_count,
                (SELECT AVG(rating) FROM reviews r WHERE r.store_id = s.id) as rating 
                FROM stores s 
                LEFT JOIN products p ON s.id = p.store_id 
                GROUP BY s.id 
                HAVING product_count > 0
                ORDER BY rating DESC 
                LIMIT 4";
$featuredStores = $conn->query($storesQuery)->fetchAll(PDO::FETCH_ASSOC);

$categoriesQuery = "SELECT c.*, COUNT(p.id) as product_count 
                   FROM categories c 
                   LEFT JOIN products p ON c.id = p.category_id 
                   GROUP BY c.id 
                   HAVING product_count > 0
                   ORDER BY product_count DESC 
                   LIMIT 6";
$categories = $conn->query($categoriesQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-gray-50">
    <!-- Hero Section -->
    <section class="relative bg-green-600 text-white">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1523741543316-beb7fc7023d8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Farm" class="w-full h-full object-cover opacity-30" />
            <div class="absolute inset-0 bg-interactive-1 mix-blend-multiply"></div>
        </div>
        <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">Local Farmer Connect</h1>
            <p class="mt-6 max-w-3xl text-xl">Menghubungkan petani lokal dengan konsumen langsung, membawa kesegaran dari ladang ke meja Anda.</p>
            <div class="mt-10">
                <a href="products.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-green-700 bg-white hover:bg-interactive-3 hover:scale-110 transition duration-150 ease-in-out">
                    Mulai Sekarang
                    <svg class="ml-3 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-accesibel-text-color-2 mb-8">Jelajahi Kategori</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category=<?php echo $category['id']; ?>" 
                       class="group bg-interactive-1 rounded-lg p-4 hover:bg-interactive-2 transition-colors duration-300 flex flex-col justify-between">
                        <div class="text-center">
                            <h3 class="text-lg font-semibold text-accesibel-text-color-1 mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="text-sm text-accesibel-text-color-3">
                                <?php echo number_format($category['product_count']); ?> produk
                            </p>
                        </div>
                        <div class="mt-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-white group-hover:bg-border-separator-3 transition-colors duration-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Produk Unggulan</h2>
                <a href="products.php" class="text-base font-semibold text-green-600 hover:text-green-500">
                    Lihat Semua
                    <span aria-hidden="true"> &rarr;</span>
                </a>
            </div>
            <div class="grid grid-cols-1 gap-y-10 gap-x-6 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php renderProductCard($product); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Stores -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">Toko Terbaik</h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($featuredStores as $store): ?>
                    <a href="stores/view.php?id=<?php echo $store['id']; ?>" 
                       class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <div class="aspect-w-16 aspect-h-9">
                            <img src="<?php echo $store['profile_picture'] ? 'uploads/stores/' . $store['profile_picture'] : '/images/default-store.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($store['store_name']); ?>"
                                 class="w-full h-48 object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($store['store_name']); ?>
                            </h3>
                            <div class="flex items-center mb-2">
                                <?php
                                $rating = round($store['rating'] ?? 0, 1);
                                if ($rating == 0): ?>
                                    <span class="text-sm text-gray-600">
                                        Belum ada rating
                                    </span>
                                <?php else: ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-5 h-5 <?php echo $i <= $rating ? 'text-yellow-400' : 'text-gray-300'; ?>" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-sm text-gray-600">
                                        <?php echo number_format($rating, 1); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600">
                                <?php echo number_format($store['product_count']); ?> produk
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="py-16 bg-green-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Mengapa Memilih Local Farmer Connect</h2>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Harga Terjangkau</h3>
                    <p class="text-gray-600">Langsung dari petani berarti harga lebih baik untuk semua pihak</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Kualitas Segar</h3>
                    <p class="text-gray-600">Dapatkan produk tersegar langsung dari pertanian lokal</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                    <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Dukung Lokal</h3>
                    <p class="text-gray-600">Bantu petani lokal berkembang di komunitas Anda</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
