<?php

if (! defined('ABSPATH')) {
	exit;
}

class PC_Builder_Frontend {
	public function hooks() {
		add_shortcode('pc_builder', array($this, 'render_builder_shortcode'));
		add_filter('woocommerce_product_tabs', array($this, 'register_specs_product_tab'));
	}

	public function render_builder_shortcode() {
		if (! class_exists('WooCommerce')) {
			return '<div class="pc-builder-empty">WooCommerce là bắt buộc để sử dụng trình dựng PC.</div>';
		}

		$component_types = $this->get_component_types();
		$selected_ids    = $this->get_selected_component_ids();
		$build_result    = $this->build_result($selected_ids);

		if (
			'POST' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '')
			&& isset($_POST['pc_builder_submit_action'])
			&& 'add_to_cart' === sanitize_key(wp_unslash($_POST['pc_builder_submit_action']))
		) {
			$this->handle_add_build_to_cart($build_result);
		}

		ob_start();
		?>
		<div class="pc-builder-app">
			<div class="pc-builder-intro">
				<h2><?php echo esc_html__('Trình dựng PC', 'pc-builder'); ?></h2>
				<p><?php echo esc_html__('Chọn linh kiện tương thích và thêm toàn bộ cấu hình vào giỏ hàng.', 'pc-builder'); ?></p>
			</div>

			<form method="post" class="pc-builder-form">
				<?php wp_nonce_field('pc_builder_frontend_action', 'pc_builder_frontend_nonce'); ?>
				<div class="pc-builder-grid">
					<div class="pc-builder-config">
						<?php foreach ($component_types as $component_type) : ?>
							<?php
							$options     = $this->get_products_for_component_type((int) $component_type['id']);
							$selected_id = isset($selected_ids[(int) $component_type['id']]) ? (int) $selected_ids[(int) $component_type['id']] : 0;
							?>
							<div class="pc-builder-row">
								<label for="pc-builder-component-<?php echo esc_attr((string) $component_type['id']); ?>">
									<?php echo esc_html($component_type['name']); ?>
									<?php if (! empty($component_type['is_required'])) : ?>
										<span class="pc-builder-required">*</span>
									<?php endif; ?>
								</label>
								<select
									id="pc-builder-component-<?php echo esc_attr((string) $component_type['id']); ?>"
									name="pc_builder_components[<?php echo esc_attr((string) $component_type['id']); ?>]"
								>
									<option value="0"><?php echo esc_html__('Chọn sản phẩm', 'pc-builder'); ?></option>
									<?php foreach ($options as $option) : ?>
										<option value="<?php echo esc_attr((string) $option['id']); ?>" <?php selected($selected_id, (int) $option['id']); ?>>
											<?php echo esc_html($option['label']); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="pc-builder-summary">
						<h3><?php echo esc_html__('Tóm tắt cấu hình', 'pc-builder'); ?></h3>

						<ul class="pc-builder-summary-list">
							<?php foreach ($build_result['selected_products'] as $product_data) : ?>
								<li>
									<strong><?php echo esc_html($product_data['component_name']); ?>:</strong>
									<span><?php echo esc_html($product_data['product_name']); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>

						<div class="pc-builder-stats">
							<div><span><?php echo esc_html__('Tổng giá', 'pc-builder'); ?></span><strong><?php echo wp_kses_post(wc_price($build_result['total_price'])); ?></strong></div>
							<div><span><?php echo esc_html__('Công suất ước tính', 'pc-builder'); ?></span><strong><?php echo esc_html((string) $build_result['power_estimate']); ?>W</strong></div>
							<div><span><?php echo esc_html__('Trạng thái tương thích', 'pc-builder'); ?></span><strong><?php echo esc_html($build_result['is_valid'] ? __('Hợp lệ', 'pc-builder') : __('Cần kiểm tra', 'pc-builder')); ?></strong></div>
						</div>

						<div class="pc-builder-actions">
							<button type="submit" name="pc_builder_submit_action" value="preview" class="button button-secondary">
								<?php echo esc_html__('Kiểm tra cấu hình', 'pc-builder'); ?>
							</button>
							<button type="submit" name="pc_builder_submit_action" value="add_to_cart" class="button button-primary" <?php disabled(! $build_result['can_add_to_cart']); ?>>
								<?php echo esc_html__('Thêm cấu hình vào giỏ', 'pc-builder'); ?>
							</button>
						</div>
					</div>
				</div>

				<div class="pc-builder-validation">
					<h3><?php echo esc_html__('Kiểm tra tương thích', 'pc-builder'); ?></h3>
					<?php if (empty($build_result['messages'])) : ?>
						<p><?php echo esc_html__('Hãy chọn linh kiện để bắt đầu kiểm tra tương thích.', 'pc-builder'); ?></p>
					<?php else : ?>
						<ul class="pc-builder-messages">
							<?php foreach ($build_result['messages'] as $message) : ?>
								<li class="pc-builder-message pc-builder-message-<?php echo esc_attr($message['type']); ?>">
									<?php echo esc_html($message['text']); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	public function register_specs_product_tab($tabs) {
		global $product;

		if (! $product instanceof WC_Product) {
			return $tabs;
		}

		$specs = $this->get_product_specs((int) $product->get_id());

		if (empty($specs)) {
			return $tabs;
		}

		$tabs['pc_builder_specs'] = array(
			'title'    => __('Thông số kỹ thuật', 'pc-builder'),
			'priority' => 25,
			'callback' => array($this, 'render_specs_product_tab'),
		);

		return $tabs;
	}

	public function render_specs_product_tab() {
		global $product;

		if (! $product instanceof WC_Product) {
			return;
		}

		$specs = $this->get_product_specs((int) $product->get_id());

		if (empty($specs)) {
			echo '<p>' . esc_html__('Chưa có thông số kỹ thuật.', 'pc-builder') . '</p>';
			return;
		}

		echo '<table class="shop_attributes pc-builder-specs-table"><tbody>';

		foreach ($specs as $spec) {
			$value = $spec['spec_value'];

			if (! empty($spec['unit']) && is_numeric($spec['spec_value_numeric'])) {
				$value = rtrim(rtrim((string) $spec['spec_value_numeric'], '0'), '.') . $spec['unit'];
			}

			echo '<tr>';
			echo '<th>' . esc_html($this->format_spec_label($spec['spec_key'])) . '</th>';
			echo '<td>' . esc_html($value) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function handle_add_build_to_cart($build_result) {
		if (! isset($_POST['pc_builder_frontend_nonce'])) {
			return;
		}

		if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pc_builder_frontend_nonce'])), 'pc_builder_frontend_action')) {
			return;
		}

		if (! $build_result['can_add_to_cart']) {
			wc_add_notice(__('Cấu hình này chưa sẵn sàng để thêm vào giỏ hàng.', 'pc-builder'), 'error');
			return;
		}

		foreach ($build_result['selected_products'] as $product_data) {
			WC()->cart->add_to_cart($product_data['product_id'], 1);
		}

		wc_add_notice(__('Đã thêm toàn bộ cấu hình PC vào giỏ hàng.', 'pc-builder'));
		wp_safe_redirect(wc_get_cart_url());
		exit;
	}

	private function get_selected_component_ids() {
		if (
			'POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')
			|| ! isset($_POST['pc_builder_components'])
			|| ! is_array($_POST['pc_builder_components'])
		) {
			return array();
		}

		$selected_ids = array();

		foreach (wp_unslash($_POST['pc_builder_components']) as $component_type_id => $product_id) {
			$selected_ids[(int) $component_type_id] = absint($product_id);
		}

		return $selected_ids;
	}

	private function get_component_types() {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT id, code, name, is_required
			FROM {$wpdb->prefix}pc_component_types
			ORDER BY sort_order ASC, name ASC",
			ARRAY_A
		);

		return is_array($rows) ? $rows : array();
	}

	private function get_products_for_component_type($component_type_id) {
		global $wpdb;

		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT product_id
				FROM {$wpdb->prefix}pc_product_specs
				WHERE component_type_id = %d
				ORDER BY product_id DESC",
				$component_type_id
			)
		);

		if (empty($product_ids)) {
			return array();
		}

		$options = array();

		foreach ($product_ids as $product_id) {
			$product = wc_get_product((int) $product_id);

			if (! $product || 'publish' !== $product->get_status()) {
				continue;
			}

			$options[] = array(
				'id'    => (int) $product->get_id(),
				'label' => $product->get_name() . ' - ' . wp_strip_all_tags(wc_price((float) $product->get_price())),
			);
		}

		return $options;
	}

	private function build_result($selected_ids) {
		$component_types   = $this->get_component_types();
		$selected_products = array();
		$messages          = array();
		$total_price       = 0;
		$power_estimate    = 0;
		$is_valid          = true;
		$has_selection     = ! empty(array_filter($selected_ids));

		if (! $has_selection) {
			return array(
				'selected_products' => array(),
				'messages'          => array(),
				'total_price'       => 0,
				'power_estimate'    => 0,
				'is_valid'          => false,
				'can_add_to_cart'   => false,
			);
		}

		foreach ($component_types as $component_type) {
			$component_type_id = (int) $component_type['id'];
			$product_id        = isset($selected_ids[$component_type_id]) ? (int) $selected_ids[$component_type_id] : 0;

			if ($product_id <= 0) {
				if (! empty($component_type['is_required'])) {
					$is_valid   = false;
					$messages[] = array(
						'type' => 'warning',
						'text' => sprintf(__('Thiếu linh kiện bắt buộc: %s.', 'pc-builder'), $component_type['name']),
					);
				}

				continue;
			}

			$product = wc_get_product($product_id);

			if (! $product) {
				continue;
			}

			$total_price    += (float) $product->get_price();
			$power_estimate += $this->get_product_power_estimate($product_id);
			$selected_products[$component_type_id] = array(
				'component_type_id' => $component_type_id,
				'component_name'    => $component_type['name'],
				'product_id'        => $product_id,
				'product_name'      => $product->get_name(),
			);
		}

		$rule_messages = $this->validate_rules($selected_products);

		foreach ($rule_messages as $rule_message) {
			$messages[] = $rule_message;

			if ('error' === $rule_message['type']) {
				$is_valid = false;
			}
		}

		if (empty($messages) && ! empty($selected_products)) {
			$messages[] = array(
				'type' => 'success',
				'text' => __('Cấu hình hiện tại tương thích.', 'pc-builder'),
			);
		}

		return array(
			'selected_products' => $selected_products,
			'messages'          => $messages,
			'total_price'       => $total_price,
			'power_estimate'    => $power_estimate,
			'is_valid'          => $is_valid,
			'can_add_to_cart'   => $is_valid && ! empty($selected_products),
		);
	}

	private function validate_rules($selected_products) {
		global $wpdb;

		$messages = array();
		$rules    = $wpdb->get_results(
			"SELECT *
			FROM {$wpdb->prefix}pc_compatibility_rules
			WHERE is_active = 1
			ORDER BY priority ASC, id ASC",
			ARRAY_A
		);

		if (empty($rules)) {
			return $messages;
		}

		foreach ($rules as $rule) {
			$source_component_id = (int) $rule['source_component_type_id'];
			$target_component_id = (int) $rule['target_component_type_id'];

			if (empty($selected_products[$source_component_id]) || empty($selected_products[$target_component_id])) {
				continue;
			}

			$source_specs = $this->get_product_specs_map((int) $selected_products[$source_component_id]['product_id']);
			$target_specs = $this->get_product_specs_map((int) $selected_products[$target_component_id]['product_id']);
			$source_spec  = $source_specs[$rule['source_spec_key']] ?? null;
			$target_spec  = $target_specs[$rule['target_spec_key']] ?? null;

			if (! $source_spec || ! $target_spec) {
				$messages[] = array(
					'type' => 'warning',
					'text' => sprintf(
						__('Thiếu dữ liệu thông số cho quy tắc: %s / %s.', 'pc-builder'),
						$rule['source_spec_key'],
						$rule['target_spec_key']
					),
				);
				continue;
			}

			if (! $this->compare_spec_values($source_spec, $target_spec, $rule['operator'])) {
				$messages[] = array(
					'type' => 'error',
					'text' => $rule['error_message'],
				);
			}
		}

		return $messages;
	}

	private function compare_spec_values($source_spec, $target_spec, $operator) {
		$source_value      = $source_spec['spec_value'];
		$target_value      = $target_spec['spec_value'];
		$source_numeric    = isset($source_spec['spec_value_numeric']) && '' !== (string) $source_spec['spec_value_numeric'] ? (float) $source_spec['spec_value_numeric'] : null;
		$target_numeric    = isset($target_spec['spec_value_numeric']) && '' !== (string) $target_spec['spec_value_numeric'] ? (float) $target_spec['spec_value_numeric'] : null;
		$normalized_source = strtolower(trim((string) $source_value));
		$normalized_target = strtolower(trim((string) $target_value));

		switch ($operator) {
			case 'eq':
				return $normalized_source === $normalized_target;
			case 'neq':
				return $normalized_source !== $normalized_target;
			case 'gte':
				return null !== $source_numeric && null !== $target_numeric && $source_numeric >= $target_numeric;
			case 'lte':
				return null !== $source_numeric && null !== $target_numeric && $source_numeric <= $target_numeric;
			case 'contains':
				return false !== stripos($normalized_source, $normalized_target) || false !== stripos($normalized_target, $normalized_source);
			default:
				return false;
		}
	}

	private function get_product_specs($product_id) {
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

	private function get_product_specs_map($product_id) {
		$specs = $this->get_product_specs($product_id);
		$map   = array();

		foreach ($specs as $spec) {
			$map[$spec['spec_key']] = $spec;
		}

		return $map;
	}

	private function get_product_power_estimate($product_id) {
		$specs = $this->get_product_specs_map($product_id);

		if (! empty($specs['power_draw']['spec_value_numeric'])) {
			return (int) $specs['power_draw']['spec_value_numeric'];
		}

		if (! empty($specs['tdp']['spec_value_numeric'])) {
			return (int) $specs['tdp']['spec_value_numeric'];
		}

		return 0;
	}

	private function format_spec_label($spec_key) {
		$labels = array(
			'socket'         => __('Socket', 'pc-builder'),
			'tdp'            => __('TDP', 'pc-builder'),
			'power_draw'     => __('Công suất tiêu thụ', 'pc-builder'),
			'ram_type'       => __('Chuẩn RAM', 'pc-builder'),
			'form_factor'    => __('Kích thước chuẩn', 'pc-builder'),
			'capacity'       => __('Dung lượng', 'pc-builder'),
			'speed'          => __('Tốc độ', 'pc-builder'),
			'vram'           => __('Bộ nhớ đồ họa', 'pc-builder'),
			'interface'      => __('Chuẩn giao tiếp', 'pc-builder'),
			'watt'           => __('Công suất nguồn', 'pc-builder'),
			'efficiency'     => __('Hiệu suất', 'pc-builder'),
			'gpu_max_length' => __('Chiều dài GPU tối đa', 'pc-builder'),
			'socket_support' => __('Socket hỗ trợ', 'pc-builder'),
		);

		if (isset($labels[$spec_key])) {
			return $labels[$spec_key];
		}

		return ucwords(str_replace('_', ' ', $spec_key));
	}
}
