<?php
define( 'MAX_QUANTITY', 3 );

add_filter(
	'woocommerce_add_to_cart_validation',
	'validate_discounted_prdct_qty_limitation',
	10,
	3
);
/**
 * Hooked functionality to limit the discounted product quantity to checkout.
 *
 * @param bool $passed      Validation.
 * @param int  $product_id  Product ID.
 * @param int  $quantity    Product quantity.
 *
 * @return bool Return true if quantity is not exceeded else false.
 */
function validate_discounted_prdct_qty_limitation( $passed, $product_id, $quantity ) {
	$product_object = wc_get_product( $product_id );

	$parent_id = $product_object->get_parent_id();
	if ( ! empty( $parent_id ) ) {
		$product_id = $parent_id;
	}

	return validate_product_qty( $product_id, $quantity, 'add' );
}

add_filter(
	'woocommerce_update_cart_validation',
	'on_update_cart_limit_qty',
	10,
	4
);
/**
 * Hooked functionality to check limit of the discounted product quantity in cart page on updating the cart.
 *
 * @param bool   $passed        Validation status.
 * @param string $cart_item_key Cart internal id.
 * @param Array  $values        Updated cart item array.
 * @param int    $quantity      Product quantity.
 *
 * @return bool Return true if quantity is not exceeded else false.
 */
function on_update_cart_limit_qty( $passed, $cart_item_key, $values, $quantity ) {
	return validate_product_qty( $values['product_id'], $quantity, 'update' );
}

/**
 * Validation function to limit the quantity.
 *
 * @param int    $product_id  Product ID.
 * @param int    $quantity    Product quantity.
 * @param string $action_type Type of the action. Allowed 'add' or 'update'.
 *
 * @return bool Return true if quantity is not exceeded else false.
 */
function validate_product_qty( $product_id, $quantity, $action_type ) {
	if ( is_discounted_prdct_qty_exceeded( $product_id, $quantity, $action_type ) ) {

		$output_string = wp_sprintf(
			'%s %d.',
			__( 'Discounted product maximum quantity is ' ),
			MAX_QUANTITY
		);

		wc_add_notice( $output_string, 'notice' );
		return false;
	}

	return true;
}

/**
 * Helper function to check discounted product add to cart limit reached.
 *
 * @param int    $product_id  Product ID.
 * @param int    $quantity    Product quantity.
 * @param string $action_type Type of the action. Allowed 'add' or 'update'.
 *
 * @return bool Return true if quantity exceeded else false.
 */
function is_discounted_prdct_qty_exceeded( $product_id, $quantity, $action_type ) {

	$product_obj   = wc_get_product( $product_id );
	$regular_price = $product_obj->get_regular_price();
	$sale_price    = $product_obj->get_sale_price();

	$cart_id              = WC()->cart->generate_cart_id( $product_id );
	$find_product_in_cart = WC()->cart->find_product_in_cart( $cart_id );

	if ( ! empty( $sale_price ) &&
		(float) $sale_price < (float) $regular_price ) { // Validate is discounted product.

		if ( 'add' === $action_type &&
			! empty( $find_product_in_cart ) ) {

			$cart_item = wc()->cart->get_cart_item( $cart_id );
			$quantity  = $cart_item['quantity'] + $quantity;
		}

		if ( $quantity > MAX_QUANTITY ) {

			return true;
		}
	}

	return false;
}
