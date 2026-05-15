<?php
get_header();

$categories = function_exists('get_terms')
	? get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 8,
		)
	)
	: array();

$featured_limit = max(1, min(12, absint(pcgear_store_theme_mod('featured_limit', 8))));
$latest_limit   = max(1, min(12, absint(pcgear_store_theme_mod('latest_limit', 4))));

$featured_products = function_exists('wc_get_products')
	? wc_get_products(
		array(
			'status'   => 'publish',
			'featured' => true,
			'limit'    => $featured_limit,
		)
	)
	: array();

$latest_products = function_exists('wc_get_products')
	? wc_get_products(
		array(
			'status'  => 'publish',
			'orderby' => 'date',
			'order'   => 'DESC',
			'limit'   => $latest_limit,
		)
	)
	: array();

$hero_product = ! empty($featured_products) ? $featured_products[0] : null;
$builder_url  = pcgear_store_page_url('build-pc', home_url('/build-pc/'));
$shop_url     = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$hero_image   = $hero_product instanceof WC_Product ? wp_get_attachment_image_url($hero_product->get_image_id(), 'large') : '';
$hero_name    = $hero_product instanceof WC_Product ? $hero_product->get_name() : __('Thermalright Peerless Assassin 120 SE', 'pcgear-store');
$hero_price   = $hero_product instanceof WC_Product ? $hero_product->get_price_html() : (function_exists('wc_price') ? wc_price(24990000) : '24.990.000 ₫');
$hero_copy    = $hero_product instanceof WC_Product ? wp_strip_all_tags($hero_product->get_short_description()) : __('Tản nhiệt khí hiệu năng cao cho CPU gaming mạnh và máy làm việc nặng.', 'pcgear-store');
$hero_kicker  = $hero_product instanceof WC_Product ? strtoupper(wp_strip_all_tags(wc_get_product_category_list($hero_product->get_id(), ' / '))) : __('Tản nhiệt / Hiệu năng', 'pcgear-store');

$custom_hero_image = pcgear_store_theme_mod('hero_image', '');
$custom_hero_name  = pcgear_store_theme_mod('hero_title', '');
$custom_hero_copy  = pcgear_store_theme_mod('hero_text', '');
$custom_hero_label = pcgear_store_theme_mod('hero_kicker', '');

if ('' !== $custom_hero_image) {
	$hero_image = $custom_hero_image;
}

if ('' !== $custom_hero_name) {
	$hero_name = $custom_hero_name;
}

if ('' !== $custom_hero_copy) {
	$hero_copy = $custom_hero_copy;
}

if ('' !== $custom_hero_label) {
	$hero_kicker = $custom_hero_label;
}

$hero_primary_label   = pcgear_store_theme_mod('hero_primary_label', __('Mua ngay', 'pcgear-store'));
$hero_primary_url     = pcgear_store_theme_mod('hero_primary_url', '');
$hero_secondary_label = pcgear_store_theme_mod('hero_secondary_label', __('Xem cửa hàng', 'pcgear-store'));
$hero_secondary_url   = pcgear_store_theme_mod('hero_secondary_url', '');

if ('' === $hero_primary_url) {
	$hero_primary_url = $hero_product instanceof WC_Product ? get_permalink($hero_product->get_id()) : $shop_url;
}

if ('' === $hero_secondary_url) {
	$hero_secondary_url = $shop_url;
}

$feature_items = array(
	array(
		'eyebrow' => __('Hiệu năng', 'pcgear-store'),
		'title'   => __('Danh mục linh kiện được chọn lọc để trình bày gọn, rõ và đúng chất công nghệ cao cấp.', 'pcgear-store'),
		'link'    => $shop_url,
		'cta'     => __('Mua ngay', 'pcgear-store'),
	),
	array(
		'eyebrow' => __('PC Builder', 'pcgear-store'),
		'title'   => __('Tự chọn CPU, mainboard, RAM, VGA và kiểm tra tương thích ngay trong luồng mua sắm.', 'pcgear-store'),
		'link'    => $builder_url,
		'cta'     => __('Mở trình dựng', 'pcgear-store'),
	),
	array(
		'eyebrow' => __('Demo BCCĐ', 'pcgear-store'),
		'title'   => __('Theme gọn để tập trung vào plugin riêng, WooCommerce và trải nghiệm trình bày khi bảo vệ.', 'pcgear-store'),
		'link'    => $builder_url,
		'cta'     => __('Xem luồng demo', 'pcgear-store'),
	),
);

$trust_items = array(
	__('Sản phẩm thật, ảnh thật, dữ liệu rõ ràng', 'pcgear-store'),
	__('Luồng mua sắm và build PC liền mạch', 'pcgear-store'),
	__('Giao diện dark premium lấy cảm hứng từ Corsair', 'pcgear-store'),
	__('Tối ưu để demo nhanh trên local hoặc VPS', 'pcgear-store'),
);
?>
<section class="hero-section">
	<div class="hero-layout">
		<div class="hero-copy" data-reveal="fade-up">
			<span class="section-kicker"><?php echo esc_html($hero_kicker); ?></span>
			<h1><?php echo esc_html($hero_name); ?></h1>
			<p><?php echo esc_html($hero_copy); ?></p>
			<div class="hero-actions">
				<a class="pc-button" href="<?php echo esc_url($hero_primary_url); ?>"><?php echo esc_html($hero_primary_label); ?></a>
				<a class="pc-button pc-button--ghost" href="<?php echo esc_url($hero_secondary_url); ?>"><?php echo esc_html($hero_secondary_label); ?></a>
			</div>
			<div class="hero-ticker">
				<span><?php echo esc_html__('Hiệu năng cao', 'pcgear-store'); ?></span>
				<span><?php echo esc_html__('Thiết kế premium', 'pcgear-store'); ?></span>
				<span><?php echo esc_html__('Sẵn sàng build PC', 'pcgear-store'); ?></span>
			</div>
		</div>

		<div class="hero-stage" data-reveal="fade-up">
			<div class="hero-stage__frame">
				<?php if ($hero_image) : ?>
					<img src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($hero_name); ?>">
				<?php endif; ?>
			</div>
			<div class="hero-stage__card">
				<span class="hero-stage__label"><?php echo esc_html__('Nổi bật trong tuần', 'pcgear-store'); ?></span>
				<h2><?php echo esc_html($hero_name); ?></h2>
				<div class="hero-stage__price"><?php echo wp_kses_post($hero_price); ?></div>
				<a class="hero-stage__link" href="<?php echo esc_url($hero_primary_url); ?>">
					<?php echo esc_html__('Xem sản phẩm', 'pcgear-store'); ?>
				</a>
			</div>
		</div>
	</div>
</section>

<section class="category-strip" data-reveal="fade-up">
	<div class="category-strip__inner">
		<?php if (! empty($categories) && ! is_wp_error($categories)) : ?>
			<?php foreach ($categories as $category) : ?>
				<?php $category_link = get_term_link($category); ?>
				<?php if (! is_wp_error($category_link)) : ?>
					<a class="category-chip" href="<?php echo esc_url($category_link); ?>">
						<span>//</span>
						<strong><?php echo esc_html($category->name); ?></strong>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</section>

<section class="promo-section">
	<div class="promo-grid">
		<?php foreach ($feature_items as $feature_item) : ?>
			<article class="promo-card" data-reveal="fade-up">
				<span class="section-kicker"><?php echo esc_html($feature_item['eyebrow']); ?></span>
				<h2><?php echo esc_html($feature_item['title']); ?></h2>
				<a class="promo-card__link" href="<?php echo esc_url($feature_item['link']); ?>">
					<?php echo esc_html($feature_item['cta']); ?>
				</a>
			</article>
		<?php endforeach; ?>
	</div>
</section>

<section class="section-block">
	<div class="section-head" data-reveal="fade-up">
		<div>
			<span class="section-kicker"><?php echo esc_html__('Shop by category', 'pcgear-store'); ?></span>
			<h2><?php echo esc_html__('Danh mục chính cho một bộ máy gaming hoàn chỉnh', 'pcgear-store'); ?></h2>
		</div>
		<a class="section-link" href="<?php echo esc_url($shop_url); ?>"><?php echo esc_html__('Xem toàn bộ cửa hàng', 'pcgear-store'); ?></a>
	</div>
	<div class="category-grid">
		<?php if (! empty($categories) && ! is_wp_error($categories)) : ?>
			<?php foreach ($categories as $category) : ?>
				<?php $category_link = get_term_link($category); ?>
				<?php if (! is_wp_error($category_link)) : ?>
					<a class="category-card" href="<?php echo esc_url($category_link); ?>" data-reveal="fade-up">
						<span class="category-card__code">// <?php echo esc_html(strtoupper($category->slug)); ?></span>
						<strong><?php echo esc_html($category->name); ?></strong>
						<span><?php echo esc_html($category->count); ?> <?php echo esc_html__('sản phẩm', 'pcgear-store'); ?></span>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="empty-state"><?php echo esc_html__('Chưa có danh mục sản phẩm.', 'pcgear-store'); ?></div>
		<?php endif; ?>
	</div>
</section>

<section class="section-block">
	<div class="section-head" data-reveal="fade-up">
		<div>
			<span class="section-kicker"><?php echo esc_html__('Featured gear', 'pcgear-store'); ?></span>
			<h2><?php echo esc_html__('Linh kiện nổi bật cho góc máy premium', 'pcgear-store'); ?></h2>
		</div>
	</div>
	<div class="product-grid">
		<?php if (! empty($featured_products)) : ?>
			<?php foreach ($featured_products as $product) : ?>
				<?php pcgear_store_render_product_card($product); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="empty-state"><?php echo esc_html__('Chưa có sản phẩm nổi bật.', 'pcgear-store'); ?></div>
		<?php endif; ?>
	</div>
</section>

<section class="builder-spotlight" data-reveal="fade-up">
	<div class="builder-spotlight__copy">
		<span class="section-kicker"><?php echo esc_html__('Customize your setup', 'pcgear-store'); ?></span>
		<h2><?php echo esc_html__('Trình dựng PC là điểm nhấn để website không chỉ dừng ở mức bán hàng.', 'pcgear-store'); ?></h2>
		<p><?php echo esc_html__('Người dùng chọn linh kiện trực tiếp, hệ thống đối chiếu socket CPU, loại RAM và không gian vỏ case, sau đó thêm toàn bộ cấu hình vào giỏ hàng.', 'pcgear-store'); ?></p>
		<a class="pc-button" href="<?php echo esc_url($builder_url); ?>"><?php echo esc_html__('Mở trình dựng PC', 'pcgear-store'); ?></a>
	</div>
	<div class="builder-spotlight__list">
		<div class="builder-point">
			<strong>01</strong>
			<span><?php echo esc_html__('Chọn linh kiện từ danh mục thực tế của WooCommerce.', 'pcgear-store'); ?></span>
		</div>
		<div class="builder-point">
			<strong>02</strong>
			<span><?php echo esc_html__('Kiểm tra tương thích tự động trong thời gian thực.', 'pcgear-store'); ?></span>
		</div>
		<div class="builder-point">
			<strong>03</strong>
			<span><?php echo esc_html__('Thêm toàn bộ cấu hình vào giỏ hàng bằng một thao tác.', 'pcgear-store'); ?></span>
		</div>
	</div>
</section>

<section class="section-block">
	<div class="section-head" data-reveal="fade-up">
		<div>
			<span class="section-kicker"><?php echo esc_html__('Latest additions', 'pcgear-store'); ?></span>
			<h2><?php echo esc_html__('Sản phẩm mới cập nhật', 'pcgear-store'); ?></h2>
		</div>
	</div>
	<div class="product-grid product-grid--compact">
		<?php if (! empty($latest_products)) : ?>
			<?php foreach ($latest_products as $product) : ?>
				<?php pcgear_store_render_product_card($product); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="empty-state"><?php echo esc_html__('Chưa có sản phẩm mới.', 'pcgear-store'); ?></div>
		<?php endif; ?>
	</div>
</section>

<section class="trust-section" data-reveal="fade-up">
	<div class="trust-grid">
		<?php foreach ($trust_items as $trust_item) : ?>
			<div class="trust-card">
				<span class="trust-card__icon">+</span>
				<p><?php echo esc_html($trust_item); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php
get_footer();
