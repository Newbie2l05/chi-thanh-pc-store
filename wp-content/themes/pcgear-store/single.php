<?php
get_header();
?>
<section class="content-shell">
	<?php while (have_posts()) : the_post(); ?>
		<article <?php post_class('content-card'); ?>>
			<header class="content-card__header">
				<h1><?php the_title(); ?></h1>
				<div class="post-item__meta"><?php echo esc_html(get_the_date()); ?></div>
			</header>
			<div class="content-card__body">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</section>
<?php
get_footer();
