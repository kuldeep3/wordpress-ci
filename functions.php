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
