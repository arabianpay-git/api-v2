<?php

use Illuminate\Support\Facades\Route;

Route::get('v2/enc', function () {
    $key = base64_decode('dlzHBZOPN+4ZZ2Jnfkll/iVUJ1GuwfwCRnvxxuCMXdg=');
    $iv = base64_decode('l2kMAFuEh7jazjgDCfIKUg==');

    $encrypted_text = "+55DjFR+WpnI7lQI0CYQtjxLtTHbHwrYeoxkJx6J3ys4Vbfyp2hoe/B8D/BgG0HrtGpIv/k23O73O6LzpZbVj6EKnwo9s/2KNl7OuXx78ZMAjodmsXnU2q780GTMOanoafiBq9drVQb1g9SskUAaHMFynt4OV99uYa6pd+4401T7d3o0seOraAGaB+jGZwsUygidN92xfO8xmPDBrgQVCqH31TAe5rqgvNVnzbXt3Ta+VPyeXnNyf6ruvU1/2OEmnIAckoQftVFXKFAIioZrHbMy7xWJ6yx2sGaPfjpVzNwqRgVbtpEB4nHzrM0ekZqAyhWe6Ef8ItDuQUoWJf761NskmiqBz/uhE3IP2IbjmIx/LDky0U4qHJckFDCkuIGISij+6SBBqYKn63sNNNK2qW51y6ZgPm++vNZYzOJNH8DewSkCKw4Y2+XElI/JS5+uizmWvk2s7+kUZ7Zutr9ru+RaagNPAF41Sx9p0WVE8oe4Gqqf9GDl1Xzuyb1u2KVKyp4yk7FeRl7D9UocRZ5dEHmYxisTi+Dp9YechtbHQqOWHcaK8X8mhArAo7/8OCS0k0FWjxRcZgUkExzVVxVTgDIvfQOyVpWFclTD6TI54Br6oIfmZpkRSRFcdQ0ulwBkOjleSZUO5J3RP13R0xAu3Xj/nm3pfmJV1lcUx6JlUJU=";
    $decrypted = openssl_decrypt($encrypted_text, 'AES-256-CBC', $key, 0, $iv);
    return response()->json(['message' => $decrypted]);
});

require __DIR__.'/api/auth.php';
require __DIR__.'/api/products.php';
require __DIR__.'/api/suppliers.php';
require __DIR__.'/api/brands.php';
require __DIR__.'/api/orders.php';
require __DIR__.'/api/cart.php';
require __DIR__.'/api/home.php';
require __DIR__.'/api/categories.php';
require __DIR__.'/api/addresses.php';
require __DIR__.'/api/payments.php';
require __DIR__.'/api/common.php';
require __DIR__.'/api/nafath.php';
require __DIR__.'/api/pages.php';
require __DIR__.'/api/user.php';
require __DIR__.'/api/coupons.php';
require __DIR__.'/api/reviews.php';
require __DIR__.'/api/search.php';
require __DIR__.'/api/notifications.php';
require __DIR__.'/api/wishlists.php';
require __DIR__.'/api/followed.php';
require __DIR__.'/api/clickpay.php';
require __DIR__.'/api/kyc.php';