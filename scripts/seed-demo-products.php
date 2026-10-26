<?php
// Run only for the local take-home demonstration, inside the WordPress container.
require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product_id_by_sku')) {
    fwrite(STDERR, "WooCommerce is not active.\n");
    exit(1);
}

$categories = array(
    'desk-essentials' => 'Desk essentials',
    'carry-everywhere' => 'Carry everywhere',
    'everyday-wear' => 'Everyday wear',
    'home-and-living' => 'Home & living',
);
$terms = array();
foreach ($categories as $slug => $name) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        $created = wp_insert_term($name, 'product_cat', array('slug' => $slug));
        if (is_wp_error($created)) {
            fwrite(STDERR, $created->get_error_message() . "\n");
            exit(1);
        }
        $term = get_term((int) $created['term_id'], 'product_cat');
    }
    $terms[$slug] = (int) $term->term_id;
}

$products = array(
    array('sku' => 'URUMI-NOTEBOOK-01', 'name' => 'Urumi Field Notebook', 'price' => '349', 'category' => 'desk-essentials', 'summary' => 'A linen-covered notebook for ideas worth keeping.'),
    array('sku' => 'URUMI-DESKMAT-01', 'name' => 'Urumi Desk Mat', 'price' => '699', 'category' => 'desk-essentials', 'summary' => 'A spacious, soft-touch surface for your daily setup.'),
    array('sku' => 'URUMI-TOTE-01', 'name' => 'Urumi Canvas Tote', 'price' => '499', 'category' => 'carry-everywhere', 'summary' => 'A simple canvas carryall for everyday essentials.'),
    array('sku' => 'URUMI-BOTTLE-01', 'name' => 'Urumi Steel Bottle', 'price' => '599', 'category' => 'carry-everywhere', 'summary' => 'A clean-lined steel bottle for life on the move.'),
    array('sku'=>'URUMI-PLANNER-01','name'=>'Urumi Linen Weekly Planner','price'=>'399','category'=>'desk-essentials','summary'=>'A tactile planner for the week ahead.','image'=>'linen-planner.webp'),
    array('sku'=>'URUMI-PENS-01','name'=>'Urumi Gel Pen Set','price'=>'249','category'=>'desk-essentials','summary'=>'Five easy-writing pens in thoughtful colors.','image'=>'gel-pens.webp'),
    array('sku'=>'URUMI-BOOKMARK-01','name'=>'Urumi Brass Bookmark','price'=>'179','category'=>'desk-essentials','summary'=>'A little marker for your next good read.','image'=>'brass-bookmark.webp'),
    array('sku'=>'URUMI-ORGANIZER-01','name'=>'Urumi Cork Desk Organizer','price'=>'549','category'=>'desk-essentials','summary'=>'Give small desk essentials a place of their own.','image'=>'cork-organizer.webp'),
    array('sku'=>'URUMI-PENCUP-01','name'=>'Urumi Ceramic Pen Cup','price'=>'329','category'=>'desk-essentials','summary'=>'A warm, useful accent for the creative desk.','image'=>'ceramic-pen-cup.webp'),
    array('sku'=>'URUMI-SLEEVE-01','name'=>'Urumi Canvas Laptop Sleeve','price'=>'899','category'=>'carry-everywhere','summary'=>'A soft, simple layer for your daily commute.','image'=>'laptop-sleeve.webp'),
    array('sku'=>'URUMI-POUCH-01','name'=>'Urumi Travel Pouch','price'=>'449','category'=>'carry-everywhere','summary'=>'Keep the little things together on the move.','image'=>'travel-pouch.webp'),
    array('sku'=>'URUMI-CROSSBODY-01','name'=>'Urumi Mini Crossbody Bag','price'=>'1299','category'=>'carry-everywhere','summary'=>'The hands-free bag for essentials-only days.','image'=>'crossbody-bag.webp'),
    array('sku'=>'URUMI-CABLES-01','name'=>'Urumi Cable Organizer','price'=>'399','category'=>'carry-everywhere','summary'=>'A tidier home for your everyday tech.','image'=>'cable-organizer.webp'),
    array('sku'=>'URUMI-KEYLOOP-01','name'=>'Urumi Key Loop','price'=>'199','category'=>'carry-everywhere','summary'=>'A small detail that is easy to carry.','image'=>'key-loop.webp'),
    array('sku'=>'URUMI-CAP-01','name'=>'Urumi Cotton Cap','price'=>'499','category'=>'everyday-wear','summary'=>'An easygoing cap for sunny days.','image'=>'cotton-cap.webp'),
    array('sku'=>'URUMI-TEE-01','name'=>'Urumi Oversized Tee','price'=>'749','category'=>'everyday-wear','summary'=>'A relaxed everyday favorite in soft cotton.','image'=>'oversized-tee.webp'),
    array('sku'=>'URUMI-BEANIE-01','name'=>'Urumi Ribbed Beanie','price'=>'399','category'=>'everyday-wear','summary'=>'A cozy finish for cooler days.','image'=>'ribbed-beanie.webp'),
    array('sku'=>'URUMI-SOCKS-01','name'=>'Urumi Everyday Socks Set','price'=>'299','category'=>'everyday-wear','summary'=>'Three soft pairs for all the days in between.','image'=>'socks-set.webp'),
    array('sku'=>'URUMI-ESPRESSO-01','name'=>'Urumi Espresso Cup','price'=>'249','category'=>'home-and-living','summary'=>'A small ceramic cup for a slow morning.','image'=>'espresso-cup.webp'),
    array('sku'=>'URUMI-CARAFE-01','name'=>'Urumi Glass Water Carafe','price'=>'699','category'=>'home-and-living','summary'=>'An elegant water carafe for the table.','image'=>'glass-carafe.webp'),
    array('sku'=>'URUMI-CANDLE-01','name'=>'Urumi Amber Candle','price'=>'599','category'=>'home-and-living','summary'=>'A soft glow for an unhurried evening.','image'=>'candle.webp'),
    array('sku'=>'URUMI-TRAY-01','name'=>'Urumi Stoneware Tray','price'=>'449','category'=>'home-and-living','summary'=>'A lovely landing spot for small essentials.','image'=>'stoneware-tray.webp'),
    array('sku'=>'URUMI-THROW-01','name'=>'Urumi Soft Throw Blanket','price'=>'1199','category'=>'home-and-living','summary'=>'A textured layer to make home feel softer.','image'=>'throw-blanket.webp'),
    array('sku'=>'URUMI-LUNCHBOX-01','name'=>'Urumi Lunch Box','price'=>'749','category'=>'home-and-living','summary'=>'Pack something good for the day ahead.','image'=>'lunch-box.webp'),
);

foreach ($products as $item) {
    $existing = wc_get_product_id_by_sku($item['sku']);
    if ($existing) {
        $product = wc_get_product($existing);
        echo "Exists: {$item['name']} (#{$existing})\n";
    } else {
        $product = new WC_Product_Simple();
        $product->set_name($item['name']);
        $product->set_sku($item['sku']);
        $product->set_regular_price($item['price']);
        $product->set_description($item['summary'] . ' This product is part of the Urumi Goods demonstration collection; test orders are not fulfilled.');
        $product->set_short_description($item['summary']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_category_ids(array($terms[$item['category']]));
        $id = $product->save();
        if (!$id) {
            fwrite(STDERR, "Could not create {$item['name']}.\n");
            exit(1);
        }
        echo "Created: {$item['name']} (#{$id})\n";
    }
    if (empty($item['image']) || $product->get_image_id()) continue;
    $source = '/tmp/urumi-product-images/' . $item['image'];
    if (!is_readable($source)) {
        fwrite(STDERR, "Missing catalog image: {$item['image']}\n");
        exit(1);
    }
    $upload = wp_upload_bits('urumi-' . $item['image'], null, file_get_contents($source));
    if (!empty($upload['error'])) {
        fwrite(STDERR, "Image upload failed: {$upload['error']}\n");
        exit(1);
    }
    $attachment_id = wp_insert_attachment(array('post_mime_type' => 'image/webp', 'post_title' => $item['name'], 'post_status' => 'inherit'), $upload['file'], $product->get_id());
    if (is_wp_error($attachment_id)) {
        fwrite(STDERR, $attachment_id->get_error_message() . "\n");
        exit(1);
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $item['name']);
    $product->set_image_id($attachment_id);
    $product->save();
    echo "Photo added: {$item['name']}\n";
}

// Rename the two earlier sample products without changing their IDs, prices, images or orders.
$legacy = array(
    'Urumi AI Demo Mug' => array('name' => 'Urumi Ceramic Mug', 'slug' => 'urumi-ceramic-mug', 'category' => 'desk-essentials'),
    'Urumi Demo Hoodie' => array('name' => 'Urumi Studio Hoodie', 'slug' => 'urumi-studio-hoodie', 'category' => 'everyday-wear'),
);
foreach ($legacy as $old_name => $update) {
    $matches = wc_get_products(array('status' => 'publish', 'limit' => 10, 'search' => $old_name));
    foreach ($matches as $product) {
        if ($product->get_name() !== $old_name) continue;
        $product->set_name($update['name']);
        $product->set_slug($update['slug']);
        $product->save();
        echo "Renamed: {$old_name} → {$update['name']} (#{$product->get_id()})\n";
        break;
    }
}

// Place the earlier products into the same customer-facing navigation.
foreach (array('Urumi Ceramic Mug' => 'desk-essentials', 'Urumi Studio Hoodie' => 'everyday-wear') as $name => $slug) {
    $matches = wc_get_products(array('status' => 'publish', 'limit' => 10, 'search' => $name));
    foreach ($matches as $product) {
        if ($product->get_name() !== $name) continue;
        wp_set_object_terms($product->get_id(), array($terms[$slug]), 'product_cat', true);
        echo "Categorized: {$name} (#{$product->get_id()})\n";
        break;
    }
}
