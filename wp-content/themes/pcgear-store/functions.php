<?php

if (! defined('ABSPATH')) {
	exit;
}

function pcgear_store_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('woocommerce');
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary' => __('Menu chính', 'pcgear-store'),
		)
	);
}
add_action('after_setup_theme', 'pcgear_store_setup');

function pcgear_store_redirect_to_primary_host() {
	if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}

	if (PHP_SAPI === 'cli' || headers_sent() || empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
		return;
	}

	$home_url    = home_url('/');
	$home_parts  = wp_parse_url($home_url);
	$target_host = $home_parts['host'] ?? '';

	if ('' === $target_host) {
		return;
	}

	$current_host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));

	if (strtolower($current_host) === strtolower($target_host)) {
		return;
	}

	$target_scheme = $home_parts['scheme'] ?? 'http';
	$target_port   = isset($home_parts['port']) ? ':' . (int) $home_parts['port'] : '';
	$request_uri   = wp_unslash($_SERVER['REQUEST_URI']);
	$redirect_url  = $target_scheme . '://' . $target_host . $target_port . $request_uri;

	wp_safe_redirect($redirect_url, 301);
	exit;
}
add_action('template_redirect', 'pcgear_store_redirect_to_primary_host', 1);

function pcgear_store_enqueue_assets() {
	$theme_version = '2.4.9';

	wp_enqueue_style(
		'pcgear-store-fonts',
		'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
	wp_enqueue_style('pcgear-store-style', get_stylesheet_uri(), array(), '1.0.0');
	wp_enqueue_style(
		'pcgear-store-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array('pcgear-store-style', 'pcgear-store-fonts'),
		$theme_version
	);
	wp_add_inline_style('pcgear-store-theme', pcgear_store_get_custom_css());
	wp_enqueue_script(
		'pcgear-store-navigation',
		get_template_directory_uri() . '/assets/js/navigation.js',
		array(),
		$theme_version,
		true
	);
}
add_action('wp_enqueue_scripts', 'pcgear_store_enqueue_assets');

function pcgear_store_limit_generated_image_sizes($sizes) {
	$allowed_sizes = array(
		'thumbnail',
		'large',
		'woocommerce_thumbnail',
		'woocommerce_single',
		'woocommerce_gallery_thumbnail',
	);

	foreach (array_keys($sizes) as $size_name) {
		if (! in_array($size_name, $allowed_sizes, true)) {
			unset($sizes[$size_name]);
		}
	}

	return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'pcgear_store_limit_generated_image_sizes');

function pcgear_store_products_per_page() {
	$products_per_page = absint(get_theme_mod('pcgear_shop_products_per_page', 12));
	return max(4, min(48, $products_per_page));
}
add_filter('loop_shop_per_page', 'pcgear_store_products_per_page', 20);

function pcgear_store_loop_columns() {
	$columns = absint(get_theme_mod('pcgear_shop_columns', 4));
	return max(2, min(6, $columns));
}
add_filter('loop_shop_columns', 'pcgear_store_loop_columns');

function pcgear_store_force_vnd_symbol($currency_symbol, $currency) {
	if ('VND' === $currency) {
		return '₫';
	}

	return $currency_symbol;
}
add_filter('woocommerce_currency_symbol', 'pcgear_store_force_vnd_symbol', 10, 2);

function pcgear_store_cart_count() {
	if (! function_exists('WC') || ! WC()->cart) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

function pcgear_store_theme_mod($key, $default = '') {
	return get_theme_mod('pcgear_' . $key, $default);
}

function pcgear_store_get_custom_css() {
	$accent       = sanitize_hex_color(pcgear_store_theme_mod('accent_color', '#f7b500')) ?: '#f7b500';
	$accent_bold  = sanitize_hex_color(pcgear_store_theme_mod('accent_strong_color', '#ffcb39')) ?: '#ffcb39';
	$columns      = max(2, min(6, absint(pcgear_store_theme_mod('shop_columns', 4))));
	$image_fit    = pcgear_store_theme_mod('product_image_fit', 'contain');
	$image_fit    = in_array($image_fit, array('contain', 'cover'), true) ? $image_fit : 'contain';
	$button_css   = pcgear_store_theme_mod('show_product_button', true) ? '' : '.shop-shell__body .products li.product .button,.pc-card .pc-button{display:none!important;}';

	return sprintf(
		':root{--accent:%1$s;--accent-strong:%2$s;}@media (min-width:1024px){.shop-shell__body .products{grid-template-columns:repeat(%3$d,minmax(0,1fr));}}.shop-shell__body .products li.product img,.pc-card__media img{object-fit:%4$s;}%5$s',
		esc_html($accent),
		esc_html($accent_bold),
		$columns,
		esc_html($image_fit),
		$button_css
	);
}

function pcgear_store_page_url($slug, $fallback = '') {
	$page = get_page_by_path($slug);
	return $page ? get_permalink($page) : $fallback;
}

function pcgear_store_filter_account_menu_items($items) {
	unset($items['dashboard'], $items['downloads']);

	$preferred_order = array(
		'edit-account',
		'orders',
		'edit-address',
		'payment-methods',
		'customer-logout',
	);

	$ordered_items = array();

	foreach ($preferred_order as $key) {
		if (isset($items[$key])) {
			$ordered_items[$key] = $items[$key];
		}
	}

	foreach ($items as $key => $label) {
		if (! isset($ordered_items[$key])) {
			$ordered_items[$key] = $label;
		}
	}

	return $ordered_items;
}
add_filter('woocommerce_account_menu_items', 'pcgear_store_filter_account_menu_items', 20);

function pcgear_store_redirect_account_endpoints() {
	if (! function_exists('is_account_page') || ! function_exists('wc_get_account_endpoint_url')) {
		return;
	}

	if (is_admin() || wp_doing_ajax() || ! is_account_page() || ! is_user_logged_in()) {
		return;
	}

	if (is_wc_endpoint_url('downloads')) {
		wp_safe_redirect(wc_get_account_endpoint_url('edit-account'));
		exit;
	}

	if (! is_wc_endpoint_url()) {
		wp_safe_redirect(wc_get_account_endpoint_url('edit-account'));
		exit;
	}
}
add_action('template_redirect', 'pcgear_store_redirect_account_endpoints', 20);

function pcgear_store_primary_links() {
	$shop_url    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
	$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
	$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
	$builder_url = pcgear_store_page_url('build-pc', home_url('/build-pc/'));

	return array(
		array(
			'label' => __('Trang chủ', 'pcgear-store'),
			'url'   => home_url('/'),
		),
		array(
			'label' => __('Cửa hàng', 'pcgear-store'),
			'url'   => $shop_url,
		),
		array(
			'label' => __('Xây dựng PC', 'pcgear-store'),
			'url'   => $builder_url,
		),
		array(
			'label' => __('Tài khoản', 'pcgear-store'),
			'url'   => $account_url,
		),
		array(
			'label' => __('Giỏ hàng', 'pcgear-store'),
			'url'   => $cart_url,
		),
	);
}

function pcgear_store_header_navigation() {
	return pcgear_store_public_navigation();
}

function pcgear_store_render_product_card($product) {
	if (! $product instanceof WC_Product) {
		return;
	}
	?>
	<article class="pc-card" data-reveal="fade-up">
		<a class="pc-card__media" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
			<?php echo $product->get_image('woocommerce_thumbnail'); ?>
			<span class="pc-card__actions">
				<span class="pc-card__action"><?php echo esc_html__('Xem nhanh', 'pcgear-store'); ?></span>
				<span class="pc-card__action"><?php echo esc_html__('Chi tiết', 'pcgear-store'); ?></span>
			</span>
		</a>
		<div class="pc-card__body">
			<div class="pc-card__meta"><?php echo wp_kses_post(wc_get_product_category_list($product->get_id(), ' / ')); ?></div>
			<h3><a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
			<div class="pc-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
			<a class="pc-button pc-button--ghost" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
				<?php echo esc_html__('Xem chi tiết', 'pcgear-store'); ?>
			</a>
		</div>
	</article>
	<?php
}

function pcgear_store_support_page_url() {
	return pcgear_store_page_url('cau-hoi-thuong-gap', home_url('/cau-hoi-thuong-gap/'));
}

function pcgear_store_public_navigation() {
	$shop_url      = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
	$builder_url   = pcgear_store_page_url('build-pc', home_url('/build-pc/'));
	$guides_url    = pcgear_store_page_url('flow-demo-bccd', $builder_url);
	$software_url  = pcgear_store_page_url('giai-thich-pc-builder', $builder_url);
	$categories    = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 8,
		)
	);
	$product_links = array();

	if (! is_wp_error($categories) && ! empty($categories)) {
		foreach ($categories as $category) {
			$link = get_term_link($category);
			if (is_wp_error($link)) {
				continue;
			}

			$product_links[] = array(
				'label' => $category->name,
				'url'   => $link,
			);
		}
	}

	return array(
		array(
			'key'    => 'products',
			'label'  => __('Sản phẩm', 'pcgear-store'),
			'url'    => $shop_url,
			'groups' => array(
				array(
					'type'  => 'list',
					'title' => __('Linh kiện PC', 'pcgear-store'),
					'links' => $product_links,
				),
				array(
					'type'  => 'list',
					'title' => __('Gaming gear', 'pcgear-store'),
					'links' => array(
						array('label' => __('Tản nhiệt', 'pcgear-store'), 'url' => home_url('/product-category/cooler/')),
						array('label' => __('Card đồ họa', 'pcgear-store'), 'url' => home_url('/product-category/gpu/')),
						array('label' => __('Nguồn máy tính', 'pcgear-store'), 'url' => home_url('/product-category/psu/')),
						array('label' => __('SSD tốc độ cao', 'pcgear-store'), 'url' => home_url('/product-category/ssd/')),
					),
				),
				array(
					'type'  => 'list',
					'title' => __('Mua sắm', 'pcgear-store'),
					'links' => array(
						array('label' => __('Toàn bộ cửa hàng', 'pcgear-store'), 'url' => $shop_url),
						array('label' => __('Sản phẩm nổi bật', 'pcgear-store'), 'url' => add_query_arg('orderby', 'popularity', $shop_url)),
						array('label' => __('Sản phẩm mới', 'pcgear-store'), 'url' => add_query_arg('orderby', 'date', $shop_url)),
						array('label' => __('PC Builder', 'pcgear-store'), 'url' => $builder_url),
					),
				),
				array(
					'type'  => 'feature',
					'title' => __('Build nhanh một cấu hình hoàn chỉnh', 'pcgear-store'),
					'text'  => __('Chọn linh kiện, kiểm tra tương thích và thêm toàn bộ build vào giỏ hàng chỉ trong một luồng.', 'pcgear-store'),
					'link'  => $builder_url,
					'cta'   => __('Mở PC Builder', 'pcgear-store'),
				),
			),
		),
		array(
			'key'    => 'guides',
			'label'  => __('Hướng dẫn', 'pcgear-store'),
			'url'    => $guides_url,
			'groups' => array(
				array(
					'type'  => 'list',
					'title' => __('Bài hướng dẫn', 'pcgear-store'),
					'links' => array(
						array('label' => __('Cách chọn CPU và mainboard', 'pcgear-store'), 'url' => pcgear_store_page_url('huong-dan-chon-cpu-mainboard', $guides_url)),
						array('label' => __('Kiểm tra socket và RAM', 'pcgear-store'), 'url' => pcgear_store_page_url('huong-dan-socket-va-ram', $guides_url)),
						array('label' => __('Ước tính công suất nguồn', 'pcgear-store'), 'url' => pcgear_store_page_url('huong-dan-cong-suat-nguon', $guides_url)),
					),
				),
			),
		),
		array(
			'key'    => 'software',
			'label'  => __('Phần mềm', 'pcgear-store'),
			'url'    => $software_url,
			'groups' => array(
				array(
					'type'  => 'list',
					'title' => __('PC Builder Plugin', 'pcgear-store'),
					'links' => array(
						array('label' => __('Giới thiệu PC Builder', 'pcgear-store'), 'url' => pcgear_store_page_url('giai-thich-pc-builder', $software_url)),
						array('label' => __('Kiểm tra tương thích', 'pcgear-store'), 'url' => pcgear_store_page_url('kiem-tra-tuong-thich', $software_url)),
						array('label' => __('Thêm build vào giỏ hàng', 'pcgear-store'), 'url' => $builder_url),
					),
				),
				array(
					'type'  => 'list',
					'title' => __('Công nghệ sử dụng', 'pcgear-store'),
					'links' => array(
						array('label' => __('WordPress + WooCommerce', 'pcgear-store'), 'url' => $shop_url),
						array('label' => __('Custom Theme', 'pcgear-store'), 'url' => home_url('/')),
						array('label' => __('MySQL Database', 'pcgear-store'), 'url' => $software_url),
						array('label' => __('GitHub Repository', 'pcgear-store'), 'url' => home_url('/')),
					),
				),
			),
		),
	);
}

function pcgear_store_get_product_specs($product_id) {
	global $wpdb;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT spec_key, spec_value, spec_value_numeric, unit
			FROM {$wpdb->prefix}pc_product_specs
			WHERE product_id = %d
			ORDER BY spec_key ASC",
			$product_id
		),
		ARRAY_A
	);

	return is_array($rows) ? $rows : array();
}

function pcgear_store_format_spec_label($spec_key) {
	$labels = array(
		'socket'         => __('Socket', 'pcgear-store'),
		'tdp'            => __('TDP', 'pcgear-store'),
		'power_draw'     => __('Công suất tiêu thụ', 'pcgear-store'),
		'ram_type'       => __('Chuẩn RAM', 'pcgear-store'),
		'form_factor'    => __('Kích thước chuẩn', 'pcgear-store'),
		'capacity'       => __('Dung lượng', 'pcgear-store'),
		'speed'          => __('Tốc độ', 'pcgear-store'),
		'vram'           => __('Bộ nhớ đồ họa', 'pcgear-store'),
		'interface'      => __('Chuẩn giao tiếp', 'pcgear-store'),
		'wattage'        => __('Công suất nguồn', 'pcgear-store'),
		'efficiency'     => __('Hiệu suất', 'pcgear-store'),
		'gpu_max_length' => __('Chiều dài GPU tối đa', 'pcgear-store'),
		'socket_support' => __('Socket hỗ trợ', 'pcgear-store'),
		'cooler_height'  => __('Chiều cao tản nhiệt', 'pcgear-store'),
		'length'         => __('Chiều dài', 'pcgear-store'),
		'type'           => __('Loại', 'pcgear-store'),
		'modular'        => __('Kiểu dây nguồn', 'pcgear-store'),
		'read_speed'     => __('Tốc độ đọc', 'pcgear-store'),
		'fans_included'  => __('Số quạt đi kèm', 'pcgear-store'),
		'radiator_size'  => __('Kích thước radiator', 'pcgear-store'),
	);

	return $labels[$spec_key] ?? ucwords(str_replace('_', ' ', $spec_key));
}

function pcgear_store_format_spec_value($spec) {
	if (! empty($spec['unit']) && '' !== (string) $spec['spec_value_numeric'] && null !== $spec['spec_value_numeric']) {
		$value = rtrim(rtrim((string) $spec['spec_value_numeric'], '0'), '.');
		return $value . $spec['unit'];
	}

	return (string) $spec['spec_value'];
}

function pcgear_store_get_product_gallery_ids($product) {
	if (! $product instanceof WC_Product) {
		return array();
	}

	$gallery_ids = array();
	$image_id    = (int) $product->get_image_id();

	if ($image_id > 0) {
		$gallery_ids[] = $image_id;
	}

	foreach ($product->get_gallery_image_ids() as $gallery_id) {
		$gallery_ids[] = (int) $gallery_id;
	}

	$gallery_ids = array_values(array_unique(array_filter($gallery_ids)));

	return $gallery_ids;
}

/* Dead code removed: pcgear_store_loop_edit_link was unreachable (had return at start). */

function pcgear_store_sanitize_checkbox($checked) {
	return (bool) $checked;
}

function pcgear_store_sanitize_positive_int($value) {
	return max(0, absint($value));
}

function pcgear_store_sanitize_select($value, $setting) {
	$control = $setting->manager->get_control($setting->id);
	$choices = $control ? $control->choices : array();

	return array_key_exists($value, $choices) ? $value : $setting->default;
}

function pcgear_store_add_text_control($wp_customize, $section, $id, $label, $default = '', $type = 'text') {
	$sanitize_callback = 'sanitize_text_field';

	if ('url' === $type) {
		$sanitize_callback = 'esc_url_raw';
	} elseif ('number' === $type) {
		$sanitize_callback = 'pcgear_store_sanitize_positive_int';
	}

	$wp_customize->add_setting(
		$id,
		array(
			'default'           => $default,
			'sanitize_callback' => $sanitize_callback,
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		$id,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => $type,
		)
	);
}

function pcgear_store_add_textarea_control($wp_customize, $section, $id, $label, $default = '') {
	$wp_customize->add_setting(
		$id,
		array(
			'default'           => $default,
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		$id,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'textarea',
		)
	);
}

function pcgear_store_customize_register($wp_customize) {
	$wp_customize->add_panel(
		'pcgear_store_panel',
		array(
			'title'       => __('PC Gear tùy biến', 'pcgear-store'),
			'description' => __('Các thiết lập demo trực tiếp cho giao diện, trang chủ và card sản phẩm.', 'pcgear-store'),
			'priority'    => 25,
		)
	);

	$wp_customize->add_section(
		'pcgear_store_brand',
		array(
			'title' => __('Logo và thương hiệu', 'pcgear-store'),
			'panel' => 'pcgear_store_panel',
		)
	);

	$wp_customize->add_setting(
		'pcgear_logo_image',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'pcgear_logo_image',
			array(
				'label'   => __('Ảnh logo', 'pcgear-store'),
				'section' => 'pcgear_store_brand',
			)
		)
	);
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_brand', 'pcgear_logo_mark', __('Chữ logo khi chưa có ảnh', 'pcgear-store'), 'CT');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_brand', 'pcgear_brand_title', __('Tên thương hiệu', 'pcgear-store'), '');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_brand', 'pcgear_brand_tagline', __('Mô tả ngắn', 'pcgear-store'), '');

	$wp_customize->add_section(
		'pcgear_store_header',
		array(
			'title' => __('Thanh trên cùng', 'pcgear-store'),
			'panel' => 'pcgear_store_panel',
		)
	);

	$wp_customize->add_setting(
		'pcgear_topbar_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'pcgear_store_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'pcgear_topbar_enabled',
		array(
			'label'   => __('Hiển thị thanh trên cùng', 'pcgear-store'),
			'section' => 'pcgear_store_header',
			'type'    => 'checkbox',
		)
	);

	pcgear_store_add_text_control($wp_customize, 'pcgear_store_header', 'pcgear_topbar_1_label', __('Nhãn link 1', 'pcgear-store'), 'PC Builder');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_header', 'pcgear_topbar_1_url', __('URL link 1', 'pcgear-store'), home_url('/build-pc/'), 'url');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_header', 'pcgear_topbar_2_label', __('Nhãn link 2', 'pcgear-store'), 'Hỗ trợ');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_header', 'pcgear_topbar_2_url', __('URL link 2', 'pcgear-store'), home_url('/my-account/'), 'url');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_header', 'pcgear_topbar_3_label', __('Nhãn link 3', 'pcgear-store'), 'Đăng nhập');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_header', 'pcgear_topbar_3_url', __('URL link 3', 'pcgear-store'), home_url('/my-account/'), 'url');

	$wp_customize->add_section(
		'pcgear_store_home',
		array(
			'title' => __('Trang chủ', 'pcgear-store'),
			'panel' => 'pcgear_store_panel',
		)
	);
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_kicker', __('Dòng nhỏ hero', 'pcgear-store'), '');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_title', __('Tiêu đề hero', 'pcgear-store'), '');
	pcgear_store_add_textarea_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_text', __('Mô tả hero', 'pcgear-store'), '');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_primary_label', __('Nút chính', 'pcgear-store'), 'Mua ngay');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_primary_url', __('URL nút chính', 'pcgear-store'), '', 'url');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_secondary_label', __('Nút phụ', 'pcgear-store'), 'Xem cửa hàng');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_hero_secondary_url', __('URL nút phụ', 'pcgear-store'), '', 'url');
	$wp_customize->add_setting(
		'pcgear_hero_image',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'pcgear_hero_image',
			array(
				'label'   => __('Ảnh hero', 'pcgear-store'),
				'section' => 'pcgear_store_home',
			)
		)
	);
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_featured_limit', __('Số sản phẩm nổi bật', 'pcgear-store'), '8', 'number');
	pcgear_store_add_text_control($wp_customize, 'pcgear_store_home', 'pcgear_latest_limit', __('Số sản phẩm mới', 'pcgear-store'), '4', 'number');

	$wp_customize->add_section(
		'pcgear_store_shop',
		array(
			'title' => __('Cửa hàng và sản phẩm', 'pcgear-store'),
			'panel' => 'pcgear_store_panel',
		)
	);

	$wp_customize->add_setting(
		'pcgear_shop_columns',
		array(
			'default'           => 4,
			'sanitize_callback' => 'pcgear_store_sanitize_positive_int',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'pcgear_shop_columns',
		array(
			'label'       => __('Số cột sản phẩm desktop', 'pcgear-store'),
			'section'     => 'pcgear_store_shop',
			'type'        => 'number',
			'input_attrs' => array(
				'min' => 2,
				'max' => 6,
			),
		)
	);

	$wp_customize->add_setting(
		'pcgear_shop_products_per_page',
		array(
			'default'           => 12,
			'sanitize_callback' => 'pcgear_store_sanitize_positive_int',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'pcgear_shop_products_per_page',
		array(
			'label'       => __('Số sản phẩm mỗi trang', 'pcgear-store'),
			'section'     => 'pcgear_store_shop',
			'type'        => 'number',
			'input_attrs' => array(
				'min' => 4,
				'max' => 48,
			),
		)
	);

	$wp_customize->add_setting(
		'pcgear_product_image_fit',
		array(
			'default'           => 'contain',
			'sanitize_callback' => 'pcgear_store_sanitize_select',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'pcgear_product_image_fit',
		array(
			'label'   => __('Kiểu hiển thị ảnh sản phẩm', 'pcgear-store'),
			'section' => 'pcgear_store_shop',
			'type'    => 'select',
			'choices' => array(
				'contain' => __('Không cắt ảnh', 'pcgear-store'),
				'cover'   => __('Phủ kín khung', 'pcgear-store'),
			),
		)
	);

	$wp_customize->add_setting(
		'pcgear_show_product_button',
		array(
			'default'           => true,
			'sanitize_callback' => 'pcgear_store_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'pcgear_show_product_button',
		array(
			'label'   => __('Hiển thị nút trên card sản phẩm', 'pcgear-store'),
			'section' => 'pcgear_store_shop',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_section(
		'pcgear_store_product_admin',
		array(
			'title'       => __('Quản trị sản phẩm', 'pcgear-store'),
			'description' => __('Dữ liệu sản phẩm không hiển thị nút sửa ngoài frontend public. Ảnh, gallery, giá, tồn kho và mô tả chỉnh trong WooCommerce > Sản phẩm.', 'pcgear-store'),
			'panel'       => 'pcgear_store_panel',
		)
	);
	pcgear_store_add_textarea_control(
		$wp_customize,
		'pcgear_store_product_admin',
		'pcgear_product_edit_hint',
		__('Ghi chú demo cho phần sản phẩm', 'pcgear-store'),
		__('Demo: vào Sản phẩm > Tất cả sản phẩm, chọn sản phẩm, đổi ảnh đại diện, thư viện ảnh, giá VND, mô tả ngắn, danh mục rồi cập nhật.', 'pcgear-store')
	);

	$wp_customize->add_section(
		'pcgear_store_colors',
		array(
			'title' => __('Màu giao diện', 'pcgear-store'),
			'panel' => 'pcgear_store_panel',
		)
	);

	$wp_customize->add_setting(
		'pcgear_accent_color',
		array(
			'default'           => '#f7b500',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'pcgear_accent_color',
			array(
				'label'   => __('Màu nhấn', 'pcgear-store'),
				'section' => 'pcgear_store_colors',
			)
		)
	);

	$wp_customize->add_setting(
		'pcgear_accent_strong_color',
		array(
			'default'           => '#ffcb39',
			'sanitize_callback' => 'sanitize_hex_color',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'pcgear_accent_strong_color',
			array(
				'label'   => __('Màu nhấn đậm', 'pcgear-store'),
				'section' => 'pcgear_store_colors',
			)
		)
	);

	$wp_customize->add_section(
		'pcgear_store_footer',
		array(
			'title' => __('Footer', 'pcgear-store'),
			'panel' => 'pcgear_store_panel',
		)
	);
	pcgear_store_add_textarea_control($wp_customize, 'pcgear_store_footer', 'pcgear_footer_text', __('Nội dung giới thiệu footer', 'pcgear-store'), '');
}
add_action('customize_register', 'pcgear_store_customize_register');
