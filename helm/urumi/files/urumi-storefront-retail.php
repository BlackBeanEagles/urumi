<?php
/** Plugin Name: Urumi Retail Storefront */
if (!defined('ABSPATH')) exit;

function urumi_retail_mark() {
    return '<span class="urumi-wordmark-symbol" aria-hidden="true">◇</span><span class="urumi-brand-words">URUMI<small>GOODS FOR EVERY DAY</small></span>';
}

function urumi_retail_category_url($slug) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) return wc_get_page_permalink('shop');
    $url = get_term_link($term);
    return is_wp_error($url) ? wc_get_page_permalink('shop') : $url;
}

add_filter('render_block', function ($html, $block) {
    if (!isset($block['blockName']) || $block['blockName'] !== 'core/template-part') return $html;
    $slug = isset($block['attrs']['slug']) ? $block['attrs']['slug'] : '';
    if ($slug === 'header') {
        $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
        $cart = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        ob_start(); ?>
        <div id="urumi-page-top" class="urumi-retail-announcement"><span>Good things for wherever the day takes you.</span><a href="<?php echo esc_url($shop); ?>">Shop the collection <span aria-hidden="true">↗</span></a></div>
        <header class="urumi-site-header">
          <div class="urumi-header-inner">
            <a class="urumi-wordmark" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Urumi Goods home"><?php echo urumi_retail_mark(); ?></a>
            <form class="urumi-product-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
              <label class="screen-reader-text" for="urumi-store-search">Search products</label>
              <span class="urumi-search-glyph" aria-hidden="true">⌕</span>
              <input id="urumi-store-search" type="search" name="s" placeholder="Search products, collections, everyday favorites" value="<?php echo esc_attr(get_search_query()); ?>">
              <input type="hidden" name="post_type" value="product">
              <button type="submit">Search <span aria-hidden="true">↗</span></button>
            </form>
            <nav class="urumi-header-actions" aria-label="Account and bag">
              <a class="urumi-account-link" href="<?php echo esc_url($account); ?>">Account</a>
              <a class="urumi-cart-link" href="<?php echo esc_url($cart); ?>" aria-label="Bag, <?php echo (int) $count; ?> items"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h16l-1 12H5L4 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>Bag <span><?php echo (int) $count; ?></span></a>
            </nav>
          </div>
          <nav class="urumi-category-nav" aria-label="Shop categories">
            <div><a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>>Discover</a><a href="<?php echo esc_url($shop); ?>"<?php echo is_shop() ? ' aria-current="page"' : ''; ?>>Shop all</a><a href="<?php echo esc_url(urumi_retail_category_url('desk-essentials')); ?>"<?php echo is_product_category('desk-essentials') ? ' aria-current="page"' : ''; ?>>Desk essentials</a><a href="<?php echo esc_url(urumi_retail_category_url('carry-everywhere')); ?>"<?php echo is_product_category('carry-everywhere') ? ' aria-current="page"' : ''; ?>>Carry everywhere</a><a href="<?php echo esc_url(urumi_retail_category_url('everyday-wear')); ?>"<?php echo is_product_category('everyday-wear') ? ' aria-current="page"' : ''; ?>>Everyday wear</a><a href="<?php echo esc_url(urumi_retail_category_url('home-and-living')); ?>"<?php echo is_product_category('home-and-living') ? ' aria-current="page"' : ''; ?>>Home &amp; living</a></div>
          </nav>
        </header>
        <?php return ob_get_clean();
    }
    if ($slug === 'footer') {
        $shop = wc_get_page_permalink('shop');
        ob_start(); ?>
        <footer class="urumi-site-footer">
          <div class="urumi-footer-cta">
            <div class="urumi-footer-cta-copy"><span class="urumi-footer-eyebrow">THE EVERYDAY EDIT / URUMI GOODS</span><h2>Make room for <em>good things.</em></h2><p>Small upgrades for the desk, the journey, and the moments in between. Find your next favorite in the collection.</p><div class="urumi-footer-cta-actions"><a class="urumi-footer-main-link" href="<?php echo esc_url($shop); ?>">Explore all products <span aria-hidden="true">↗</span></a><a class="urumi-footer-secondary-link" href="<?php echo esc_url(urumi_retail_category_url('desk-essentials')); ?>">Shop desk essentials <span aria-hidden="true">→</span></a></div></div>
            <a class="urumi-footer-visual" href="<?php echo esc_url($shop); ?>" aria-label="Explore all Urumi Goods products"><span class="urumi-footer-visual-tag">A GOOD DAY STARTS HERE <span aria-hidden="true">↗</span></span></a>
          </div>
          <div class="urumi-footer-inner">
            <div class="urumi-footer-brand"><a class="urumi-wordmark" href="<?php echo esc_url(home_url('/')); ?>"><?php echo urumi_retail_mark(); ?></a><p>Thoughtful pieces for bright ideas and ordinary days.</p><span class="urumi-footer-brand-line">DESK / JOURNEY / HOME</span></div>
            <div><h2>Explore</h2><nav aria-label="Shop collections">
              <a href="<?php echo esc_url($shop); ?>">All products</a>
              <a href="<?php echo esc_url(urumi_retail_category_url('desk-essentials')); ?>">Desk essentials</a>
              <a href="<?php echo esc_url(urumi_retail_category_url('carry-everywhere')); ?>">Carry everywhere</a>
              <a href="<?php echo esc_url(urumi_retail_category_url('everyday-wear')); ?>">Everyday wear</a>
              <a href="<?php echo esc_url(urumi_retail_category_url('home-and-living')); ?>">Home &amp; living</a>
            </nav></div>
            <div><h2>Your shop</h2><nav aria-label="Account and checkout">
              <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">My account</a>
              <a href="<?php echo esc_url(wc_get_cart_url()); ?>">View bag</a>
              <a href="<?php echo esc_url(wc_get_checkout_url()); ?>">Checkout</a>
            </nav></div>
            <div class="urumi-footer-about"><h2>Good to know</h2><p>Explore the collection, find an everyday favorite, and review your picks in your bag.</p></div>
          </div>
          <div class="urumi-footer-bottom"><span>© <?php echo esc_html(date('Y')); ?> Urumi Goods</span><a href="#urumi-page-top">Back to top ↑</a></div>
        </footer>
        <?php return ob_get_clean();
    }
    return $html;
}, 20, 2);

function urumi_retail_catalog_archive() {
    if (!function_exists('is_shop')) return false;
    if (is_shop()) return true;
    if (!is_product_category()) return false;
    $term = get_queried_object();
    return $term instanceof WP_Term && in_array($term->slug, array('desk-essentials', 'carry-everywhere', 'everyday-wear', 'home-and-living'), true);
}

add_filter('body_class', function ($classes) {
    if (urumi_retail_catalog_archive()) $classes[] = 'urumi-retail-archive';
    return $classes;
});

add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id) {
    if (!WC()->session) return;
    $product = wc_get_product($product_id);
    if ($product) WC()->session->set('urumi_added_product', $product->get_name());
}, 10, 2);

function urumi_retail_catalog() {
    if (!function_exists('wc_get_products')) return '<p>The collection is getting ready.</p>';
    $shop = wc_get_page_permalink('shop');
    $collections = array(
        'desk-essentials' => 'Desk essentials',
        'carry-everywhere' => 'Carry everywhere',
        'everyday-wear' => 'Everyday wear',
        'home-and-living' => 'Home & living',
    );
    $prices = array('all' => 'Any price', 'under-500' => 'Under ₹500', '500-999' => '₹500–₹999', '1000-plus' => '₹1,000 and above');
    $sorts = array('newest' => 'Newest first', 'price-asc' => 'Price: low to high', 'price-desc' => 'Price: high to low', 'name' => 'Name: A to Z');
    $archive_term = is_product_category() ? get_queried_object() : null;
    $archive_category = $archive_term instanceof WP_Term && isset($collections[$archive_term->slug]) ? $archive_term->slug : '';
    $search = is_search() ? sanitize_text_field(get_search_query(false)) : '';
    $category = isset($_GET['urumi_cat']) && is_string($_GET['urumi_cat']) ? sanitize_key(wp_unslash($_GET['urumi_cat'])) : $archive_category;
    if (!isset($collections[$category])) $category = $archive_category;
    $price = isset($_GET['urumi_price']) && is_string($_GET['urumi_price']) ? sanitize_key(wp_unslash($_GET['urumi_price'])) : 'all';
    if (!isset($prices[$price])) $price = 'all';
    $sort = isset($_GET['urumi_sort']) && is_string($_GET['urumi_sort']) ? sanitize_key(wp_unslash($_GET['urumi_sort'])) : 'newest';
    if (!isset($sorts[$sort])) $sort = 'newest';
    $page = isset($_GET['urumi_page']) && is_string($_GET['urumi_page']) ? max(1, absint($_GET['urumi_page'])) : 1;
    $products = wc_get_products(array('status' => 'publish', 'limit' => -1, 'orderby' => 'date', 'order' => 'DESC'));
    $products = array_values(array_filter($products, function ($product) use ($category, $price, $search) {
        if ($category && !has_term($category, 'product_cat', $product->get_id())) return false;
        if ($search && stripos(wp_strip_all_tags($product->get_name() . ' ' . $product->get_short_description() . ' ' . $product->get_description()), $search) === false) return false;
        $amount = (float) $product->get_price();
        if ($price === 'under-500' && $amount >= 500) return false;
        if ($price === '500-999' && ($amount < 500 || $amount >= 1000)) return false;
        if ($price === '1000-plus' && $amount < 1000) return false;
        return true;
    }));
    usort($products, function ($a, $b) use ($sort) {
        if ($sort === 'price-asc' || $sort === 'price-desc') {
            $result = (float) $a->get_price() <=> (float) $b->get_price();
            return $result ?: strcasecmp($a->get_name(), $b->get_name());
        }
        if ($sort === 'name') return strcasecmp($a->get_name(), $b->get_name());
        $a_date = $a->get_date_created();
        $b_date = $b->get_date_created();
        return ($b_date ? $b_date->getTimestamp() : 0) <=> ($a_date ? $a_date->getTimestamp() : 0);
    });
    if ($sort === 'price-desc') $products = array_reverse($products);
    $total = count($products);
    $per_page = 12;
    $pages = max(1, (int) ceil($total / $per_page));
    $page = min($page, $pages);
    $visible = array_slice($products, ($page - 1) * $per_page, $per_page);
    $query = array();
    if ($search) { $query['s'] = $search; $query['post_type'] = 'product'; }
    if ($category) $query['urumi_cat'] = $category;
    if ($price !== 'all') $query['urumi_price'] = $price;
    if ($sort !== 'newest') $query['urumi_sort'] = $sort;
    $stories = array(
        'desk-essentials' => array('For ideas worth keeping.', 'Notebooks, desk-side pieces, and everyday tools.'),
        'carry-everywhere' => array('Take the day with you.', 'Bags and small essentials for moving through the day.'),
        'everyday-wear' => array('Comfort with character.', 'Easy layers and accessories for ordinary days.'),
        'home-and-living' => array('Make room for slow moments.', 'Little pieces for the table, sofa, and spaces in between.'),
    );
    $headline = $search ? ($total ? 'Results for “' . $search . '”' : 'Nothing matched “' . $search . '”') : ($category ? $stories[$category][0] : 'Find your everyday favorite.');
    $intro = $search ? ($total ? 'Everyday goods that match your search.' : 'Try a different term or explore the full collection.') : ($category ? $stories[$category][1] : 'Thoughtful things for the desk, the journey, and home.');
    $clear_url = $search ? add_query_arg(array('s' => $search, 'post_type' => 'product'), $shop) : $shop;
    $page_base = $archive_category && $category === $archive_category ? get_term_link($archive_term) : $shop;
    if (is_wp_error($page_base)) $page_base = $shop;
    if ($page_base !== $shop) unset($query['urumi_cat']);
    ob_start(); ?>
    <section class="urumi-catalog" aria-label="Product collection">
      <div class="urumi-catalog-head"><div><span class="urumi-eyebrow"><?php echo esc_html($search ? 'Search the collection' : ($category ? $collections[$category] : 'Shop all')); ?></span><h1><?php echo esc_html($headline); ?></h1><p><?php echo esc_html($intro); ?></p></div><span class="urumi-catalog-total"><?php echo (int) $total; ?> <?php echo $total === 1 ? 'item' : 'items'; ?></span></div>
      <form class="urumi-catalog-controls" method="get" action="<?php echo esc_url($shop); ?>">
        <?php if ($search) : ?><input type="hidden" name="s" value="<?php echo esc_attr($search); ?>"><input type="hidden" name="post_type" value="product"><?php endif; ?>
        <label>Collection<select name="urumi_cat"><option value="">All collections</option><?php foreach ($collections as $slug => $label) : ?><option value="<?php echo esc_attr($slug); ?>" <?php selected($category, $slug); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        <label>Price<select name="urumi_price"><?php foreach ($prices as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($price, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        <label>Sort by<select name="urumi_sort"><?php foreach ($sorts as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($sort, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        <button type="submit">Show products <span aria-hidden="true">↗</span></button>
      </form>
      <div class="urumi-catalog-status"><p><?php if ($total) : ?>Showing <?php echo (int) (($page - 1) * $per_page + 1); ?>–<?php echo (int) min($page * $per_page, $total); ?> of <?php echo (int) $total; ?> products<?php else : ?>No products match these filters<?php endif; ?><?php if ($category) echo ' · ' . esc_html($collections[$category]); ?><?php if ($price !== 'all') echo ' · ' . esc_html($prices[$price]); ?></p><?php if ($category || $price !== 'all' || $sort !== 'newest') : ?><a href="<?php echo esc_url($clear_url); ?>">Clear filters ×</a><?php endif; ?></div>
      <?php if ($visible) : ?><div class="urumi-catalog-grid">
        <?php foreach ($visible as $index => $product) : $url = get_permalink($product->get_id()); $name = $product->get_name(); $terms = get_the_terms($product->get_id(), 'product_cat'); $label = 'Urumi Goods'; if ($terms && !is_wp_error($terms)) foreach ($terms as $term) if ($term->slug !== 'uncategorized') { $label = $term->name; break; } $summary = wp_trim_words(wp_strip_all_tags($product->get_short_description()), 18); ?>
        <article class="urumi-catalog-card">
          <a class="urumi-catalog-photo" href="<?php echo esc_url($url); ?>" aria-label="View <?php echo esc_attr($name); ?>"><?php if ($product->get_image_id()) echo wp_get_attachment_image($product->get_image_id(), 'woocommerce_thumbnail', false, array('alt' => $name, 'loading' => $index < 3 ? 'eager' : 'lazy')); else echo '<span class="urumi-placeholder-shape" aria-hidden="true"></span>'; ?><span aria-hidden="true">↗</span></a>
          <div class="urumi-catalog-card-copy"><span class="urumi-product-kicker"><?php echo esc_html($label); ?></span><h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($name); ?></a></h3><?php if ($summary) : ?><p><?php echo esc_html($summary); ?></p><?php endif; ?><div class="urumi-catalog-card-bottom"><strong><?php echo wp_kses_post($product->get_price_html()); ?></strong><?php if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) : ?><a data-urumi-add-to-bag href="<?php echo esc_url(add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url())); ?>">Add to bag +</a><?php else : ?><a href="<?php echo esc_url($url); ?>">View details ↗</a><?php endif; ?></div></div>
        </article>
        <?php endforeach; ?>
      </div><?php else : ?><div class="urumi-catalog-empty"><span aria-hidden="true">◇</span><h2>No matches this time.</h2><p>Try a different search, collection, or price range.</p><a href="<?php echo esc_url($shop); ?>">See all products ↗</a></div><?php endif; ?>
      <?php if ($pages > 1) : ?><nav class="urumi-catalog-pages" aria-label="Product pages"><?php for ($number = 1; $number <= $pages; $number++) : $link = add_query_arg(array_merge($query, array('urumi_page' => $number)), $page_base); ?><a href="<?php echo esc_url($link); ?>"<?php echo $number === $page ? ' aria-current="page"' : ''; ?>><?php echo (int) $number; ?></a><?php endfor; ?></nav><?php endif; ?>
    </section>
    <?php return ob_get_clean();
}

add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return 'Add to bag';
});

function urumi_retail_gallery() {
    $product = wc_get_product(get_queried_object_id());
    if (!$product || !$product->get_image_id()) return '';
    $ids = array_values(array_unique(array_merge(array($product->get_image_id()), $product->get_gallery_image_ids())));
    $first_url = wp_get_attachment_image_url($ids[0], 'large');
    if (!$first_url) return '';
    ob_start(); ?>
    <div class="urumi-detail-gallery" data-urumi-gallery>
      <button type="button" class="urumi-detail-main" data-urumi-enlarge aria-label="Enlarge product photo"><img data-urumi-main src="<?php echo esc_url($first_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>"><span>View larger ↗</span></button>
      <div class="urumi-detail-views" aria-label="Product photo views">
        <?php foreach ($ids as $index => $id) : $url = wp_get_attachment_image_url($id, 'large'); if (!$url) continue; ?>
        <button type="button" data-urumi-view data-src="<?php echo esc_url($url); ?>" data-alt="<?php echo esc_attr($product->get_name() . ' photo ' . ($index + 1)); ?>" data-crop="false" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>"><?php echo wp_get_attachment_image($id, 'thumbnail', false, array('alt' => '', 'loading' => 'lazy')); ?><span><?php echo $index === 0 ? 'Full photo' : 'Photo ' . (int) ($index + 1); ?></span></button>
        <?php endforeach; ?>
        <?php if (count($ids) === 1) : ?><button type="button" data-urumi-view data-src="<?php echo esc_url($first_url); ?>" data-alt="<?php echo esc_attr($product->get_name() . ' close-up crop of the same photo'); ?>" data-crop="true" aria-pressed="false"><?php echo wp_get_attachment_image($ids[0], 'thumbnail', false, array('alt' => '', 'loading' => 'lazy')); ?><span>Closer crop</span></button><?php endif; ?>
      </div>
      <?php if (count($ids) === 1) : ?><p class="urumi-detail-photo-note">Closer crop shows the same product photo, not a separate view.</p><?php endif; ?>
      <dialog class="urumi-detail-dialog"><button type="button" data-urumi-close aria-label="Close enlarged photo">×</button><img src="<?php echo esc_url($first_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>"></dialog>
    </div>
    <?php return ob_get_clean();
}

// Cart and Checkout blocks render independently of WooCommerce's classic hooks.
add_filter('render_block', function ($html, $block) {
    $name = isset($block['blockName']) ? $block['blockName'] : '';
    if (urumi_retail_catalog_archive()) {
        if ($name === 'core/query-title') return '';
        if ($name === 'woocommerce/product-collection') return urumi_retail_catalog();
        if (in_array($name, array('woocommerce/product-results-count', 'woocommerce/catalog-sorting'), true)) return '';
    }
    if ($name === 'woocommerce/product-image-gallery' && is_product()) {
        $gallery = urumi_retail_gallery();
        if ($gallery) return $gallery;
    }
    if ($name === 'woocommerce/add-to-cart-form' && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        $summary_html = '';
        $facts = array();
        if ($product) {
            $summary = trim(wp_strip_all_tags($product->get_short_description()));
            if ($summary) $summary_html = '<p>' . esc_html($summary) . '</p>';
            $terms = get_the_terms($product->get_id(), 'product_cat');
            if ($terms && !is_wp_error($terms)) foreach ($terms as $term) if ($term->slug !== 'uncategorized') { $facts[] = 'Collection: ' . $term->name; break; }
            if ($product->get_sku()) $facts[] = 'SKU: ' . $product->get_sku();
            if ($product->has_dimensions()) $facts[] = 'Dimensions: ' . wc_format_dimensions($product->get_dimensions(false));
            if ($product->has_weight()) $facts[] = 'Weight: ' . wc_format_weight($product->get_weight());
        }
        $facts_html = '';
        if ($summary_html || $facts) {
            $facts_html = '<div class="urumi-detail-facts"><h2>At a glance</h2>' . $summary_html;
            if ($facts) {
                $facts_html .= '<ul>';
                foreach ($facts as $fact) $facts_html .= '<li>' . esc_html($fact) . '</li>';
                $facts_html .= '</ul>';
            }
            $facts_html .= '</div>';
        }
        return $html . $facts_html . '<div class="urumi-product-note"><span aria-hidden="true">◇</span><div><strong>Made for the everyday</strong><p>Add this piece to your bag and review your selection before checkout.</p></div></div>';
    }
    if ($name !== 'woocommerce/cart' && $name !== 'woocommerce/checkout') return $html;
    $added_notice = '';
    if ($name === 'woocommerce/cart' && WC()->session) {
        $added = WC()->session->get('urumi_added_product');
        if ($added && WC()->cart && WC()->cart->get_cart_contents_count() > 0) {
            $added_notice = '<div class="urumi-cart-confirm" role="status"><span aria-hidden="true">✓</span><div><strong>' . esc_html($added) . ' added to your bag.</strong><p>Review your items, or continue to checkout.</p></div></div>';
            WC()->session->__unset('urumi_added_product');
        }
    }
    $is_checkout = $name === 'woocommerce/checkout';
    $progress = '<nav class="urumi-checkout-steps" aria-label="Shopping progress"><span class="' . ($is_checkout ? '' : 'is-current') . '">01 <span>Bag</span></span><span aria-hidden="true">→</span><span class="' . ($is_checkout ? 'is-current' : '') . '">02 <span>Checkout</span></span></nav>';
    $disclosure = $is_checkout ? '<p class="urumi-checkout-disclosure">Demo checkout: no payment is collected and no items are shipped.</p>' : '';
    return $added_notice . $progress . $disclosure . $html;
}, 25, 2);

add_action('init', function () {
    add_shortcode('urumi_storefront', function () {
        if (!function_exists('wc_get_products')) return '<p>The collection is getting ready.</p>';
        $shop = wc_get_page_permalink('shop');
        $categories = array(
            array('slug' => 'desk-essentials', 'label' => 'Desk essentials', 'caption' => 'For the ideas worth keeping'),
            array('slug' => 'carry-everywhere', 'label' => 'Carry everywhere', 'caption' => 'Out-and-about favorites'),
            array('slug' => 'everyday-wear', 'label' => 'Everyday wear', 'caption' => 'Comfort with character'),
            array('slug' => 'home-and-living', 'label' => 'Home & living', 'caption' => 'Little rituals at home'),
        );
        $products = array();
        $product_categories = array();
        foreach ($categories as $category) {
            foreach (wc_get_products(array('status' => 'publish', 'category' => array($category['slug']), 'limit' => 2, 'orderby' => 'date', 'order' => 'DESC')) as $product) {
                $products[] = $product;
                $product_categories[$product->get_id()] = $category['slug'];
            }
        }
        $spotlights = array();
        foreach (array(
            array('slug' => 'urumi-desk-mat', 'moment' => 'Make space to create', 'eyebrow' => '01 / AT YOUR DESK', 'copy' => 'A calmer surface for the ideas you want to keep.'),
            array('slug' => 'urumi-mini-crossbody-bag', 'moment' => 'Take the day with you', 'eyebrow' => '02 / OUT IN THE WORLD', 'copy' => 'Just enough room for the things that matter.'),
            array('slug' => 'urumi-soft-throw-blanket', 'moment' => 'Come home to comfort', 'eyebrow' => '03 / SLOW MOMENTS', 'copy' => 'A soft landing for the end of a full day.'),
        ) as $spec) {
            $post = get_page_by_path($spec['slug'], OBJECT, 'product');
            $product = $post ? wc_get_product($post->ID) : false;
            if ($product && $product->get_status() === 'publish' && $product->get_image_id()) {
                $spec['product'] = $product;
                $spotlights[] = $spec;
            }
        }
        $quick_picks = array_slice($spotlights, 0, 3);
        ob_start(); ?>
        <div class="urumi-storefront">
          <section class="urumi-hero" aria-labelledby="urumi-hero-title"><div class="urumi-hero-copy">
            <span class="urumi-eyebrow">URUMI GOODS · THE EVERYDAY EDIT</span>
            <h1 id="urumi-hero-title">Good things for<br><em>every kind of day.</em></h1>
            <p>Objects for brighter routines, wherever the day takes you.</p>
            <form class="urumi-hero-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
              <label class="screen-reader-text" for="urumi-hero-search">Search products</label>
              <input id="urumi-hero-search" type="search" name="s" placeholder="What are you looking for today?">
              <input type="hidden" name="post_type" value="product">
              <button type="submit" aria-label="Search products">Explore <span aria-hidden="true">↗</span></button>
            </form>
            <nav class="urumi-hero-prompts" aria-label="Quick ways to shop"><span>START WITH</span>
              <a href="<?php echo esc_url(urumi_retail_category_url('desk-essentials')); ?>">A desk refresh ↗</a>
              <a href="<?php echo esc_url(urumi_retail_category_url('carry-everywhere')); ?>">Something to carry ↗</a>
              <a href="<?php echo esc_url(urumi_retail_category_url('home-and-living')); ?>">A little comfort ↗</a>
            </nav>
          </div></section>
          <?php if ($quick_picks) : ?>
          <section class="urumi-quick-picks" aria-labelledby="urumi-quick-picks-title">
            <div class="urumi-quick-picks-intro"><span>THE SHORTLIST</span><h2 id="urumi-quick-picks-title">Start with a favorite.</h2><a href="<?php echo esc_url($shop); ?>">See all goods <span aria-hidden="true">↗</span></a></div>
            <div class="urumi-quick-picks-list">
              <?php foreach ($quick_picks as $item) : $pick = $item['product']; $pick_url = get_permalink($pick->get_id()); ?>
              <a class="urumi-quick-pick" href="<?php echo esc_url($pick_url); ?>" aria-label="View <?php echo esc_attr($pick->get_name()); ?>">
                <?php echo wp_get_attachment_image($pick->get_image_id(), 'woocommerce_thumbnail', false, array('alt' => '', 'loading' => 'eager')); ?>
                <span><small>IN THE EDIT</small><strong><?php echo esc_html($pick->get_name()); ?></strong><span class="urumi-quick-pick-price"><?php echo wp_kses_post($pick->get_price_html()); ?></span></span>
                <span class="urumi-quick-pick-arrow" aria-hidden="true">↗</span>
              </a>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>
          <section class="urumi-category-section" aria-labelledby="urumi-category-title"><div class="urumi-section-heading"><div><span class="urumi-eyebrow">01 / FIND YOUR THING</span><h2 id="urumi-category-title">Shop your way.</h2><p>Four collections for the places your day takes you.</p></div><a class="urumi-text-link" href="<?php echo esc_url($shop); ?>">View all goods <span aria-hidden="true">↗</span></a></div>
            <div class="urumi-category-grid">
            <?php foreach ($categories as $category) :
                $term = get_term_by('slug', $category['slug'], 'product_cat');
                if (!$term || !$term->count) continue;
                $sample = wc_get_products(array('status' => 'publish', 'category' => array($category['slug']), 'limit' => 1));
                $image = $sample && $sample[0]->get_image_id() ? wp_get_attachment_image_url($sample[0]->get_image_id(), 'medium_large') : '';
            ?><a class="urumi-category-card" href="<?php echo esc_url(urumi_retail_category_url($category['slug'])); ?>">
                <?php if ($image) : ?><img src="<?php echo esc_url($image); ?>" alt="" loading="lazy"><?php endif; ?>
                <span class="urumi-category-card-copy"><small><?php echo esc_html($category['caption']); ?></small><strong><?php echo esc_html($category['label']); ?></strong><span aria-hidden="true">↗</span></span>
              </a><?php endforeach; ?>
            </div>
          </section>
          <section class="urumi-manifesto" aria-labelledby="urumi-manifesto-title">
            <span class="urumi-eyebrow">02 / THE URUMI FEELING</span>
            <h2 id="urumi-manifesto-title">Your day is made of <em>little moments.</em><br>Make them yours.</h2>
            <div><p>For the first coffee, the next idea, and everything you bring along. Explore useful pieces with a little more character.</p><a href="<?php echo esc_url($shop); ?>">Explore the whole collection <span aria-hidden="true">↗</span></a></div>
          </section>
          <?php if (count($spotlights) > 1) : ?>
          <section class="urumi-lookbook" aria-labelledby="urumi-lookbook-title">
            <div class="urumi-lookbook-heading"><span class="urumi-eyebrow">03 / A DAY IN THE DETAILS</span><h2 id="urumi-lookbook-title">Good things move with you.</h2><p>Find a piece for the moment you are in. Each one is part of the live collection.</p></div>
            <div class="urumi-lookbook-shell" data-urumi-lookbook>
              <div class="urumi-lookbook-menu" role="tablist" aria-label="Shop the everyday moments">
                <?php foreach ($spotlights as $index => $item) : ?>
                <button type="button" class="urumi-lookbook-tab<?php echo $index === 0 ? ' is-active' : ''; ?>" role="tab" id="urumi-lookbook-tab-<?php echo (int) $index; ?>" aria-controls="urumi-lookbook-panel-<?php echo (int) $index; ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>"><span><?php echo esc_html($item['eyebrow']); ?></span><strong><?php echo esc_html($item['moment']); ?></strong><small><?php echo esc_html($item['copy']); ?></small><span class="urumi-lookbook-tab-arrow" aria-hidden="true">↗</span></button>
                <?php endforeach; ?>
              </div>
              <div class="urumi-lookbook-panels">
                <?php foreach ($spotlights as $index => $item) : $item_product = $item['product']; $item_url = get_permalink($item_product->get_id()); ?>
                <article class="urumi-lookbook-panel" role="tabpanel" id="urumi-lookbook-panel-<?php echo (int) $index; ?>" aria-labelledby="urumi-lookbook-tab-<?php echo (int) $index; ?>"<?php echo $index === 0 ? '' : ' hidden'; ?>>
                  <a class="urumi-lookbook-image" href="<?php echo esc_url($item_url); ?>" aria-label="View <?php echo esc_attr($item_product->get_name()); ?>"><?php echo wp_get_attachment_image($item_product->get_image_id(), 'large', false, array('alt' => $item_product->get_name(), 'loading' => 'lazy')); ?></a>
                  <div class="urumi-lookbook-product"><div><span>IN THE SPOTLIGHT</span><h3><?php echo esc_html($item_product->get_name()); ?></h3><p><?php echo wp_kses_post($item_product->get_price_html()); ?></p></div><a href="<?php echo esc_url($item_url); ?>">Shop this piece <span aria-hidden="true">↗</span></a></div>
                </article>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
          <?php endif; ?>
          <section id="urumi-featured" class="urumi-featured" aria-labelledby="urumi-featured-title"><div class="urumi-section-heading"><div><span class="urumi-eyebrow">04 / THE EVERYDAY EDIT</span><h2 id="urumi-featured-title">The collection, your way.</h2><p>Find the pieces that fit your day.</p></div><a class="urumi-text-link" href="<?php echo esc_url($shop); ?>">Shop everything <span aria-hidden="true">↗</span></a></div>
            <div class="urumi-product-filters" data-urumi-filters role="group" aria-label="Filter featured products">
              <button type="button" data-category="all" aria-pressed="true">All goods</button>
              <?php foreach ($categories as $category) : ?><button type="button" data-category="<?php echo esc_attr($category['slug']); ?>" aria-pressed="false"><?php echo esc_html($category['label']); ?></button><?php endforeach; ?>
            </div>
            <p class="screen-reader-text" id="urumi-filter-status" role="status" aria-live="polite">Showing all featured products.</p>
            <?php if ($products) : ?><div class="urumi-product-grid">
              <?php foreach ($products as $product) : $name = $product->get_name(); $url = get_permalink($product->get_id()); ?>
              <article class="urumi-product-card" data-urumi-category="<?php echo esc_attr($product_categories[$product->get_id()]); ?>"><a class="urumi-product-image" href="<?php echo esc_url($url); ?>" aria-label="View <?php echo esc_attr($name); ?>">
                <?php if ($product->get_image_id()) echo wp_get_attachment_image($product->get_image_id(), 'woocommerce_thumbnail', false, array('alt' => $name, 'loading' => 'lazy')); else echo '<span class="urumi-placeholder-shape" aria-hidden="true"></span>'; ?>
                <span class="urumi-product-arrow" aria-hidden="true">↗</span></a>
                <div class="urumi-product-info"><div><span class="urumi-product-kicker">URUMI GOODS</span><h3><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($name); ?></a></h3></div><span class="urumi-product-price"><?php echo wp_kses_post($product->get_price_html()); ?></span></div>
                <?php if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) : ?><a class="urumi-card-add" href="<?php echo esc_url(add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url())); ?>">Add to bag <span aria-hidden="true">＋</span></a><?php else : ?><a class="urumi-card-add" href="<?php echo esc_url($url); ?>">View details <span aria-hidden="true">↗</span></a><?php endif; ?>
              </article><?php endforeach; ?>
            </div><?php else : ?><p class="urumi-empty-products">Products are coming soon.</p><?php endif; ?>
          </section>
          <section class="urumi-store-questions" aria-labelledby="urumi-store-questions-title">
            <div class="urumi-store-questions-heading"><span class="urumi-eyebrow">05 / GOOD TO KNOW</span><h2 id="urumi-store-questions-title">Before you explore.</h2><p>A few useful answers for your visit.</p><a href="<?php echo esc_url($shop); ?>">Browse all goods <span aria-hidden="true">↗</span></a></div>
            <div class="urumi-store-questions-list">
              <details><summary>Is this a real store?<span aria-hidden="true">＋</span></summary><p>This is a working WooCommerce demonstration store. You can browse products, add them to your bag, and place a test order. Nothing is shipped.</p></details>
              <details><summary>Will I be charged for a test order?<span aria-hidden="true">＋</span></summary><p>No. This storefront uses a test checkout flow. No payment is collected and no goods are shipped.</p></details>
              <details><summary>Can I search or browse by collection?<span aria-hidden="true">＋</span></summary><p>Yes. Use the search bar above or choose Desk essentials, Carry everywhere, Everyday wear, or Home &amp; living to narrow the collection.</p></details>
              <details><summary>What happens after checkout?<span aria-hidden="true">＋</span></summary><p>Your test order is saved in WooCommerce so the store and AI gateway workflow can be demonstrated. It is not a real purchase.</p></details>
            </div>
          </section>
        </div>
        <?php return ob_get_clean();
    });
}, 100);

add_action('wp_enqueue_scripts', function () {
    $css = __DIR__ . '/urumi-storefront-retail.css';
    if (is_readable($css)) wp_enqueue_style('urumi-storefront-retail', plugin_dir_url(__FILE__) . 'urumi-storefront-retail.css', array('urumi-storefront-v2'), (string) filemtime($css));
    $js = __DIR__ . '/urumi-storefront-retail.js';
    if ((is_front_page() || is_product()) && is_readable($js)) wp_enqueue_script('urumi-storefront-retail', plugin_dir_url(__FILE__) . 'urumi-storefront-retail.js', array(), (string) filemtime($js), true);
}, 20);

add_action('wp_footer', function () {
    if (is_admin() || is_cart() || is_checkout()) return;
    if (is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product && $product->is_purchasable() && $product->is_in_stock()) : ?>
        <div class="urumi-mobile-product-bar"><div><small><?php echo esc_html($product->get_name()); ?></small><strong><?php echo wp_kses_post($product->get_price_html()); ?></strong></div><button type="button" data-urumi-buy>Choose quantity &amp; add ↗</button></div>
        <?php endif;
        return;
    }
    $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    ?>
    <nav class="urumi-mobile-dock" aria-label="Quick shop navigation">
      <a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><span aria-hidden="true">◇</span>Discover</a>
      <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"<?php echo is_shop() ? ' aria-current="page"' : ''; ?>><span aria-hidden="true">▦</span>Shop</a>
      <a href="<?php echo esc_url(wc_get_cart_url()); ?>"><span aria-hidden="true">▤</span>Bag<?php if ($count) : ?><small><?php echo (int) $count; ?></small><?php endif; ?></a>
    </nav>
    <?php
}, 30);
