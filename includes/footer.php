<?php
// includes/footer.php
?>
<footer class="bg-white mt-12 border-t border-border-separator-1">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">About Local Farmer Connect</h3>
                <p class="text-accesibel-text-color-3">Fresh produce directly from local farmers to your table.</p>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo BASE_URL; ?>/products.php" class="text-accesibel-text-color-3 hover:text-primary">Products</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/stores" class="text-accesibel-text-color-3 hover:text-primary">Stores</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">For Farmers</h3>
                <ul class="space-y-2">
                    <li><a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-accesibel-text-color-3 hover:text-primary">Sell on Local Farmer Connect</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-accesibel-text-color-2 mb-4">Contact Us</h3>
                <p class="text-accesibel-text-color-3">support@localfarmerconnect.com</p>
            </div>
        </div>
        <div class="mt-8 pt-8 border-t border-border-separator-1">
            <p class="text-center text-accesibel-text-color-3">&copy; <?php echo date('Y'); ?> Local Farmer Connect</p>
        </div>
    </div>
</footer>