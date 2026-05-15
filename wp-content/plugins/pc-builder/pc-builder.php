<?php
/**
 * Plugin Name: PC Builder
 * Plugin URI: https://github.com/chithanh-pc/pc-builder
 * Description: Plugin tùy biến dựng cấu hình PC, kiểm tra tương thích linh kiện và tích hợp WooCommerce cart.
 * Version: 1.0.0
 * Author: Chí Thành
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: pc-builder
 */

if (! defined('ABSPATH')) {
	exit;
}

define('PC_BUILDER_VERSION', '1.0.0');
define('PC_BUILDER_FILE', __FILE__);
define('PC_BUILDER_PATH', plugin_dir_path(__FILE__));
define('PC_BUILDER_URL', plugin_dir_url(__FILE__));

require_once PC_BUILDER_PATH . 'includes/class-pc-builder-schema.php';
require_once PC_BUILDER_PATH . 'includes/class-pc-builder-admin.php';
require_once PC_BUILDER_PATH . 'includes/class-pc-builder-frontend.php';
require_once PC_BUILDER_PATH . 'includes/class-pc-builder-plugin.php';

register_activation_hook(PC_BUILDER_FILE, array('PC_Builder_Schema', 'activate'));

function pc_builder_bootstrap() {
	$plugin = new PC_Builder_Plugin();
	$plugin->run();
}

pc_builder_bootstrap();
