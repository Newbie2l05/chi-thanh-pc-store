<?php

if (! defined('ABSPATH')) {
	exit;
}

class PC_Builder_Admin {
	public function hooks() {
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('admin_notices', array($this, 'maybe_show_woocommerce_notice'));
		add_action('admin_notices', array($this, 'maybe_show_action_notice'));
		add_action('admin_init', array($this, 'handle_admin_actions'));
		add_action('add_meta_boxes', array($this, 'register_product_meta_box'));
		add_action('save_post_product', array($this, 'save_product_specs_meta_box'));
	}

	public function register_menu() {
		add_menu_page(
			'PC Builder',
			'PC Builder',
			'manage_options',
			'pc-builder',
			array($this, 'render_dashboard_page'),
			'dashicons-desktop',
			56
		);

		add_submenu_page(
			'pc-builder',
			'Loại linh kiện',
			'Loại linh kiện',
			'manage_options',
			'pc-builder-component-types',
			array($this, 'render_component_types_page')
		);

		add_submenu_page(
			'pc-builder',
			'Thông số sản phẩm',
			'Thông số sản phẩm',
			'manage_options',
			'pc-builder-product-specs',
			array($this, 'render_product_specs_page')
		);


	}

	public function maybe_show_woocommerce_notice() {
		if (! current_user_can('manage_options')) {
			return;
		}

		if (class_exists('WooCommerce')) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__('PC Builder có thể chạy không cần WooCommerce khi phát triển, nhưng tích hợp sản phẩm, giỏ hàng và đơn hàng yêu cầu WooCommerce.', 'pc-builder');
		echo '</p></div>';
	}

	public function maybe_show_action_notice() {
		if (! current_user_can('manage_options')) {
			return;
		}

		$notice = isset($_GET['pc_builder_notice']) ? sanitize_key(wp_unslash($_GET['pc_builder_notice'])) : '';

		if (! $notice) {
			return;
		}

		$messages = array(
			'component_saved' => array('type' => 'success', 'text' => __('Đã lưu loại linh kiện.', 'pc-builder')),
			'component_deleted' => array('type' => 'success', 'text' => __('Đã xóa loại linh kiện.', 'pc-builder')),

			'specs_saved' => array('type' => 'success', 'text' => __('Đã lưu thông số sản phẩm.', 'pc-builder')),
		);

		if (! isset($messages[$notice])) {
			return;
		}

		$message = $messages[$notice];

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr($message['type']),
			esc_html($message['text'])
		);
	}

	public function render_dashboard_page() {
		$stats = $this->get_dashboard_stats();
		
		$total_customers = $stats['customers'];
		$total_revenue   = $stats['total_revenue'];
		$total_orders    = $stats['pc_sold'];
		?>
		<div class="wrap">
			<h1 style="margin-bottom: 5px;"><?php echo esc_html__('Bảng điều khiển Tổng Quan TTShopGear', 'pc-builder'); ?></h1>
			<p style="color: #666; margin-top: 0; font-size: 14px;"><?php echo esc_html__('Thống kê hiệu suất bán hàng và hoạt động của hệ thống PC Builder.', 'pc-builder'); ?></p>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-top: 20px;">
				<!-- Card 1 -->
				<div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #0073aa;">
					<h3 style="margin: 0 0 10px; color: #555; font-size: 14px; font-weight: 600;">Tổng khách hàng</h3>
					<div style="font-size: 32px; font-weight: 800; color: #111;"><?php echo number_format($total_customers); ?> <span style="font-size: 14px; color: #46b450; font-weight: 600;"></span></div>
				</div>

				<!-- Card 2 -->
				<div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #46b450;">
					<h3 style="margin: 0 0 10px; color: #555; font-size: 14px; font-weight: 600;">Doanh thu bán hàng</h3>
					<div style="font-size: 32px; font-weight: 800; color: #111;"><?php echo number_format($total_revenue, 0, ',', '.'); ?>đ</div>
				</div>

				<!-- Card 3 -->
				<div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #ffba00;">
					<h3 style="margin: 0 0 10px; color: #555; font-size: 14px; font-weight: 600;">Đơn hàng thành công</h3>
					<div style="font-size: 32px; font-weight: 800; color: #111;"><?php echo $total_orders; ?> đơn</div>
				</div>

				<!-- Card 4 -->
				<div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #d63638;">
					<h3 style="margin: 0 0 10px; color: #555; font-size: 14px; font-weight: 600;">Cấu hình PC đã lưu</h3>
					<div style="font-size: 32px; font-weight: 800; color: #111;"><?php echo esc_html($stats['builds']); ?> cấu hình</div>
				</div>
			</div>

			<div style="margin-top: 30px; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
				<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Dữ liệu kho linh kiện PC Builder</h2>
				<table class="widefat striped" style="border: none; margin-top: 15px;">
					<tbody>
						<tr>
							<th style="width: 250px; font-size: 14px;"><?php echo esc_html__('Tổng số loại linh kiện', 'pc-builder'); ?></th>
							<td style="font-size: 14px;"><strong style="color:#0073aa; font-size: 16px;"><?php echo esc_html($stats['component_types']); ?></strong> danh mục (CPU, RAM, VGA, Nguồn...)</td>
						</tr>
						<tr>
							<th style="font-size: 14px;"><?php echo esc_html__('Linh kiện đã cập nhật thông số', 'pc-builder'); ?></th>
							<td style="font-size: 14px;"><strong style="color:#0073aa; font-size: 16px;"><?php echo esc_html($stats['specs']); ?></strong> sản phẩm sẵn sàng ráp</td>
						</tr>
						<tr>
							<th style="font-size: 14px;"><?php echo esc_html__('Trạng thái kết nối WooCommerce', 'pc-builder'); ?></th>
							<td style="font-size: 14px;"><span style="display: inline-block; padding: 3px 10px; background: #e7f5ea; color: #1e8c36; border-radius: 20px; font-weight: 600; font-size: 12px;">● Đang hoạt động</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	public function render_component_types_page() {
		$current_item = $this->get_component_type_for_edit();
		$rows         = $this->get_table_rows('pc_component_types', 100);
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Loại linh kiện', 'pc-builder'); ?></h1>
			<p><?php echo esc_html__('Quản lý các nhóm linh kiện sử dụng trong luồng PC Builder.', 'pc-builder'); ?></p>

			<form method="post" style="max-width: 720px; margin: 20px 0;">
				<?php wp_nonce_field('pc_builder_save_component_type'); ?>
				<input type="hidden" name="pc_builder_action" value="save_component_type">
				<input type="hidden" name="id" value="<?php echo esc_attr((string) ($current_item['id'] ?? 0)); ?>">

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="pc-builder-component-code"><?php echo esc_html__('Mã code', 'pc-builder'); ?></label></th>
							<td><input name="code" type="text" id="pc-builder-component-code" value="<?php echo esc_attr($current_item['code'] ?? ''); ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-component-name"><?php echo esc_html__('Tên hiển thị', 'pc-builder'); ?></label></th>
							<td><input name="name" type="text" id="pc-builder-component-name" value="<?php echo esc_attr($current_item['name'] ?? ''); ?>" class="regular-text" required></td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-component-sort"><?php echo esc_html__('Thứ tự', 'pc-builder'); ?></label></th>
							<td><input name="sort_order" type="number" id="pc-builder-component-sort" value="<?php echo esc_attr((string) ($current_item['sort_order'] ?? 0)); ?>" class="small-text"></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__('Bắt buộc', 'pc-builder'); ?></th>
							<td>
								<label>
									<input name="is_required" type="checkbox" value="1" <?php checked(! empty($current_item['is_required']), true); ?>>
									<?php echo esc_html__('Bắt buộc trong một cấu hình tiêu chuẩn.', 'pc-builder'); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(isset($current_item['id']) ? __('Cập nhật loại linh kiện', 'pc-builder') : __('Thêm loại linh kiện', 'pc-builder')); ?>
			</form>

			<?php $this->render_component_types_table($rows); ?>
		</div>
		<?php
	}

	public function render_product_specs_page() {
		$rows = $this->get_product_specs_overview();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Thông số sản phẩm', 'pc-builder'); ?></h1>
			<p><?php echo esc_html__('Chỉnh sửa thông số trực tiếp bên trong từng sản phẩm WooCommerce qua hộp meta PC Builder.', 'pc-builder'); ?></p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php echo esc_html__('Sản phẩm', 'pc-builder'); ?></th>
						<th><?php echo esc_html__('Loại linh kiện', 'pc-builder'); ?></th>
						<th><?php echo esc_html__('Số thông số', 'pc-builder'); ?></th>
						<th><?php echo esc_html__('Thao tác', 'pc-builder'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($rows)) : ?>
						<tr>
							<td colspan="4"><?php echo esc_html__('Chưa có thông số sản phẩm nào.', 'pc-builder'); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ($rows as $row) : ?>
							<tr>
								<td><?php echo esc_html($row['product_title']); ?></td>
								<td><?php echo esc_html($row['component_type_name']); ?></td>
								<td><?php echo esc_html((string) $row['spec_count']); ?></td>
								<td>
									<a href="<?php echo esc_url(get_edit_post_link((int) $row['product_id'])); ?>">
										<?php echo esc_html__('Sửa sản phẩm', 'pc-builder'); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}


	private function get_dashboard_stats() {
		global $wpdb;

		$total_revenue = 0;
		$pc_sold = 0;
		$customers = 0;

		// WooCommerce order and customer stats
		if ( class_exists( 'WooCommerce' ) ) {
			$order_stats = $wpdb->get_row( "SELECT COUNT(order_id) as total_orders, SUM(total_sales) as total_revenue FROM {$wpdb->prefix}wc_order_stats WHERE status IN ('wc-completed', 'wc-processing')" );
			if ($order_stats) {
				$total_revenue = (float) $order_stats->total_revenue;
				$pc_sold = (int) $order_stats->total_orders;
			}
			$customers = (int) $wpdb->get_var( "SELECT COUNT(customer_id) FROM {$wpdb->prefix}wc_customer_lookup" );
		}

		return array(
			'component_types' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_component_types"),
			'builds'          => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_builds"),
			'specs'           => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_product_specs"),
			'total_revenue'   => $total_revenue,
			'pc_sold'         => $pc_sold,
			'customers'       => $customers,
		);
	}

	private function get_table_rows($table_suffix, $limit = 20) {
		global $wpdb;

		$table_name = $wpdb->prefix . $table_suffix;
		$rows       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array($rows) ? $rows : array();
	}

	public function handle_admin_actions() {
		if (! is_admin() || ! current_user_can('manage_options')) {
			return;
		}

		$action = isset($_REQUEST['pc_builder_action']) ? sanitize_key(wp_unslash($_REQUEST['pc_builder_action'])) : '';

		switch ($action) {
			case 'save_component_type':
				$this->save_component_type();
				break;
			case 'delete_component_type':
				$this->delete_component_type();
				break;

		}
	}

	public function register_product_meta_box() {
		if (! post_type_exists('product')) {
			return;
		}

		add_meta_box(
			'pc-builder-product-specs',
			__('Thông số PC Builder', 'pc-builder'),
			array($this, 'render_product_specs_meta_box'),
			'product',
			'normal',
			'default'
		);
	}

	public function render_product_specs_meta_box($post) {
		$component_types = $this->get_component_types_options();
		$saved_specs     = $this->get_product_specs((int) $post->ID);
		$component_type  = 0;

		if (! empty($saved_specs[0]['component_type_id'])) {
			$component_type = (int) $saved_specs[0]['component_type_id'];
		}

		if (empty($saved_specs)) {
			$saved_specs = array_fill(
				0,
				5,
				array(
					'spec_key'           => '',
					'spec_value'         => '',
					'spec_value_numeric' => '',
					'unit'               => '',
				)
			);
		}

		wp_nonce_field('pc_builder_save_product_specs', 'pc_builder_product_specs_nonce');
		?>
		<p><?php echo esc_html__('Thêm thông số chuẩn hóa để kiểm tra tương thích. Ví dụ: socket, ram_type, gpu_max_length.', 'pc-builder'); ?></p>
		<p>
			<label for="pc-builder-product-component-type"><strong><?php echo esc_html__('Loại linh kiện', 'pc-builder'); ?></strong></label><br>
			<?php $this->render_component_type_select('pc_builder_component_type_id', $component_types, $component_type, 'pc-builder-product-component-type'); ?>
		</p>

		<table class="widefat striped" id="pc-builder-spec-table">
			<thead>
				<tr>
					<th><?php echo esc_html__('Khóa thông số', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Giá trị', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Giá trị số', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Đơn vị', 'pc-builder'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($saved_specs as $index => $spec) : ?>
					<tr>
						<td><input type="text" name="pc_builder_specs[<?php echo esc_attr((string) $index); ?>][spec_key]" value="<?php echo esc_attr($spec['spec_key']); ?>" class="regular-text"></td>
						<td><input type="text" name="pc_builder_specs[<?php echo esc_attr((string) $index); ?>][spec_value]" value="<?php echo esc_attr($spec['spec_value']); ?>" class="regular-text"></td>
						<td><input type="number" step="0.01" name="pc_builder_specs[<?php echo esc_attr((string) $index); ?>][spec_value_numeric]" value="<?php echo esc_attr((string) $spec['spec_value_numeric']); ?>" class="small-text"></td>
						<td><input type="text" name="pc_builder_specs[<?php echo esc_attr((string) $index); ?>][unit]" value="<?php echo esc_attr($spec['unit']); ?>" class="small-text"></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<button type="button" class="button" id="pc-builder-add-spec-row"><?php echo esc_html__('Thêm dòng thông số', 'pc-builder'); ?></button>
		</p>

		<script>
			(function() {
				const button = document.getElementById('pc-builder-add-spec-row');
				const tableBody = document.querySelector('#pc-builder-spec-table tbody');

				if (!button || !tableBody) {
					return;
				}

				button.addEventListener('click', function() {
					const index = tableBody.querySelectorAll('tr').length;
					const row = document.createElement('tr');

					row.innerHTML = `
						<td><input type="text" name="pc_builder_specs[${index}][spec_key]" class="regular-text"></td>
						<td><input type="text" name="pc_builder_specs[${index}][spec_value]" class="regular-text"></td>
						<td><input type="number" step="0.01" name="pc_builder_specs[${index}][spec_value_numeric]" class="small-text"></td>
						<td><input type="text" name="pc_builder_specs[${index}][unit]" class="small-text"></td>
					`;

					tableBody.appendChild(row);
				});
			})();
		</script>
		<?php
	}

	public function save_product_specs_meta_box($post_id) {
		if (! isset($_POST['pc_builder_product_specs_nonce'])) {
			return;
		}

		if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pc_builder_product_specs_nonce'])), 'pc_builder_save_product_specs')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (! current_user_can('edit_post', $post_id)) {
			return;
		}

		global $wpdb;

		$table_name        = $wpdb->prefix . 'pc_product_specs';
		$component_type_id = isset($_POST['pc_builder_component_type_id']) ? absint(wp_unslash($_POST['pc_builder_component_type_id'])) : 0;
		$spec_rows         = isset($_POST['pc_builder_specs']) && is_array($_POST['pc_builder_specs']) ? wp_unslash($_POST['pc_builder_specs']) : array();
		$normalized_specs  = array();

		foreach ($spec_rows as $spec_row) {
			$spec_key = isset($spec_row['spec_key']) ? sanitize_key($spec_row['spec_key']) : '';
			$spec_value = isset($spec_row['spec_value']) ? sanitize_text_field($spec_row['spec_value']) : '';
			$numeric_value_raw = isset($spec_row['spec_value_numeric']) ? sanitize_text_field($spec_row['spec_value_numeric']) : '';
			$unit = isset($spec_row['unit']) ? sanitize_text_field($spec_row['unit']) : '';

			if (! $spec_key || ! $spec_value) {
				continue;
			}

			$normalized_specs[$spec_key] = array(
				'spec_key'           => $spec_key,
				'spec_value'         => $spec_value,
				'spec_value_numeric' => is_numeric($numeric_value_raw) ? (float) $numeric_value_raw : null,
				'unit'               => $unit,
			);
		}

		$wpdb->delete($table_name, array('product_id' => $post_id), array('%d'));

		if ($component_type_id > 0) {
			foreach ($normalized_specs as $spec) {
				$wpdb->insert(
					$table_name,
					array(
						'product_id'          => $post_id,
						'component_type_id'   => $component_type_id,
						'spec_key'            => $spec['spec_key'],
						'spec_value'          => $spec['spec_value'],
						'spec_value_numeric'  => $spec['spec_value_numeric'],
						'unit'                => $spec['unit'],
					),
					array('%d', '%d', '%s', '%s', '%f', '%s')
				);
			}
		}
	}

	private function save_component_type() {
		check_admin_referer('pc_builder_save_component_type');

		global $wpdb;

		$table_name = $wpdb->prefix . 'pc_component_types';
		$id         = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
		$data       = array(
			'code'        => sanitize_key(wp_unslash($_POST['code'] ?? '')),
			'name'        => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
			'sort_order'  => isset($_POST['sort_order']) ? intval(wp_unslash($_POST['sort_order'])) : 0,
			'is_required' => isset($_POST['is_required']) ? 1 : 0,
		);

		if ($id > 0) {
			$wpdb->update($table_name, $data, array('id' => $id), array('%s', '%s', '%d', '%d'), array('%d'));
		} else {
			$wpdb->insert($table_name, $data, array('%s', '%s', '%d', '%d'));
		}

		$this->redirect_with_notice('admin.php?page=pc-builder-component-types', 'component_saved');
	}

	private function delete_component_type() {
		$id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

		check_admin_referer('pc_builder_delete_component_type_' . $id);

		if ($id <= 0) {
			return;
		}

		global $wpdb;

		$wpdb->delete($wpdb->prefix . 'pc_component_types', array('id' => $id), array('%d'));

		$this->redirect_with_notice('admin.php?page=pc-builder-component-types', 'component_deleted');
	}



	private function render_component_types_table($rows) {
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php echo esc_html__('Mã code', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Tên', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Thứ tự', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Bắt buộc', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Thao tác', 'pc-builder'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($rows)) : ?>
					<tr>
						<td colspan="5"><?php echo esc_html__('No component types found.', 'pc-builder'); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ($rows as $row) : ?>
						<tr>
							<td><?php echo esc_html($row['code']); ?></td>
							<td><?php echo esc_html($row['name']); ?></td>
							<td><?php echo esc_html((string) $row['sort_order']); ?></td>
							<td><?php echo esc_html(! empty($row['is_required']) ? __('Có', 'pc-builder') : __('Không', 'pc-builder')); ?></td>
							<td>
								<a href="<?php echo esc_url(admin_url('admin.php?page=pc-builder-component-types&action=edit&id=' . (int) $row['id'])); ?>"><?php echo esc_html__('Sửa', 'pc-builder'); ?></a>
								|
								<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pc-builder-component-types&pc_builder_action=delete_component_type&id=' . (int) $row['id']), 'pc_builder_delete_component_type_' . (int) $row['id'])); ?>" onclick="return confirm('<?php echo esc_js(__('Xóa loại linh kiện này?', 'pc-builder')); ?>');"><?php echo esc_html__('Xóa', 'pc-builder'); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}



	private function get_component_types_options() {
		global $wpdb;

		$rows = $wpdb->get_results("SELECT id, code, name FROM {$wpdb->prefix}pc_component_types ORDER BY sort_order ASC, name ASC", ARRAY_A);

		return is_array($rows) ? $rows : array();
	}

	private function render_component_type_select($name, $options, $selected_value = 0, $id = '') {
		?>
		<select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>">
			<option value="0"><?php echo esc_html__('Chọn loại linh kiện', 'pc-builder'); ?></option>
			<?php foreach ($options as $option) : ?>
				<option value="<?php echo esc_attr((string) $option['id']); ?>" <?php selected((int) $selected_value, (int) $option['id']); ?>>
					<?php echo esc_html($option['name'] . ' (' . $option['code'] . ')'); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function get_component_type_for_edit() {
		global $wpdb;

		$action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
		$id     = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

		if ('edit' !== $action || $id <= 0) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pc_component_types WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		return is_array($row) ? $row : array();
	}



	private function get_product_specs($product_id) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT component_type_id, spec_key, spec_value, spec_value_numeric, unit
				FROM {$wpdb->prefix}pc_product_specs
				WHERE product_id = %d
				ORDER BY id ASC",
				$product_id
			),
			ARRAY_A
		);

		return is_array($rows) ? $rows : array();
	}

	private function get_product_specs_overview() {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT specs.product_id,
				posts.post_title AS product_title,
				component_types.name AS component_type_name,
				COUNT(specs.id) AS spec_count
			FROM {$wpdb->prefix}pc_product_specs specs
			LEFT JOIN {$wpdb->posts} posts ON posts.ID = specs.product_id
			LEFT JOIN {$wpdb->prefix}pc_component_types component_types ON component_types.id = specs.component_type_id
			GROUP BY specs.product_id, posts.post_title, component_types.name
			ORDER BY specs.product_id DESC",
			ARRAY_A
		);

		return is_array($rows) ? $rows : array();
	}

	private function redirect_with_notice($path, $notice) {
		wp_safe_redirect(
			add_query_arg(
				'pc_builder_notice',
				$notice,
				admin_url($path)
			)
		);
		exit;
	}
}
