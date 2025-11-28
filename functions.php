<?php


/**
 * Add page/product-specific class: "page-or-product-{slug}".
 *
 * Why: Enables targeted CSS without extra page templates.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function thmg_body_class_add_slug( $classes ) {
	if ( is_product() || is_page() ) {
		global $post;

		$allowed_slugs = array(
			'bumbox',
			'a-la-carte',
			'jay-cutler',
			'falcons',
			'clean-meal-prep',
			'olive-oil',
			'pizza-pinsa-romano',
			'keto-meals',
			'order-in-bulk',
			'sauce-cups',
			'cbum',
		);

		if ( $post instanceof WP_Post && in_array( $post->post_name, $allowed_slugs, true ) ) {
			$classes[] = 'page-or-product-' . $post->post_name;
		}
	}
	return $classes;
}
add_filter( 'body_class', 'thmg_body_class_add_slug' );

function order_contains_only_virtual_gifts( $order ) {
	if ( ! $order ) {
		return false;
	}

	$items = $order->get_items();
	if ( empty( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		$product = $item->get_product();

		$is_virtual_gift = $product && $product->is_virtual();

		if ( ! $is_virtual_gift ) {
			return false;
		}
	}

	return true;
}

add_filter(
	'woocommerce_sale_flash',
	function () {
		return '<span class="onsale"
	">Black Friday</span>';
	}
);


add_filter(
	'woocommerce_apply_individual_use_coupon',
	function ( $individual_use, $coupon, $applied_coupons ) {

		$main_coupon   = SUBSCRIBEANDSAVE_ALLOWED_COUPONS;
		$free_shipping = SUBSCRIPTION_FREE_SHIPPING_CODE;

		$current = strtolower( $coupon->get_code() );
		$applied = array_map( 'strtolower', $applied_coupons ?? array() );

		if ( $current === $main_coupon && in_array( $free_shipping, $applied, true ) ) {
			return array( $free_shipping );
		}

		return $individual_use;
	},
	10,
	3
);

add_filter(
	'woocommerce_apply_with_individual_use_coupon',
	function ( $apply, $incoming_coupon, $existing_coupon, $applied_coupon_codes ) {

		$main_coupon   = SUBSCRIBEANDSAVE_ALLOWED_COUPONS;
		$free_shipping = SUBSCRIPTION_FREE_SHIPPING_CODE;
		$incoming      = strtolower( $incoming_coupon->get_code() );
		$existing      = strtolower( $existing_coupon->get_code() );
		$applied       = array_map( 'strtolower', $applied_coupon_codes ?? array() );

		if ( $incoming === $free_shipping && in_array( $main_coupon, $applied, true ) ) {
			return true;
		}

		return $apply;
	},
	10,
	4
);

add_action(
	'woocommerce_checkout_order_processed',
	function ( $order_id, $posted_data, $order ) {
		WC()->cart->empty_cart();
	},
	10,
	3
);

add_filter(
	'woocommerce_package_rates',
	function ( $rates, $package ) {
		if (
			defined( 'DOING_AJAX' ) && DOING_AJAX &&
			isset( $_REQUEST['wc-ajax'] ) &&
			'wc_stripe_get_shipping_options' === $_REQUEST['wc-ajax']
		) {
			uasort(
				$rates,
				function ( $a, $b ) {
					$costA = is_object( $a ) && method_exists( $a, 'get_cost' ) ? (float) $a->get_cost() : (float) ( $a->cost ?? 0 );
					$costB = is_object( $b ) && method_exists( $b, 'get_cost' ) ? (float) $b->get_cost() : (float) ( $b->cost ?? 0 );
					return $costA <=> $costB;
				}
			);

			$only_supplements = function_exists( 'mwc_cart_contains_only_category' ) ? mwc_cart_contains_only_category( 'supplements' ) : false;

			if ( $only_supplements ) {
				$rates = (array) $rates;
				reset( $rates );
				$primary_rate_id = key( $rates );

				if ( null !== $primary_rate_id ) {
					$primary_rate = $rates[ $primary_rate_id ];
					$label        = __( 'Shipping', 'megafitmeals' );

					if ( is_object( $primary_rate ) ) {
						if ( method_exists( $primary_rate, 'set_label' ) ) {
							$primary_rate->set_label( $label );
						} else {
							$primary_rate->label = $label;
						}

						if ( method_exists( $primary_rate, 'set_cost' ) ) {
							$primary_rate->set_cost( 0 );
						} else {
							$primary_rate->cost = 0;
						}

						if ( method_exists( $primary_rate, 'set_taxes' ) ) {
							$primary_rate->set_taxes( array() );
						} else {
							$primary_rate->taxes = array();
						}
					} elseif ( is_array( $primary_rate ) ) {
						$primary_rate['label'] = $label;
						$primary_rate['cost']  = 0;
						$primary_rate['taxes'] = array();
						$rates[ $primary_rate_id ] = $primary_rate;
					}

					return array( $primary_rate_id => $primary_rate );
				}
			}
		}
		return $rates;
	},
	9999, 2
);
