CREATE TABLE `wp_pc_component_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wp_pc_component_types_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_pc_product_specs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `component_type_id` BIGINT UNSIGNED NOT NULL,
  `spec_key` VARCHAR(100) NOT NULL,
  `spec_value` VARCHAR(255) NOT NULL,
  `spec_value_numeric` DECIMAL(12,2) DEFAULT NULL,
  `unit` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wp_pc_product_specs_product_key` (`product_id`, `spec_key`),
  KEY `idx_wp_pc_product_specs_component_type_id` (`component_type_id`),
  KEY `idx_wp_pc_product_specs_spec_key` (`spec_key`),
  CONSTRAINT `fk_wp_pc_product_specs_component_type`
    FOREIGN KEY (`component_type_id`) REFERENCES `wp_pc_component_types` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_pc_builds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `session_token` VARCHAR(100) DEFAULT NULL,
  `build_name` VARCHAR(150) NOT NULL,
  `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `power_estimate` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wp_pc_builds_user_id` (`user_id`),
  KEY `idx_wp_pc_builds_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_pc_build_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `build_id` BIGINT UNSIGNED NOT NULL,
  `component_type_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wp_pc_build_items_build_component` (`build_id`, `component_type_id`),
  KEY `idx_wp_pc_build_items_product_id` (`product_id`),
  KEY `idx_wp_pc_build_items_component_type_id` (`component_type_id`),
  CONSTRAINT `fk_wp_pc_build_items_build`
    FOREIGN KEY (`build_id`) REFERENCES `wp_pc_builds` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_wp_pc_build_items_component_type`
    FOREIGN KEY (`component_type_id`) REFERENCES `wp_pc_component_types` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wp_pc_compatibility_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_component_type_id` BIGINT UNSIGNED NOT NULL,
  `target_component_type_id` BIGINT UNSIGNED NOT NULL,
  `source_spec_key` VARCHAR(100) NOT NULL,
  `target_spec_key` VARCHAR(100) NOT NULL,
  `operator` ENUM('eq', 'neq', 'gte', 'lte', 'contains') NOT NULL DEFAULT 'eq',
  `error_message` VARCHAR(255) NOT NULL,
  `priority` INT NOT NULL DEFAULT 100,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wp_pc_compatibility_rules_source_type` (`source_component_type_id`),
  KEY `idx_wp_pc_compatibility_rules_target_type` (`target_component_type_id`),
  CONSTRAINT `fk_wp_pc_compatibility_rules_source_type`
    FOREIGN KEY (`source_component_type_id`) REFERENCES `wp_pc_component_types` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_wp_pc_compatibility_rules_target_type`
    FOREIGN KEY (`target_component_type_id`) REFERENCES `wp_pc_component_types` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wp_pc_component_types` (`code`, `name`, `sort_order`, `is_required`) VALUES
('cpu', 'CPU', 1, 1),
('mainboard', 'Mainboard', 2, 1),
('ram', 'RAM', 3, 1),
('gpu', 'GPU', 4, 0),
('ssd', 'SSD', 5, 1),
('psu', 'PSU', 6, 1),
('case', 'Case', 7, 1),
('cooler', 'Cooler', 8, 0);

INSERT INTO `wp_pc_compatibility_rules`
(`source_component_type_id`, `target_component_type_id`, `source_spec_key`, `target_spec_key`, `operator`, `error_message`, `priority`, `is_active`)
SELECT s.id, t.id, 'socket', 'socket', 'eq', 'CPU va mainboard khong cung socket.', 10, 1
FROM `wp_pc_component_types` s
JOIN `wp_pc_component_types` t
  ON s.code = 'cpu' AND t.code = 'mainboard'
UNION ALL
SELECT s.id, t.id, 'ram_type', 'ram_type', 'eq', 'RAM khong tuong thich voi loai RAM mainboard ho tro.', 20, 1
FROM `wp_pc_component_types` s
JOIN `wp_pc_component_types` t
  ON s.code = 'ram' AND t.code = 'mainboard'
UNION ALL
SELECT s.id, t.id, 'length', 'gpu_max_length', 'lte', 'GPU dai hon khong gian ho tro cua case.', 30, 1
FROM `wp_pc_component_types` s
JOIN `wp_pc_component_types` t
  ON s.code = 'gpu' AND t.code = 'case';

/*
Example product specs:

INSERT INTO `wp_pc_product_specs`
(`product_id`, `component_type_id`, `spec_key`, `spec_value`, `spec_value_numeric`, `unit`)
VALUES
(101, 1, 'socket', 'LGA1700', NULL, NULL),
(205, 2, 'socket', 'LGA1700', NULL, NULL),
(205, 2, 'ram_type', 'DDR5', NULL, NULL),
(307, 3, 'ram_type', 'DDR5', NULL, NULL),
(411, 4, 'length', '320', 320, 'mm'),
(512, 7, 'gpu_max_length', '340', 340, 'mm');
*/
