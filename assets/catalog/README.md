# Urumi Goods demo catalog images

These 20 original, AI-generated catalog photographs were created for the local WooCommerce demonstration. They depict concept products, not inventory that exists for sale. The store footer and product descriptions state that test orders are not fulfilled.

The source prompts requested square, photorealistic ecommerce/product-editorial images with warm natural light, neutral materials, the entire named item visible, and no readable text, logos, or watermarks. Each prompt specified the subject and setting—for example, a linen planner on a pale stone desk, gel pens on ivory paper, a brass bookmark in an open book, a canvas crossbody bag in a studio, and a glass water carafe on a wood table. Images were generated with the built-in image tool and losslessly retained in its generation folder; the files here are optimized WebP conversions for the store.

The filename in each `image` entry of `scripts/seed-demo-products.php` maps an image to a WooCommerce SKU. Run the seeder after copying these files into the WordPress pod at `/tmp/urumi-product-images/`. The seeder leaves any existing merchant-selected product image untouched.
