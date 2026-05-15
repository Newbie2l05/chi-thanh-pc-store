<?php
defined('ABSPATH') || exit;

global $product;

if (! $product instanceof WC_Product) {
	return;
}

$product_id       = (int) $product->get_id();
$gallery_ids      = pcgear_store_get_product_gallery_ids($product);
$specs            = pcgear_store_get_product_specs($product_id);
$primary_category = wc_get_product_category_list($product_id, ' / ');
$category_terms   = get_the_terms($product_id, 'product_cat');
$category_link    = '';
$category_name    = '';
$related_ids      = wc_get_related_products($product_id, 4);
$short_desc       = wp_strip_all_tags($product->get_short_description());
$description      = apply_filters('the_content', $product->get_description());
$builder_url      = pcgear_store_page_url('build-pc', home_url('/build-pc/'));
$edit_url         = false;
$thumb_specs      = array_slice($specs, 0, 6);

if (! empty($category_terms) && ! is_wp_error($category_terms)) {
	$primary_term  = array_shift($category_terms);
	$category_name = $primary_term->name;
	$category_link = get_term_link($primary_term);
	if (is_wp_error($category_link)) {
		$category_link = '';
	}
}

?>
<article id="product-<?php the_ID(); ?>" <?php wc_product_class('pc-single-product', $product); ?>>
	<?php do_action('woocommerce_before_single_product'); ?>

	<?php if (post_password_required()) : ?>
		<?php echo get_the_password_form(); ?>
		<?php return; ?>
	<?php endif; ?>

	<div class="pc-single-product__hero">
		<div class="pc-single-product__gallery" data-product-gallery>
			<?php if (function_exists('woocommerce_breadcrumb')) : ?>
				<div class="pc-single-product__breadcrumb">
					<?php woocommerce_breadcrumb(); ?>
				</div>
			<?php endif; ?>

			<div class="pc-single-product__gallery-shell">
				<?php if (! empty($gallery_ids)) : ?>
					<div class="pc-single-product__thumbs">
						<?php foreach ($gallery_ids as $index => $attachment_id) : ?>
							<?php
							$thumb_url = wp_get_attachment_image_url($attachment_id, 'woocommerce_thumbnail');
							$full_url  = wp_get_attachment_image_url($attachment_id, 'full');
							$srcset    = wp_get_attachment_image_srcset($attachment_id, 'large');
							?>
							<button
								class="pc-single-product__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
								type="button"
								data-product-thumb
								data-full="<?php echo esc_url($full_url); ?>"
								data-srcset="<?php echo esc_attr((string) $srcset); ?>"
								data-alt="<?php echo esc_attr($product->get_name()); ?>"
								aria-label="<?php echo esc_attr(sprintf(__('Ảnh %d', 'pcgear-store'), $index + 1)); ?>"
							>
								<img src="<?php echo esc_url($thumb_url ?: $full_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="pc-single-product__main">
					<?php
					$main_id  = ! empty($gallery_ids) ? $gallery_ids[0] : 0;
					$main_url = $main_id ? wp_get_attachment_image_url($main_id, 'full') : wc_placeholder_img_src('full');
					$main_set = $main_id ? wp_get_attachment_image_srcset($main_id, 'large') : '';
					?>
					<div class="pc-single-product__image-frame">
						<img
							src="<?php echo esc_url($main_url); ?>"
							<?php if ($main_set) : ?>
								srcset="<?php echo esc_attr($main_set); ?>"
								sizes="(max-width: 1200px) 100vw, 720px"
							<?php endif; ?>
							alt="<?php echo esc_attr($product->get_name()); ?>"
							data-product-main-image
						>
					</div>
				</div>
			</div>
		</div>

		<div class="pc-single-product__summary">
			<div class="pc-single-product__summary-card">
				<?php if ($category_name) : ?>
					<div class="pc-single-product__eyebrow">
						<?php if ($category_link) : ?>
							<a href="<?php echo esc_url($category_link); ?>"><?php echo esc_html($category_name); ?></a>
						<?php else : ?>
							<span><?php echo esc_html($category_name); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<h1 class="product_title entry-title"><?php echo esc_html($product->get_name()); ?></h1>

				<?php if ($short_desc) : ?>
					<p class="pc-single-product__intro"><?php echo esc_html($short_desc); ?></p>
				<?php endif; ?>

				<div class="pc-single-product__price-row">
					<div class="pc-single-product__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
					<div class="pc-single-product__stock <?php echo $product->is_in_stock() ? 'is-instock' : 'is-outstock'; ?>">
						<?php echo esc_html($product->is_in_stock() ? __('Còn hàng', 'pcgear-store') : __('Hết hàng', 'pcgear-store')); ?>
					</div>
				</div>

				<div class="pc-single-product__meta">
					<span><?php echo esc_html__('SKU', 'pcgear-store'); ?>: <?php echo esc_html($product->get_sku() ?: 'N/A'); ?></span>
					<?php if ($primary_category) : ?>
						<span><?php echo wp_kses_post($primary_category); ?></span>
					<?php endif; ?>
				</div>

				<div class="pc-single-product__cart">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>

				<div class="pc-single-product__actions">
					<?php if ($edit_url) : ?>
						<a class="pc-single-product__edit" href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Sửa ảnh, giá và mô tả sản phẩm', 'pcgear-store'); ?></a>
					<?php endif; ?>
					<a class="pc-button pc-button--ghost" href="<?php echo esc_url($builder_url); ?>"><?php echo esc_html__('Build cùng linh kiện này', 'pcgear-store'); ?></a>
				</div>
			</div>

			<?php if (! empty($thumb_specs)) : ?>
				<div class="pc-single-product__feature-card">
					<h2><?php echo esc_html__('Thông số nổi bật', 'pcgear-store'); ?></h2>
					<div class="pc-single-product__spec-list">
						<?php foreach ($thumb_specs as $spec) : ?>
							<div class="pc-single-product__spec-row">
								<span><?php echo esc_html(pcgear_store_format_spec_label($spec['spec_key'])); ?></span>
								<strong><?php echo esc_html(pcgear_store_format_spec_value($spec)); ?></strong>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="pc-single-product__sections">
		<section class="pc-single-product__section">
			<div class="pc-single-product__section-head">
				<span class="section-kicker"><?php echo esc_html__('Overview', 'pcgear-store'); ?></span>
				<h2><?php echo esc_html__('Mô tả sản phẩm', 'pcgear-store'); ?></h2>
			</div>
			<div class="pc-single-product__content">
				<?php echo wp_kses_post($description); ?>
			</div>
		</section>

		<?php if (! empty($specs)) : ?>
			<section class="pc-single-product__section">
				<div class="pc-single-product__section-head">
					<span class="section-kicker"><?php echo esc_html__('Specifications', 'pcgear-store'); ?></span>
					<h2><?php echo esc_html__('Thông số kỹ thuật', 'pcgear-store'); ?></h2>
				</div>
				<div class="pc-single-product__spec-grid">
					<?php foreach ($specs as $spec) : ?>
						<div class="pc-single-product__spec-row">
							<span><?php echo esc_html(pcgear_store_format_spec_label($spec['spec_key'])); ?></span>
							<strong><?php echo esc_html(pcgear_store_format_spec_value($spec)); ?></strong>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php if (! empty($related_ids)) : ?>
		<section class="pc-single-related">
			<div class="pc-single-product__section-head">
				<span class="section-kicker"><?php echo esc_html__('You May Also Like', 'pcgear-store'); ?></span>
				<h2><?php echo esc_html__('Sản phẩm cùng nhóm', 'pcgear-store'); ?></h2>
			</div>
			<div class="pc-single-related__grid">
				<?php foreach ($related_ids as $related_id) : ?>
					<?php
					$related_product = wc_get_product($related_id);
					if ($related_product instanceof WC_Product) {
						pcgear_store_render_product_card($related_product);
					}
					?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</article>
