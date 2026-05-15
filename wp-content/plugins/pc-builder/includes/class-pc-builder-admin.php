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

		add_submenu_page(
			'pc-builder',
			'Luật tương thích',
			'Luật tương thích',
			'manage_options',
			'pc-builder-compatibility-rules',
			array($this, 'render_compatibility_rules_page')
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
			'rule_saved' => array('type' => 'success', 'text' => __('Đã lưu luật tương thích.', 'pc-builder')),
			'rule_deleted' => array('type' => 'success', 'text' => __('Đã xóa luật tương thích.', 'pc-builder')),
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
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Bảng điều khiển PC Builder', 'pc-builder'); ?></h1>
			<p><?php echo esc_html__('Plugin tùy biến dựng cấu hình PC, kiểm tra tương thích linh kiện và tích hợp giỏ hàng WooCommerce.', 'pc-builder'); ?></p>

			<table class="widefat striped" style="max-width: 720px;">
				<tbody>
					<tr>
						<th><?php echo esc_html__('Loại linh kiện', 'pc-builder'); ?></th>
						<td><?php echo esc_html($stats['component_types']); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__('Luật tương thích', 'pc-builder'); ?></th>
						<td><?php echo esc_html($stats['rules']); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__('Cấu hình đã lưu', 'pc-builder'); ?></th>
						<td><?php echo esc_html($stats['builds']); ?></td>
					</tr>
					<tr>
						<th><?php echo esc_html__('Thông số sản phẩm', 'pc-builder'); ?></th>
						<td><?php echo esc_html($stats['specs']); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php echo esc_html__('Các bước phát triển tiếp theo', 'pc-builder'); ?></h2>
			<ol>
				<li><?php echo esc_html__('Thêm giao diện CRUD cho loại linh kiện, thông số và luật tương thích.', 'pc-builder'); ?></li>
				<li><?php echo esc_html__('Thêm hộp meta thông số kỹ thuật ở từng sản phẩm.', 'pc-builder'); ?></li>
				<li><?php echo esc_html__('Xây dựng trang PC Builder phía frontend và luồng kiểm tra.', 'pc-builder'); ?></li>
				<li><?php echo esc_html__('Tích hợp cấu hình đã chọn vào giỏ hàng WooCommerce.', 'pc-builder'); ?></li>
			</ol>
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

	public function render_compatibility_rules_page() {
		$current_item    = $this->get_compatibility_rule_for_edit();
		$rows            = $this->get_compatibility_rule_rows();
		$component_types = $this->get_component_types_options();
		$operators       = array(
			'eq'       => '= (Bằng nhau)',
			'neq'      => '!= (Khác nhau)',
			'gte'      => '>= (Lớn hơn hoặc bằng)',
			'lte'      => '<= (Nhỏ hơn hoặc bằng)',
			'contains' => 'Chứa (vd: DDR5)',
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Luật tương thích (Logic dựng PC)', 'pc-builder'); ?></h1>
			<div class="notice notice-info" style="padding:15px; margin-top:15px; border-left-color: #2271b1;">
				<p style="margin:0 0 10px; font-size:14px;"><strong>💡 Giải thích cách hoạt động (Dành cho báo cáo):</strong></p>
				<p style="margin:0 0 5px;">Hệ thống dùng tính năng này để ngăn khách hàng chọn sai linh kiện. Thay vì code cứng (hardcode), plugin cho phép Admin tự định nghĩa luật trong database.</p>
				<ul style="list-style:disc; padding-left:20px; margin:0;">
					<li><strong>Ví dụ 1 (Socket CPU):</strong> CPU và Mainboard phải khớp nhau. Cài đặt: Nguồn = <code>CPU</code>, Đích = <code>Mainboard</code>, Khóa thông số = <code>socket</code>, Toán tử = <code>= (Bằng nhau)</code>.</li>
					<li><strong>Ví dụ 2 (Kích thước Card):</strong> VGA phải lắp vừa Case. Cài đặt: Nguồn = <code>VGA</code> (khóa: <code>vga_length</code>), Đích = <code>Case</code> (khóa: <code>max_vga_length</code>), Toán tử = <code><= (Nhỏ hơn hoặc bằng)</code>.</li>
				</ul>
			</div>
			<form method="post" style="max-width: 860px; margin: 20px 0;">
				<?php wp_nonce_field('pc_builder_save_compatibility_rule'); ?>
				<input type="hidden" name="pc_builder_action" value="save_compatibility_rule">
				<input type="hidden" name="id" value="<?php echo esc_attr((string) ($current_item['id'] ?? 0)); ?>">

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="pc-builder-rule-source"><?php echo esc_html__('Linh kiện nguồn', 'pc-builder'); ?></label></th>
							<td><?php $this->render_component_type_select('source_component_type_id', $component_types, $current_item['source_component_type_id'] ?? 0, 'pc-builder-rule-source'); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-rule-target"><?php echo esc_html__('Linh kiện đích', 'pc-builder'); ?></label></th>
							<td><?php $this->render_component_type_select('target_component_type_id', $component_types, $current_item['target_component_type_id'] ?? 0, 'pc-builder-rule-target'); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-rule-source-key"><?php echo esc_html__('Khóa thông số nguồn', 'pc-builder'); ?></label></th>
							<td>
								<input name="source_spec_key" type="text" id="pc-builder-rule-source-key" value="<?php echo esc_attr($current_item['source_spec_key'] ?? ''); ?>" class="regular-text" placeholder="Ví dụ: socket" required>
								<p class="description">Thuộc tính của linh kiện thứ nhất (vd: socket của CPU).</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-rule-target-key"><?php echo esc_html__('Khóa thông số đích', 'pc-builder'); ?></label></th>
							<td>
								<input name="target_spec_key" type="text" id="pc-builder-rule-target-key" value="<?php echo esc_attr($current_item['target_spec_key'] ?? ''); ?>" class="regular-text" placeholder="Ví dụ: socket" required>
								<p class="description">Thuộc tính của linh kiện thứ hai (vd: socket của Mainboard).</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-rule-operator"><?php echo esc_html__('Toán tử', 'pc-builder'); ?></label></th>
							<td>
								<select name="operator" id="pc-builder-rule-operator">
									<?php foreach ($operators as $key => $label) : ?>
										<option value="<?php echo esc_attr($key); ?>" <?php selected($current_item['operator'] ?? 'eq', $key); ?>><?php echo esc_html($label); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-rule-message"><?php echo esc_html__('Thông báo lỗi', 'pc-builder'); ?></label></th>
							<td>
								<input name="error_message" type="text" id="pc-builder-rule-message" value="<?php echo esc_attr($current_item['error_message'] ?? ''); ?>" class="regular-text" placeholder="Ví dụ: Socket của CPU và Mainboard không khớp!" required>
								<p class="description">Dòng chữ sẽ hiện lên cảnh báo khách hàng khi họ chọn sai.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="pc-builder-rule-priority"><?php echo esc_html__('Độ ưu tiên', 'pc-builder'); ?></label></th>
							<td><input name="priority" type="number" id="pc-builder-rule-priority" value="<?php echo esc_attr((string) ($current_item['priority'] ?? 100)); ?>" class="small-text"></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__('Kích hoạt', 'pc-builder'); ?></th>
							<td>
								<label>
									<input name="is_active" type="checkbox" value="1" <?php checked(! isset($current_item['is_active']) || ! empty($current_item['is_active']), true); ?>>
									<?php echo esc_html__('Bật luật này.', 'pc-builder'); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(isset($current_item['id']) ? __('Cập nhật luật', 'pc-builder') : __('Thêm luật', 'pc-builder')); ?>
			</form>

			<?php $this->render_compatibility_rules_table($rows); ?>
		</div>
		<?php
	}

	private function get_dashboard_stats() {
		global $wpdb;

		return array(
			'component_types' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_component_types"),
			'rules'           => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_compatibility_rules"),
			'builds'          => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_builds"),
			'specs'           => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pc_product_specs"),
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
			case 'save_compatibility_rule':
				$this->save_compatibility_rule();
				break;
			case 'delete_compatibility_rule':
				$this->delete_compatibility_rule();
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

	private function save_compatibility_rule() {
		check_admin_referer('pc_builder_save_compatibility_rule');

		global $wpdb;

		$table_name = $wpdb->prefix . 'pc_compatibility_rules';
		$id         = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
		$data       = array(
			'source_component_type_id' => isset($_POST['source_component_type_id']) ? absint(wp_unslash($_POST['source_component_type_id'])) : 0,
			'target_component_type_id' => isset($_POST['target_component_type_id']) ? absint(wp_unslash($_POST['target_component_type_id'])) : 0,
			'source_spec_key'          => sanitize_key(wp_unslash($_POST['source_spec_key'] ?? '')),
			'target_spec_key'          => sanitize_key(wp_unslash($_POST['target_spec_key'] ?? '')),
			'operator'                 => sanitize_key(wp_unslash($_POST['operator'] ?? 'eq')),
			'error_message'            => sanitize_text_field(wp_unslash($_POST['error_message'] ?? '')),
			'priority'                 => isset($_POST['priority']) ? intval(wp_unslash($_POST['priority'])) : 100,
			'is_active'                => isset($_POST['is_active']) ? 1 : 0,
		);

		if ($id > 0) {
			$wpdb->update($table_name, $data, array('id' => $id), array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d'), array('%d'));
		} else {
			$wpdb->insert($table_name, $data, array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d'));
		}

		$this->redirect_with_notice('admin.php?page=pc-builder-compatibility-rules', 'rule_saved');
	}

	private function delete_compatibility_rule() {
		$id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

		check_admin_referer('pc_builder_delete_compatibility_rule_' . $id);

		if ($id <= 0) {
			return;
		}

		global $wpdb;

		$wpdb->delete($wpdb->prefix . 'pc_compatibility_rules', array('id' => $id), array('%d'));

		$this->redirect_with_notice('admin.php?page=pc-builder-compatibility-rules', 'rule_deleted');
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

	private function render_compatibility_rules_table($rows) {
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php echo esc_html__('Nguồn', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Đích', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('So khớp thông số', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Toán tử', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Độ ưu tiên', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Kích hoạt', 'pc-builder'); ?></th>
					<th><?php echo esc_html__('Thao tác', 'pc-builder'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($rows)) : ?>
					<tr>
						<td colspan="7"><?php echo esc_html__('No compatibility rules found.', 'pc-builder'); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ($rows as $row) : ?>
						<tr>
							<td><?php echo esc_html($row['source_component_name']); ?></td>
							<td><?php echo esc_html($row['target_component_name']); ?></td>
							<td><?php echo esc_html($row['source_spec_key'] . ' -> ' . $row['target_spec_key']); ?></td>
							<td><?php echo esc_html($row['operator']); ?></td>
							<td><?php echo esc_html((string) $row['priority']); ?></td>
							<td><?php echo esc_html(! empty($row['is_active']) ? __('Có', 'pc-builder') : __('Không', 'pc-builder')); ?></td>
							<td>
								<a href="<?php echo esc_url(admin_url('admin.php?page=pc-builder-compatibility-rules&action=edit&id=' . (int) $row['id'])); ?>"><?php echo esc_html__('Sửa', 'pc-builder'); ?></a>
								|
								<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pc-builder-compatibility-rules&pc_builder_action=delete_compatibility_rule&id=' . (int) $row['id']), 'pc_builder_delete_compatibility_rule_' . (int) $row['id'])); ?>" onclick="return confirm('<?php echo esc_js(__('Xóa luật tương thích này?', 'pc-builder')); ?>');"><?php echo esc_html__('Xóa', 'pc-builder'); ?></a>
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

	private function get_compatibility_rule_for_edit() {
		global $wpdb;

		$action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
		$id     = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;

		if ('edit' !== $action || $id <= 0) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pc_compatibility_rules WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		return is_array($row) ? $row : array();
	}

	private function get_compatibility_rule_rows() {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT rules.*,
				source.name AS source_component_name,
				target.name AS target_component_name
			FROM {$wpdb->prefix}pc_compatibility_rules rules
			LEFT JOIN {$wpdb->prefix}pc_component_types source ON source.id = rules.source_component_type_id
			LEFT JOIN {$wpdb->prefix}pc_component_types target ON target.id = rules.target_component_type_id
			ORDER BY rules.priority ASC, rules.id DESC",
			ARRAY_A
		);

		return is_array($rows) ? $rows : array();
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
