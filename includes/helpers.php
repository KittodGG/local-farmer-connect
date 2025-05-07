<?php
// includes/helpers.php
function getImageUrl($path, $type = 'product') {
    if (empty($path)) {
        switch ($type) {
            case 'product':
                return DEFAULT_PRODUCT_IMAGE;
            case 'store':
                return DEFAULT_STORE_IMAGE;
            case 'user':
                return DEFAULT_USER_IMAGE;
            case 'category':
                return DEFAULT_CATEGORY_IMAGE;
            default:
                return DEFAULT_PRODUCT_IMAGE;
        }
    }

    $basePath = BASE_URL . '/public/';
    
    if (strpos($path, 'uploads/') === 0) {
        return $basePath . $path;
    }
    
    if (strpos($path, 'images/') === 0) {
        return $basePath . $path;
    }
    
    switch ($type) {
        case 'product':
            return $basePath . 'uploads/products/' . $path;
        case 'store':
            return $basePath . 'uploads/stores/' . $path;
        case 'user':
            return $basePath . 'uploads/users/' . $path;
        case 'category':
            return $basePath . 'images/categories/' . $path;
        default:
            return $basePath . 'uploads/' . $path;
    }
}
?>