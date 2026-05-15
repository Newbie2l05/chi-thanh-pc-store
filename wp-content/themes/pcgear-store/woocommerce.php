<?php
get_header();
?>
<section class="shop-shell">
	<?php if (! function_exists('is_product') || ! is_product()) : ?>
		<div class="shop-shell__header">
			<?php if (function_exists('woocommerce_breadcrumb')) : ?>
				<?php woocommerce_breadcrumb(); ?>
			<?php endif; ?>
			<h1><?php woocommerce_page_title(); ?></h1>
		</div>
	<?php endif; ?>
	<div class="shop-shell__body">
		<?php woocommerce_content(); ?>
	</div>
</section>
<?php
get_footer();
