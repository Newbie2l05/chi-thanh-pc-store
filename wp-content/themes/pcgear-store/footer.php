<?php
$brand_title = pcgear_store_theme_mod('brand_title', '');
$footer_text = pcgear_store_theme_mod('footer_text', '');
$shop_url    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$builder_url = pcgear_store_page_url('build-pc', home_url('/build-pc/'));

if ('' === $brand_title) {
	$brand_title = get_bloginfo('name');
}

if ('' === $footer_text) {
	$footer_text = __('Website thương mại điện tử bán linh kiện PC và gaming gear, xây dựng bằng WordPress + WooCommerce + plugin PC Builder tùy biến.', 'pcgear-store');
}
?>
</main>
<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__brand">
			<h3><?php echo esc_html($brand_title); ?></h3>
			<p><?php echo esc_html($footer_text); ?></p>
		</div>
		<div class="site-footer__col">
			<h4><?php echo esc_html__('Mua sắm', 'pcgear-store'); ?></h4>
			<ul>
				<li><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html__('Trang chủ', 'pcgear-store'); ?></a></li>
				<li><a href="<?php echo esc_url($shop_url); ?>"><?php echo esc_html__('Cửa hàng', 'pcgear-store'); ?></a></li>
				<li><a href="<?php echo esc_url($builder_url); ?>"><?php echo esc_html__('Xây dựng PC', 'pcgear-store'); ?></a></li>
			</ul>
		</div>
		<div class="site-footer__col">
			<h4><?php echo esc_html__('Hệ thống', 'pcgear-store'); ?></h4>
			<ul>
				<li><a href="<?php echo esc_url($cart_url); ?>"><?php echo esc_html__('Giỏ hàng', 'pcgear-store'); ?></a></li>
				<li><a href="<?php echo esc_url($account_url); ?>"><?php echo esc_html__('Tài khoản', 'pcgear-store'); ?></a></li>
			</ul>
		</div>
		<div class="site-footer__col">
			<h4><?php echo esc_html__('Công nghệ', 'pcgear-store'); ?></h4>
			<ul>
				<li><span>WordPress + WooCommerce</span></li>
				<li><span>Custom Theme & Plugin</span></li>
				<li><span>MySQL Database</span></li>
			</ul>
		</div>
	</div>
	<div class="site-footer__bottom">
		<p>&copy; <?php echo esc_html(gmdate('Y')); ?> <?php echo esc_html($brand_title); ?>. <?php echo esc_html__('Đề tài BCCĐ — Phần mềm mã nguồn mở.', 'pcgear-store'); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
