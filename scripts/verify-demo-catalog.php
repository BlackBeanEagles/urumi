<?php
// Read-only check for the local demonstration catalog.
require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_products')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

$products = wc_get_products(array('status' => 'publish', 'limit' => -1));
$missing = array();
foreach ($products as $product) {
    if (!$product->get_image_id() || !wp_get_attachment_url($product->get_image_id())) {
        $missing[] = $product->get_name();
    }
}
echo 'Published products: ' . count($products) . "\n";
echo 'Products without a valid image: ' . count($missing) . "\n";
foreach ($missing as $name) echo "  - {$name}\n";
if (count($products) < 26 || $missing) exit(1);
