<?php

if (! defined('ABSPATH')) {
	exit;
}

class PC_Builder_Schema {
	public static function activate() {
		self::create_tables();
		self::seed_defaults();
	}

	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate         = $wpdb->get_charset_collate();
		$component_types_table   = $wpdb->prefix . 'pc_component_types';
		$product_specs_table     = $wpdb->prefix . 'pc_product_specs';
		$builds_table            = $wpdb->prefix . 'pc_builds';
		$build_items_table       = $wpdb->prefix . 'pc_build_items';
		$compatibility_rules_tbl = $wpdb->prefix . 'pc_compatibility_rules';

		$sql = array(
			"CREATE TABLE {$component_types_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				code VARCHAR(50) NOT NULL,
				name VARCHAR(100) NOT NULL,
				sort_order INT NOT NULL DEFAULT 0,
				is_required TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_pc_component_types_code (code)
			) {$charset_collate};",
			"CREATE TABLE {$product_specs_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				product_id BIGINT UNSIGNED NOT NULL,
				component_type_id BIGINT UNSIGNED NOT NULL,
				spec_key VARCHAR(100) NOT NULL,
				spec_value VARCHAR(255) NOT NULL,
				spec_value_numeric DECIMAL(12,2) DEFAULT NULL,
				unit VARCHAR(20) DEFAULT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_pc_product_specs_product_key (product_id, spec_key),
				KEY idx_pc_product_specs_component_type_id (component_type_id),
				KEY idx_pc_product_specs_spec_key (spec_key)
			) {$charset_collate};",
			"CREATE TABLE {$builds_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED DEFAULT NULL,
				session_token VARCHAR(100) DEFAULT NULL,
				build_name VARCHAR(150) NOT NULL,
				total_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				power_estimate INT NOT NULL DEFAULT 0,
				status VARCHAR(30) NOT NULL DEFAULT 'draft',
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_pc_builds_user_id (user_id),
				KEY idx_pc_builds_session_token (session_token)
			) {$charset_collate};",
			"CREATE TABLE {$build_items_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				build_id BIGINT UNSIGNED NOT NULL,
				component_type_id BIGINT UNSIGNED NOT NULL,
				product_id BIGINT UNSIGNED NOT NULL,
				quantity INT NOT NULL DEFAULT 1,
				unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uk_pc_build_items_build_component (build_id, component_type_id),
				KEY idx_pc_build_items_product_id (product_id),
				KEY idx_pc_build_items_component_type_id (component_type_id)
			) {$charset_collate};",
			"CREATE TABLE {$compatibility_rules_tbl} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				source_component_type_id BIGINT UNSIGNED NOT NULL,
				target_component_type_id BIGINT UNSIGNED NOT NULL,
				source_spec_key VARCHAR(100) NOT NULL,
				target_spec_key VARCHAR(100) NOT NULL,
				operator ENUM('eq', 'neq', 'gte', 'lte', 'contains') NOT NULL DEFAULT 'eq',
				error_message VARCHAR(255) NOT NULL,
				priority INT NOT NULL DEFAULT 100,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_pc_compatibility_rules_source_type (source_component_type_id),
				KEY idx_pc_compatibility_rules_target_type (target_component_type_id)
			) {$charset_collate};",
		);

		foreach ($sql as $statement) {
			dbDelta($statement);
		}
	}

	public static function seed_defaults() {
		global $wpdb;

		$component_types_table   = $wpdb->prefix . 'pc_component_types';
		$compatibility_rules_tbl = $wpdb->prefix . 'pc_compatibility_rules';

		$default_components = array(
			array('code' => 'cpu', 'name' => 'CPU', 'sort_order' => 1, 'is_required' => 1),
			array('code' => 'mainboard', 'name' => 'Bo mạch chủ', 'sort_order' => 2, 'is_required' => 1),
			array('code' => 'ram', 'name' => 'RAM', 'sort_order' => 3, 'is_required' => 1),
			array('code' => 'gpu', 'name' => 'Card đồ họa', 'sort_order' => 4, 'is_required' => 0),
			array('code' => 'ssd', 'name' => 'Ổ cứng SSD', 'sort_order' => 5, 'is_required' => 1),
			array('code' => 'psu', 'name' => 'Nguồn máy tính', 'sort_order' => 6, 'is_required' => 1),
			array('code' => 'case', 'name' => 'Vỏ case', 'sort_order' => 7, 'is_required' => 1),
			array('code' => 'cooler', 'name' => 'Tản nhiệt', 'sort_order' => 8, 'is_required' => 0),
		);

		foreach ($default_components as $component) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$component_types_table} WHERE code = %s LIMIT 1",
					$component['code']
				)
			);

			if (! $exists) {
				$wpdb->insert($component_types_table, $component, array('%s', '%s', '%d', '%d'));
			}
		}

		$component_rows = $wpdb->get_results("SELECT id, code FROM {$component_types_table}", ARRAY_A);
		$component_map  = array();

		foreach ($component_rows as $component_row) {
			$component_map[$component_row['code']] = (object) $component_row;
		}

		if (empty($component_map['cpu']) || empty($component_map['mainboard']) || empty($component_map['ram']) || empty($component_map['gpu']) || empty($component_map['case'])) {
			return;
		}

		$default_rules = array(
			array(
				'source_component_type_id' => (int) $component_map['cpu']->id,
				'target_component_type_id' => (int) $component_map['mainboard']->id,
				'source_spec_key'          => 'socket',
				'target_spec_key'          => 'socket',
				'operator'                 => 'eq',
				'error_message'            => 'CPU và bo mạch chủ phải dùng cùng socket.',
				'priority'                 => 10,
				'is_active'                => 1,
			),
			array(
				'source_component_type_id' => (int) $component_map['ram']->id,
				'target_component_type_id' => (int) $component_map['mainboard']->id,
				'source_spec_key'          => 'ram_type',
				'target_spec_key'          => 'ram_type',
				'operator'                 => 'eq',
				'error_message'            => 'RAM không tương thích với chuẩn RAM mà bo mạch chủ hỗ trợ.',
				'priority'                 => 20,
				'is_active'                => 1,
			),
			array(
				'source_component_type_id' => (int) $component_map['gpu']->id,
				'target_component_type_id' => (int) $component_map['case']->id,
				'source_spec_key'          => 'length',
				'target_spec_key'          => 'gpu_max_length',
				'operator'                 => 'lte',
				'error_message'            => 'Card đồ họa dài hơn không gian hỗ trợ của vỏ case.',
				'priority'                 => 30,
				'is_active'                => 1,
			),
		);

		foreach ($default_rules as $rule) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id
					FROM {$compatibility_rules_tbl}
					WHERE source_component_type_id = %d
						AND target_component_type_id = %d
						AND source_spec_key = %s
						AND target_spec_key = %s
					LIMIT 1",
					$rule['source_component_type_id'],
					$rule['target_component_type_id'],
					$rule['source_spec_key'],
					$rule['target_spec_key']
				)
			);

			if (! $exists) {
				$wpdb->insert(
					$compatibility_rules_tbl,
					$rule,
					array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d')
				);
			}
		}
	}
}
