<?php
if (! defined('ABSPATH')) {
	exit;
}

$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$support_url = function_exists('pcgear_store_support_page_url') ? pcgear_store_support_page_url() : home_url('/cau-hoi-thuong-gap/');
$nav_items   = function_exists('pcgear_store_public_navigation') ? pcgear_store_public_navigation() : pcgear_store_header_navigation();
$logo_image  = pcgear_store_theme_mod('logo_image', '');
$logo_mark   = pcgear_store_theme_mod('logo_mark', 'CT');
$brand_title = pcgear_store_theme_mod('brand_title', '');
$brand_text  = pcgear_store_theme_mod('brand_tagline', '');

$saved_support_url = pcgear_store_theme_mod('topbar_2_url', '');
if ('' === $saved_support_url || untrailingslashit($saved_support_url) === untrailingslashit($account_url)) {
	$saved_support_url = $support_url;
}

$topbar_links = array(
	array(
		'label' => pcgear_store_theme_mod('topbar_1_label', 'PC Builder'),
		'url'   => pcgear_store_theme_mod('topbar_1_url', home_url('/build-pc/')),
	),
	array(
		'label' => pcgear_store_theme_mod('topbar_2_label', 'Hỗ trợ'),
		'url'   => $saved_support_url,
	),
	array(
		'label' => pcgear_store_theme_mod('topbar_3_label', 'Đăng nhập'),
		'url'   => pcgear_store_theme_mod('topbar_3_url', $account_url),
	),
);

if ('' === $brand_title) {
	$brand_title = get_bloginfo('name');
}

if ('' === $brand_text) {
	$brand_text = get_bloginfo('description');
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
	<?php if (pcgear_store_theme_mod('topbar_enabled', true)) : ?>
		<div class="site-topbar">
			<div class="site-topbar__inner">
				<?php foreach ($topbar_links as $topbar_link) : ?>
					<?php if (! empty($topbar_link['label']) && ! empty($topbar_link['url'])) : ?>
						<a href="<?php echo esc_url($topbar_link['url']); ?>"><?php echo esc_html($topbar_link['label']); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="site-header__inner">
		<a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>">
			<span class="site-brand__mark">
				<?php if ($logo_image) : ?>
					<img src="<?php echo esc_url($logo_image); ?>" alt="<?php echo esc_attr($brand_title); ?>">
				<?php else : ?>
					<?php echo esc_html($logo_mark); ?>
				<?php endif; ?>
			</span>
			<span class="site-brand__text">
				<strong><?php echo esc_html($brand_title); ?></strong>
				<small><?php echo esc_html($brand_text); ?></small>
			</span>
		</a>

		<button class="site-menu-toggle" type="button" aria-expanded="false" aria-controls="site-header-panel">
			<span class="screen-reader-text"><?php echo esc_html__('Mở menu điều hướng', 'pcgear-store'); ?></span>
			<span class="site-menu-toggle__line"></span>
			<span class="site-menu-toggle__line"></span>
			<span class="site-menu-toggle__line"></span>
		</button>

		<div class="site-header__panel" id="site-header-panel">
			<nav class="site-nav" aria-label="<?php echo esc_attr__('Điều hướng chính', 'pcgear-store'); ?>">
				<ul class="site-nav__menu">
					<?php foreach ($nav_items as $item) : ?>
						<li class="site-nav__item site-nav__item--mega">
							<a class="site-nav__link" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
							<div class="mega-menu">
								<div class="mega-menu__inner">
									<?php foreach ($item['groups'] as $group) : ?>
										<?php if ('card' === $group['type']) : ?>
											<div class="mega-menu__card">
												<h3><?php echo esc_html($group['title']); ?></h3>
												<p><?php echo esc_html($group['text']); ?></p>
												<a href="<?php echo esc_url($group['link']); ?>"><?php echo esc_html($group['cta']); ?></a>
											</div>
										<?php elseif ('feature' === $group['type']) : ?>
											<div class="mega-menu__feature">
												<h3><?php echo esc_html($group['title']); ?></h3>
												<p><?php echo esc_html($group['text']); ?></p>
												<a href="<?php echo esc_url($group['link']); ?>"><?php echo esc_html($group['cta']); ?></a>
											</div>
										<?php else : ?>
											<div class="mega-menu__column">
												<h3><?php echo esc_html($group['title']); ?></h3>
												<ul>
													<?php foreach ($group['links'] as $link) : ?>
														<li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
													<?php endforeach; ?>
												</ul>
											</div>
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="site-actions">
				<button class="site-search-toggle" type="button" aria-expanded="false" aria-controls="site-search-panel">
					<span class="screen-reader-text"><?php echo esc_html__('Mở tìm kiếm', 'pcgear-store'); ?></span>
					<span class="site-search-toggle__icon"></span>
				</button>
				<a class="site-cart" href="<?php echo esc_url($cart_url); ?>">
					<span><?php echo esc_html__('Giỏ hàng', 'pcgear-store'); ?></span>
					<strong><?php echo esc_html((string) pcgear_store_cart_count()); ?></strong>
				</a>
			</div>
		</div>
	</div>

	<div class="site-search-panel" id="site-search-panel" hidden>
		<div class="site-search-panel__inner">
			<?php if (function_exists('get_product_search_form')) : ?>
				<div class="site-search"><?php get_product_search_form(); ?></div>
			<?php endif; ?>
		</div>
	</div>
</header>
<main class="site-main">
