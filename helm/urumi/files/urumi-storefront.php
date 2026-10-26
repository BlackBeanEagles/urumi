<?php
/** Plugin Name: Urumi Storefront */
if (!defined('ABSPATH')) exit;

// Keep the editor available at /wp-admin/, but never show its toolbar to shoppers.
add_filter('show_admin_bar', '__return_false', PHP_INT_MAX);
add_action('after_setup_theme', function () {
    if (!is_admin()) show_admin_bar(false);
}, PHP_INT_MAX);
// A late CSS guard also handles WordPress themes that print an already-built toolbar.
add_action('wp_head', function () {
    echo '<style id="urumi-hide-front-toolbar">html{margin-top:0!important}body:not(.wp-admin) #wpadminbar{display:none!important}.admin-bar .urumi-site-header{top:0!important}</style>';
}, PHP_INT_MAX);

add_action('init', function () {
    if (get_option('urumi_storefront_initialized')) return;
    // Never replace a homepage that an owner has already selected.
    if (get_option('show_on_front') !== 'page') {
        $page = get_page_by_path('urumi-home');
        $id = $page ? $page->ID : wp_insert_post(array('post_title' => 'Home', 'post_name' => 'urumi-home', 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => '[urumi_storefront]'));
        if ($id && !is_wp_error($id)) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', (int) $id);
        }
    }
    update_option('urumi_storefront_initialized', '1');
}, 20);

add_action('wp_enqueue_scripts', function () {
    $css = __DIR__ . '/urumi-storefront.css';
    if (file_exists($css)) wp_enqueue_style('urumi-storefront', plugin_dir_url(__FILE__) . 'urumi-storefront.css', array(), (string) filemtime($css));
    $polish = __DIR__ . '/urumi-storefront-v2.css';
    if (file_exists($polish)) wp_enqueue_style('urumi-storefront-v2', plugin_dir_url(__FILE__) . 'urumi-storefront-v2.css', array('urumi-storefront'), (string) filemtime($polish));
});

// Give only our named demo products their original catalog artwork.
// Existing merchant images are never replaced.
add_action('init', function () {
    if (!function_exists('wc_get_products')) return;
    $assets = array(
        'demo-mug.webp' => array('Urumi AI Demo Mug', 'Urumi Ceramic Mug'),
        'demo-hoodie.webp' => array('Urumi Demo Hoodie', 'Urumi Studio Hoodie'),
        'notebook.webp' => array('Urumi Field Notebook'),
        'desk-mat.webp' => array('Urumi Desk Mat'),
        'tote.webp' => array('Urumi Canvas Tote'),
        'bottle.webp' => array('Urumi Steel Bottle'),
    );
    foreach (wc_get_products(array('status' => 'publish', 'limit' => 30)) as $product) {
        if ($product->get_image_id()) continue;
        foreach ($assets as $filename => $names) {
            if (!in_array($product->get_name(), $names, true)) continue;
            $source = __DIR__ . '/urumi-assets/' . $filename;
            if (!is_readable($source)) continue;
            $existing = get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_urumi_demo_asset', 'meta_value' => $filename));
            $attachment_id = $existing ? (int) $existing[0] : 0;
            if (!$attachment_id) {
                $upload = wp_upload_bits('urumi-' . $filename, null, file_get_contents($source));
                if (!empty($upload['error'])) continue;
                $attachment_id = wp_insert_attachment(array('post_mime_type' => 'image/webp', 'post_title' => $product->get_name(), 'post_status' => 'inherit'), $upload['file']);
                if (is_wp_error($attachment_id)) continue;
                update_post_meta($attachment_id, '_urumi_demo_asset', $filename);
                require_once ABSPATH . 'wp-admin/includes/image.php';
                wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
            }
            $product->set_image_id($attachment_id);
            $product->save();
            break;
        }
    }
}, 30);

add_filter('render_block', function ($html, $block) {
    if ($block['blockName'] !== 'core/template-part') return $html;
    $slug = isset($block['attrs']['slug']) ? $block['attrs']['slug'] : '';
    if ($slug === 'header') {
        $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $cart = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        return '<header class="urumi-site-header"><div class="urumi-header-inner"><a class="urumi-wordmark" href="' . esc_url(home_url('/')) . '" aria-label="Urumi connected store"><span class="urumi-wordmark-symbol">◇</span><span class="urumi-brand-words">URUMI<small>CONNECTED STORE</small></span></a><nav aria-label="Main navigation"><a href="' . esc_url(home_url('/')) . '">Discover</a><a href="' . esc_url(home_url('/shop/')) . '">Shop</a><a href="' . esc_url($account) . '">Account</a><a class="urumi-cart-link" href="' . esc_url($cart) . '">Bag <span>' . (int) $count . '</span></a></nav></div></header>';
    }
    if ($slug === 'footer') {
        return '<footer class="urumi-site-footer"><div><a class="urumi-wordmark" href="' . esc_url(home_url('/')) . '"><span class="urumi-wordmark-symbol">◇</span><span class="urumi-brand-words">URUMI<small>CONNECTED STORE</small></span></a><p>A real storefront. Connected through one gateway.</p></div><nav aria-label="Footer navigation"><a href="' . esc_url(home_url('/shop/')) . '">Shop</a><a href="' . esc_url(home_url('/cart/')) . '">Bag</a><a href="' . esc_url(home_url('/my-account/')) . '">Account</a></nav><small>© ' . esc_html(date('Y')) . ' Urumi demo store</small></footer>';
    }
    return $html;
}, 10, 2);

add_shortcode('urumi_storefront', function () {
    if (!function_exists('wc_get_products')) return '<p>The collection is getting ready.</p>';
    $products = wc_get_products(array('status' => 'publish', 'limit' => 6, 'orderby' => 'date', 'order' => 'DESC'));
    $shop = wc_get_page_permalink('shop');
    ob_start(); ?>
    <div class="urumi-storefront">
      <section class="urumi-hero" aria-labelledby="urumi-hero-title">
        <div class="urumi-hero-copy"><span class="urumi-eyebrow">◇ &nbsp; COMMERCE, CONNECTED</span><h1 id="urumi-hero-title">A little store.<br><em>A bigger idea.</em></h1><p>Explore a real WooCommerce shop connected to the Urumi AI Gateway. The products you browse here are the same products its AI tools can discover and manage.</p><div class="urumi-hero-actions"><a class="urumi-button" href="<?php echo esc_url($shop); ?>">Explore the shop <span aria-hidden="true">↗</span></a><a class="urumi-text-link" href="#urumi-featured">See the collection <span aria-hidden="true">↓</span></a></div></div><span class="urumi-hero-index">01 / DISCOVER</span>
      </section>
      <div class="urumi-proof-strip" aria-label="How this store works"><span><b>01</b> Browse live products</span><span><b>02</b> Shop with WooCommerce</span><span><b>03</b> Connected through MCP</span></div>
      <section id="urumi-featured" class="urumi-featured" aria-labelledby="urumi-featured-title"><div class="urumi-section-heading"><div><span class="urumi-eyebrow">THE CONNECTED COLLECTION</span><h2 id="urumi-featured-title">Good things, real data.</h2><p>Live products from this WooCommerce store.</p></div><a class="urumi-text-link" href="<?php echo esc_url($shop); ?>">Shop everything <span aria-hidden="true">↗</span></a></div>
        <?php if ($products) : ?><div class="urumi-product-grid"><?php foreach ($products as $product) : $name = $product->get_name(); $visual = stripos($name, 'mug') !== false ? 'mug' : (stripos($name, 'hoodie') !== false ? 'hoodie' : 'object'); ?><article class="urumi-product-card"><a class="urumi-product-image <?php echo esc_attr($visual); ?>" href="<?php echo esc_url(get_permalink($product->get_id())); ?>" aria-label="View <?php echo esc_attr($name); ?>"><?php if ($product->get_image_id()) echo wp_get_attachment_image($product->get_image_id(), 'woocommerce_thumbnail', false, array('alt' => $name)); else echo '<span class="urumi-placeholder-shape" aria-hidden="true"></span>'; ?><span class="urumi-product-arrow" aria-hidden="true">↗</span></a><div class="urumi-product-info"><div><span class="urumi-product-kicker">URUMI GOODS</span><h3><a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($name); ?></a></h3></div><span class="urumi-product-price"><?php echo wp_kses_post($product->get_price_html()); ?></span></div></article><?php endforeach; ?></div><?php else : ?><p class="urumi-empty-products">Products are coming soon.</p><?php endif; ?>
      </section><section class="urumi-endnote"><span>FROM STOREFRONT TO AI TOOL</span><p>One store. One gateway. A complete story.</p><a class="urumi-button light" href="<?php echo esc_url($shop); ?>">Visit the shop <span aria-hidden="true">↗</span></a></section>
    </div>
    <?php return ob_get_clean();
});
