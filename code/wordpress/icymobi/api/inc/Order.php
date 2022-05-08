<?php

/**
 * Created by PhpStorm.
 * User: phongnguyen
 * Date: 6/16/16
 * Time: 5:39 PM
 */

class Inspius_Order extends AbstractApi {

	const ORDER_GET_PRICE = 'get_price';
	const ORDER_CREATE = 'create_order';
	const ORDER_GET = 'get_order';
	const ORDER_UPDATE = 'change_order_status';
	const ORDER_LIST_BY_CUSTOMER = 'list_customer_order';
	const ORDER_CREATE_CART = 'create_cart';
	const ORDER_LIST_COUNTRIES = 'list_countries';

	public function response($params = []) {
		$data = [];
		if ($action = $this->_getParam('task')) {
			switch ($action) {
				case self::ORDER_GET_PRICE:
					$data = $this->_getPrice();
					break;
				case self::ORDER_CREATE:
					$data = $this->_createOrder();
					break;
				case self::ORDER_UPDATE:
					$data = $this->_changeOrderStatus();
					break;
				case self::ORDER_GET:
					$data = $this->_getOrder();
					break;
				case self::ORDER_LIST_BY_CUSTOMER:
					$data = $this->_getCustomerOrder();
					break;
				case self::ORDER_CREATE_CART:
					$data = $this->_createCart();
					break;
				case self::ORDER_LIST_COUNTRIES:
					$data = $this->_getCountryList();
					break;
				default:
					break;
			}
			return $data;
		}
		throw new Exception(Inspius_Status::USER_NO_ROUTE);
	}

	protected function _getPrice() {
		$jsonItems = str_replace("\\", "", $this->_getParam('line_items'));
		$items = json_decode($jsonItems, true);

		/* @var $cart WC_Cart */
		$cart = new WC_Cart();
		foreach ($items as $item) {
			$cart->add_to_cart($item['product_id'], $item['quantity']);
		}
		$couponCode = str_replace("\\", "", $this->_getParam('coupon'));
		if ($couponCode) {
			$coupon = new WC_Coupon($couponCode);
			if ($coupon->is_valid()) {
				$cart->add_discount($couponCode);
			}
		}
		$discounted_price = $this->getCouponDiscount($cart);
		$couponDiscountAmount = round( $discounted_price, wc_get_price_decimals() );
		return [
			'discount_total' => $couponDiscountAmount,
			'subtotal' => $cart->subtotal,
			'total' => $cart->subtotal - $couponDiscountAmount,
			'currency' => get_option('woocommerce_currency')
		];
	}
	
	protected function getCouponDiscount($cart) {
		WC()->cart = $cart;
		$discounted_price = 0;
		$cartContens = $cart->get_cart();
		foreach ($cartContens as $values) {
			$_product = $values['data'];
			// Prices
			$price = $_product->get_price();
			$product = $values['data'];
			$undiscounted_price = $price;
			foreach ($cart->get_coupons() as $code => $coupon) {
				if ($coupon->is_valid() && ( $coupon->is_valid_for_product($product, $values) || $coupon->is_valid_for_cart() )) {
					$discount_amount = $coupon->get_discount_amount('yes' === get_option('woocommerce_calc_discounts_sequentially', 'no') ? $price : $undiscounted_price, $values, true);
					$discount_amount = min($price, $discount_amount);
					$price = max($price - $discount_amount, 0);
					$total_discount = $discount_amount * $values['quantity'];
					$total_discount_tax = 0;
					if (wc_tax_enabled()) {
						$tax_rates = WC_Tax::get_rates($product->get_tax_class());
						$taxes = WC_Tax::calc_tax($discount_amount, $tax_rates, $cart->prices_include_tax);
						$total_discount_tax = WC_Tax::get_tax_total($taxes) * $values['quantity'];
						$total_discount = $cart->prices_include_tax ? $total_discount - $total_discount_tax : $total_discount;
					}
				}
				// If the price is 0, we can stop going through coupons because there is nothing more to discount for this product.
				if (0 >= $price) {
					break;
				}
			}
			$discounted_price += $total_discount;
		}
		return $discounted_price;
	}

	protected function _createCart() {
		return [
			'cart_id' => '',
			'price' => $this->_getPrice(),
			'shipping_methods' => $this->_getShippingMethod(),
			'payment_methods' => $this->_getPaymentMethod()
		];
	}

	protected function _createOrder() {
		if (Inspius_Icymobi_Option::instance()->get_option('general_enable_app', '')) {
			throw new Exception(Inspius_Status::APP_DISABLED);
		}
		$params = $this->_getParams(['payment_method', 'payment_method_title', 'billing', 'shipping', 'line_items', 'shipping_lines']);
		if (count($params) == 6) {

//            $params['set_paid'] = true;
			$params['billing'] = json_decode(str_replace("\\", "", $params['billing']), true);
			$params['shipping'] = json_decode(str_replace("\\", "", $params['shipping']), true);
			$params['line_items'] = json_decode(str_replace("\\", "", $params['line_items']), true);
			$params['shipping_lines'] = json_decode(str_replace("\\", "", $params['shipping_lines']), true);

			$customerId = $this->_getParam('customer_id');
			if ($customerId && is_numeric($customerId)) {
				$params['customer_id'] = $customerId;
				try {
					$this->wc_api->put("customers/$customerId", [
						'billing' => $params['billing'],
						'shipping' => $params['shipping']
							]
					);
				} catch (Exception $ex) {
					
				}
			}

			$note = $this->_getParam('customer_note');
			if ($note) {
				$params['customer_note'] = $note;
			}

			$couponCode = $this->_getParam('coupon');
			if ($couponCode) {
				$coupon = new WC_Coupon($couponCode);
				if ($coupon->is_valid()) {
					/* @var $cart WC_Cart */
					$cart = new WC_Cart();
					foreach ($params['line_items'] as $item) {
						$cart->add_to_cart($item['product_id'], $item['quantity']);
					}
					$cart->add_discount($couponCode);
					$discounted_price = $this->getCouponDiscount($cart);
					$couponDiscountAmount = round( $discounted_price, wc_get_price_decimals() );
					if ($couponDiscountAmount > 0) {
						$params['fee_lines'] = [
							[
								'name' => "Discount by coupon '{$couponCode}'",
								'total' => "-" . $couponDiscountAmount
							]
						];
						$params['coupon_lines'] = [
							[
								'code' => $couponCode,
								'discount' => $couponDiscountAmount
							]
						];
					}
					$coupon->inc_usage_count();
				}
			}

			$order = $this->wc_api->post('orders', $params);

			// Add Divice Token
			if ($order['id'] > 0) {
				update_post_meta($order['id'], 'icymobi_device_token', $this->_getParam('device_token'));
			}

			return $this->_formatOrder($order);
		}
		throw new Exception(Inspius_Status::ORDER_INVALID_DATA);
	}

	protected function _changeOrderStatus() {
		$statusList = ['pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'];
		$id = $this->_getParam('id');
		$status = $this->_getParam('status');

		if ($id && is_numeric($id) && in_array($status, $statusList)) {
			return $this->_formatOrder($this->wc_api->put("orders/$id", ['status' => $status]));
		}
		throw new Exception(Inspius_Status::ORDER_NOT_FOUND);
	}

	protected function _getOrder() {
		$id = $this->_getParam('id');
		if ($id && is_numeric($id)) {
                        $orders = $this->wc_api->get("orders", ['include' => $id]);
			return $this->_formatOrder($orders[0]);
		}
		throw new Exception(Inspius_Status::ORDER_NOT_FOUND);
	}

	protected function _getCustomerOrder() {
		$customerId = $this->_getParam('customer_id');
		if ($customerId && is_numeric($customerId)) {
			$data = ['customer' => $customerId];
			if ($page = $this->_getParam('page')) {
				$data['page'] = $page;
			}
			if ($perPage = $this->_getParam('per_page')) {
				$data['per_page'] = $perPage;
			}
			return $this->_formatOrderList($this->wc_api->get("orders", $data));
		}
		throw new Exception(Inspius_Status::ORDER_NOT_FOUND);
	}

	protected function _getCountryList() {
		$countryModel = new WC_Countries();
		$countries = $countryModel->get_countries();
		$returnCountries = [];
		foreach ($countries as $id => $name) {
			$returnCountries[$id] = [
				'id' => $id,
				'name' => $name,
				'state' => !$countryModel->get_states($id) ? [] : $countryModel->get_states($id)
			];
		}
		return $returnCountries;
	}

	private function _getShippingMethod() {
		$returnZones = [];

		// get all shipping from zones
		$zones = new WC_Shipping_Zones();
		foreach ($zones->get_zones() as $id => $zone) {
			$currentZone = new WC_Shipping_Zone($id);
			$zone['shipping_methods'] = $currentZone->get_shipping_methods(true);
			$zone['zone_locations'] = $this->_formatZoneLocations($zone['zone_locations']);
			$returnZones['zones'][$id] = $zone;
		}

		// get rest of the world shipping methods
		$defaultZone = new WC_Shipping_Zone(0);
		$returnZones['default'] = $defaultZone->get_data();
		$returnZones['default']['shipping_methods'] = $defaultZone->get_shipping_methods(true);

		return $returnZones;
	}

	private function _getPaymentMethod() {
		$paymentGateways = new WC_Payment_Gateways();
		$methods = $this->_formatPaymentMethod($paymentGateways->get_available_payment_gateways());
		if (array_key_exists('bacs', $methods)) {
			$methods['bacs']['accounts'] = get_option('woocommerce_bacs_accounts');
		}
		return apply_filters('icymobi_list_payment_method', $methods);
	}

	private function _formatOrder($order) {
		// add payment method title
		$paymentGateways = $this->_getPaymentMethod();
		foreach ($paymentGateways as $id => $gateway) {
			if ($id == $order['payment_method']) {
				$order['payment_method_title'] = $gateway['title'];
				break;
			}
		}

		// add status text
		$order['status_text'] = wc_get_order_status_name($order['status']);

		// add coupon total
		if (!empty($order['coupon_lines'])) {
			$coupons = [];
			foreach ($order['coupon_lines'] as $coupon) {
				$coupons[] = "Discount by coupon '" . $coupon['code'] . "'";
			}

			foreach ($order['fee_lines'] as $fee) {
				if (in_array($fee['name'], $coupons)) {
					$order['discount_total'] = $order['discount_total'] - $fee['total']; // because total of discount fee is negative
					$order['discount_tax'] = $order['total_tax'] + $fee['total_tax'];
				}
			}
		}
		return $order;
	}

	private function _formatOrderList($orders) {
		$count = count($orders);
		if ($count > 0) {
			$paymentGateways = $this->_getPaymentMethod();
			for ($i = 0; $i < $count; $i++) {
				foreach ($paymentGateways as $id => $gateway) {
					if ($id == $orders[$i]['payment_method']) {
						$orders[$i]['payment_method_title'] = $gateway['title'];
						break;
					}
				}
				$orders[$i]['status_text'] = wc_get_order_status_name($orders[$i]['status']);
			}
		}
		return $orders;
	}

	private function _formatZoneLocations($locations) {
		$postcodes = $countries = $states = [];
		foreach ($locations as $location) {
			if ($location->type == 'postcode') {
				$postcodes[] = $location->code;
			}
			if ($location->type == 'state') {
				$states[] = $location->code;
			}
			if ($location->type == 'country') {
				$countries[] = $location->code;
			}
		}
		$returnLocations = [];
		if (count($postcodes) > 0) {
			foreach ($postcodes as $code) {
				foreach ($states as $state) {
					$returnLocations[] = $state . "-" . $code;
				}
				foreach ($countries as $country) {
					$returnLocations[] = $country . "-" . $code;
				}
			}
		} else {
			$returnLocations = array_merge($states, $countries);
		}
		return $returnLocations;
	}

	private function _formatPaymentMethod($methods) {
		$formattedMethods = [];
		foreach ($methods as $method) {
			if ($method->id == 'bacs' || $method->id == 'cheque' || $method->id == 'cod') {
				$formattedMethods[$method->id] = [
					"id" => $method->id,
					"title" => $method->title,
					"description" => $method->description
				];
			}
		}
		return $formattedMethods;
	}

}