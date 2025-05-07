<?php
// components/product-card.php (continued)

function renderProductCard($product) {
    $imageUrl = $product['image'] ? BASE_URL . '/uploads/products/' . $product['image'] : '/images/default-product.jpg';
    ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
        <a href="<?php echo BASE_URL; ?>/products/detail.php?id=<?php echo $product['id']; ?>">
            <div class="aspect-w-1 aspect-h-1">
                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="object-cover w-full h-48">
            </div>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-1 truncate">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h3>
                <p class="text-accesibel-text-color-3 text-sm mb-2 truncate">
                    <?php echo htmlspecialchars($product['store_name']); ?>
                </p>
                <div class="flex justify-between items-center">
                    <span class="text-primary font-semibold">
                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                    </span>
                    <?php if(isset($product['rating'])): ?>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm text-accesibel-text-color-3 ml-1">
                                <?php echo number_format($product['rating'], 1); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if($product['stock'] <= 5 && $product['stock'] > 0): ?>
                    <p class="text-orange-500 text-sm mt-2">
                        Only <?php echo $product['stock']; ?> left in stock!
                    </p>
                <?php elseif($product['stock'] == 0): ?>
                    <p class="text-red-500 text-sm mt-2">Out of stock</p>
                <?php endif; ?>
            </div>
        </a>
        <?php if(isCustomer() && $product['stock'] > 0): ?>
            <div class="p-4 pt-0">
                <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                        class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-border-separator-3 transition-colors duration-200">
                    Add to Cart
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php
}

function renderProductCard2($product, $store) {
    $imageUrl = $product['image'] ? BASE_URL . '/uploads/products/' . $product['image'] : '/images/default-product.jpg';
    ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
        <a href="<?php echo BASE_URL; ?>/products/detail.php?id=<?php echo $product['id']; ?>">
            <div class="aspect-w-1 aspect-h-1">
                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="object-cover w-full h-48">
            </div>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-1 truncate">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h3>
                <p class="text-accesibel-text-color-3 text-sm mb-2 truncate">
                    <?php echo htmlspecialchars($store['store_name']); ?>
                </p>
                <div class="flex justify-between items-center">
                    <span class="text-primary font-semibold">
                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                    </span>
                    <?php if(isset($product['rating'])): ?>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm text-accesibel-text-color-3 ml-1">
                                <?php echo number_format($product['rating'], 1); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if($product['stock'] <= 5 && $product['stock'] > 0): ?>
                    <p class="text-orange-500 text-sm mt-2">
                        Only <?php echo $product['stock']; ?> left in stock!
                    </p>
                <?php elseif($product['stock'] == 0): ?>
                    <p class="text-red-500 text-sm mt-2">Out of stock</p>
                <?php endif; ?>
            </div>
        </a>
        <?php if(isCustomer() && $product['stock'] > 0): ?>
            <div class="p-4 pt-0">
                <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                        class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-border-separator-3 transition-colors duration-200">
                    Add to Cart
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php
}

// Add to cart functionality using AJAX
?>
<script>
function addToCart(productId) {
    fetch('<?php echo BASE_URL; ?>/api/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Product added to cart!', 'success');
            // Update cart counter if exists
            const cartCounter = document.getElementById('cart-counter');
            if (cartCounter) {
                cartCounter.textContent = data.cartCount;
            }
        } else {
            showToast(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred', 'error');
        console.error('Error:', error);
    });
}
</script>