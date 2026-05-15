<?php
/**
 * Plugin Name: MoMo QR Payment
 * Plugin URI: https://github.com/Newbie2l05/chi-thanh-pc-store
 * Description: Cổng thanh toán MoMo QR cho WooCommerce - Hiển thị mã QR để khách hàng quét và chuyển khoản qua ví MoMo.
 * Version: 1.0.0
 * Author: Lâm Chí Thành & Đặng Hoàng Tùng
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: momo-qr-payment
 */

if (! defined('ABSPATH')) {
	exit;
}

add_action('plugins_loaded', 'momo_qr_payment_init');

function momo_qr_payment_init() {
	if (! class_exists('WC_Payment_Gateway')) {
		return;
	}

	class WC_Gateway_MoMo_QR extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'momo_qr';
			$this->icon               = '';
			$this->has_fields         = false;
			$this->method_title       = __('MoMo QR', 'momo-qr-payment');
			$this->method_description = __('Thanh toán bằng cách quét mã QR MoMo. Đơn hàng sẽ ở trạng thái chờ xử lý cho đến khi Admin xác nhận đã nhận tiền.', 'momo-qr-payment');

			$this->init_form_fields();
			$this->init_settings();

			$this->title       = $this->get_option('title', __('Thanh toán MoMo QR', 'momo-qr-payment'));
			$this->description = $this->get_option('description', __('Quét mã QR bằng ứng dụng MoMo để thanh toán.', 'momo-qr-payment'));
			$this->momo_phone  = $this->get_option('momo_phone', '0355379198');
			$this->momo_name   = $this->get_option('momo_name', 'TTShopGear');

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_thankyou_' . $this->id, array($this, 'render_thankyou_qr'));
			add_action('woocommerce_email_after_order_table', array($this, 'render_email_instructions'), 10, 3);
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __('Bật/Tắt', 'momo-qr-payment'),
					'type'    => 'checkbox',
					'label'   => __('Bật cổng thanh toán MoMo QR', 'momo-qr-payment'),
					'default' => 'yes',
				),
				'title' => array(
					'title'       => __('Tiêu đề', 'momo-qr-payment'),
					'type'        => 'text',
					'description' => __('Tiêu đề hiển thị cho khách hàng khi chọn phương thức thanh toán.', 'momo-qr-payment'),
					'default'     => __('Thanh toán MoMo QR', 'momo-qr-payment'),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __('Mô tả', 'momo-qr-payment'),
					'type'        => 'textarea',
					'description' => __('Mô tả hiển thị cho khách hàng khi chọn MoMo QR.', 'momo-qr-payment'),
					'default'     => __('Quét mã QR bằng ứng dụng MoMo để thanh toán. Đơn hàng sẽ được xử lý sau khi chúng tôi xác nhận thanh toán.', 'momo-qr-payment'),
				),
				'momo_phone' => array(
					'title'       => __('Số điện thoại MoMo', 'momo-qr-payment'),
					'type'        => 'text',
					'description' => __('Số điện thoại đăng ký ví MoMo nhận tiền.', 'momo-qr-payment'),
					'default'     => '0355379198',
					'desc_tip'    => true,
				),
				'momo_name' => array(
					'title'       => __('Tên người nhận', 'momo-qr-payment'),
					'type'        => 'text',
					'description' => __('Tên hiển thị trên trang thanh toán (tên chủ ví MoMo hoặc tên cửa hàng).', 'momo-qr-payment'),
					'default'     => 'TTShopGear',
					'desc_tip'    => true,
				),
			);
		}

		public function process_payment($order_id) {
			$order = wc_get_order($order_id);

			if (! $order) {
				return array('result' => 'fail');
			}

			$order->update_status('on-hold', __('Chờ xác nhận thanh toán MoMo QR.', 'momo-qr-payment'));
			wc_reduce_stock_levels($order_id);
			WC()->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url($order),
			);
		}

		public function render_thankyou_qr($order_id) {
			$order = wc_get_order($order_id);

			if (! $order || $order->get_payment_method() !== $this->id) {
				return;
			}

			if ($order->is_paid()) {
				return;
			}

			$amount      = $order->get_total();
			$order_num   = $order->get_order_number();
			$description = 'TTShopGear DH' . $order_num;
			$phone       = $this->momo_phone;
			$name        = $this->momo_name;

			// MoMo deep link format: 2|99|phone|||0|0|amount|description
			$momo_data = '2|99|' . $phone . '|||0|0|' . (int) $amount . '|' . $description;
			$qr_url    = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . rawurlencode($momo_data);

			$this->output_qr_section($qr_url, $amount, $order_num, $phone, $name, $description);
		}

		public function render_email_instructions($order, $sent_to_admin, $plain_text) {
			if ($order->get_payment_method() !== $this->id || $order->is_paid() || $sent_to_admin) {
				return;
			}

			$amount    = $order->get_total();
			$order_num = $order->get_order_number();
			$phone     = $this->momo_phone;
			$name      = $this->momo_name;
			$desc      = 'TTShopGear DH' . $order_num;

			if ($plain_text) {
				echo "\n== THANH TOÁN MOMO ==\n";
				echo "Số MoMo: " . esc_html($phone) . "\n";
				echo "Chủ ví: " . esc_html($name) . "\n";
				echo "Số tiền: " . wp_strip_all_tags(wc_price($amount)) . "\n";
				echo "Nội dung CK: " . esc_html($desc) . "\n";
			} else {
				echo '<h2 style="color:#a50064;">Thanh toán MoMo</h2>';
				echo '<table cellspacing="0" cellpadding="8" border="1" style="border-collapse:collapse; border:1px solid #ddd; margin-bottom:16px;">';
				echo '<tr><td><strong>Số MoMo</strong></td><td>' . esc_html($phone) . '</td></tr>';
				echo '<tr><td><strong>Chủ ví</strong></td><td>' . esc_html($name) . '</td></tr>';
				echo '<tr><td><strong>Số tiền</strong></td><td>' . wp_kses_post(wc_price($amount)) . '</td></tr>';
				echo '<tr><td><strong>Nội dung CK</strong></td><td style="color:#a50064; font-weight:bold;">' . esc_html($desc) . '</td></tr>';
				echo '</table>';
			}
		}

		private function output_qr_section($qr_url, $amount, $order_num, $phone, $name, $description) {
			?>
			<div class="momo-qr-payment" id="momo-qr-payment">
				<style>
					.momo-qr-payment {
						max-width: 480px;
						margin: 32px auto;
						padding: 32px;
						border-radius: 24px;
						background: linear-gradient(165deg, #a50064 0%, #d82d8b 50%, #ff6fae 100%);
						color: #fff;
						text-align: center;
						box-shadow: 0 20px 60px rgba(165, 0, 100, 0.35);
						font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					}

					.momo-qr-payment__logo {
						display: flex;
						align-items: center;
						justify-content: center;
						gap: 10px;
						margin-bottom: 20px;
					}

					.momo-qr-payment__logo-icon {
						width: 48px;
						height: 48px;
						border-radius: 12px;
						background: #fff;
						display: grid;
						place-items: center;
						font-size: 28px;
						font-weight: 900;
						color: #a50064;
						line-height: 1;
					}

					.momo-qr-payment__logo-text {
						font-size: 28px;
						font-weight: 800;
						letter-spacing: -0.02em;
					}

					.momo-qr-payment__title {
						margin: 0 0 6px;
						font-size: 18px;
						font-weight: 600;
						opacity: 0.92;
					}

					.momo-qr-payment__subtitle {
						margin: 0 0 20px;
						font-size: 14px;
						opacity: 0.78;
					}

					.momo-qr-payment__qr-frame {
						display: inline-block;
						padding: 14px;
						border-radius: 18px;
						background: #fff;
						box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
						margin-bottom: 22px;
					}

					.momo-qr-payment__qr-frame img {
						display: block;
						width: 256px;
						height: 256px;
						border-radius: 8px;
					}

					.momo-qr-payment__amount {
						font-size: 36px;
						font-weight: 900;
						margin: 0 0 4px;
						letter-spacing: -0.02em;
					}

					.momo-qr-payment__amount-label {
						font-size: 14px;
						opacity: 0.78;
						margin: 0 0 20px;
					}

					.momo-qr-payment__info {
						background: rgba(255, 255, 255, 0.15);
						backdrop-filter: blur(8px);
						border-radius: 14px;
						padding: 16px 20px;
						text-align: left;
						display: grid;
						gap: 10px;
					}

					.momo-qr-payment__row {
						display: flex;
						justify-content: space-between;
						align-items: center;
						gap: 12px;
					}

					.momo-qr-payment__row-label {
						font-size: 13px;
						opacity: 0.78;
					}

					.momo-qr-payment__row-value {
						font-size: 14px;
						font-weight: 700;
						text-align: right;
					}

					.momo-qr-payment__row-value--copy {
						cursor: pointer;
						padding: 4px 10px;
						border-radius: 8px;
						background: rgba(255, 255, 255, 0.18);
						transition: background 0.2s;
					}

					.momo-qr-payment__row-value--copy:hover {
						background: rgba(255, 255, 255, 0.32);
					}

					.momo-qr-payment__note {
						margin: 18px 0 0;
						font-size: 13px;
						opacity: 0.72;
						line-height: 1.5;
					}

					.momo-qr-payment__timer {
						display: inline-flex;
						align-items: center;
						gap: 6px;
						margin-top: 14px;
						padding: 8px 16px;
						border-radius: 999px;
						background: rgba(255, 255, 255, 0.15);
						font-size: 14px;
						font-weight: 600;
					}

					.momo-qr-payment__timer-dot {
						width: 8px;
						height: 8px;
						border-radius: 50%;
						background: #7fff7f;
						animation: momo_pulse 1.4s ease infinite;
					}

					@keyframes momo_pulse {
						0%, 100% { opacity: 1; }
						50% { opacity: 0.4; }
					}
				</style>

				<div class="momo-qr-payment__logo">
					<div class="momo-qr-payment__logo-icon">M</div>
					<span class="momo-qr-payment__logo-text">MoMo</span>
				</div>

				<h3 class="momo-qr-payment__title">Quét mã QR để thanh toán</h3>
				<p class="momo-qr-payment__subtitle">Mở ứng dụng MoMo → Quét QR → Xác nhận</p>

				<div class="momo-qr-payment__qr-frame">
					<img src="<?php echo esc_url($qr_url); ?>" alt="MoMo QR Code" width="256" height="256">
				</div>

				<p class="momo-qr-payment__amount"><?php echo wp_kses_post(wc_price($amount)); ?></p>
				<p class="momo-qr-payment__amount-label">Tổng thanh toán đơn hàng #<?php echo esc_html($order_num); ?></p>

				<div class="momo-qr-payment__info">
					<div class="momo-qr-payment__row">
						<span class="momo-qr-payment__row-label">Số MoMo</span>
						<span class="momo-qr-payment__row-value momo-qr-payment__row-value--copy" onclick="navigator.clipboard.writeText('<?php echo esc_js($phone); ?>'); this.textContent='Đã copy ✓'; setTimeout(()=>this.textContent='<?php echo esc_js($phone); ?>',1500);" title="Bấm để copy">
							<?php echo esc_html($phone); ?>
						</span>
					</div>
					<div class="momo-qr-payment__row">
						<span class="momo-qr-payment__row-label">Chủ ví</span>
						<span class="momo-qr-payment__row-value"><?php echo esc_html($name); ?></span>
					</div>
					<div class="momo-qr-payment__row">
						<span class="momo-qr-payment__row-label">Nội dung CK</span>
						<span class="momo-qr-payment__row-value momo-qr-payment__row-value--copy" onclick="navigator.clipboard.writeText('<?php echo esc_js($description); ?>'); this.textContent='Đã copy ✓'; setTimeout(()=>this.textContent='<?php echo esc_js($description); ?>',1500);" title="Bấm để copy">
							<?php echo esc_html($description); ?>
						</span>
					</div>
				</div>

				<div class="momo-qr-payment__timer">
					<span class="momo-qr-payment__timer-dot"></span>
					Đang chờ thanh toán...
				</div>

				<p class="momo-qr-payment__note">
					Sau khi chuyển khoản thành công, chúng tôi sẽ xác nhận và xử lý đơn hàng của bạn trong thời gian sớm nhất.
				</p>
			</div>
			<?php
		}
	}

	add_filter('woocommerce_payment_gateways', function ($gateways) {
		$gateways[] = 'WC_Gateway_MoMo_QR';
		return $gateways;
	});
}
