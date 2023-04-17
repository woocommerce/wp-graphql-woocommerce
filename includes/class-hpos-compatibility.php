<?php
/**
 * Defines utility functions to be used when expliicit HPOS compatibility check is needed.
 *
 * @package WPGraphQL\WooCommerce
 */

namespace WPGraphQL\WooCommerce;

/**
 * Trait HPOS_Compatibility
 */
trait HPOS_Compatibility {
	/**
	 * Determine if the HPOS feature is enabled and the new tables are currently authoritative for storing orders.
	 * 
	 * @return bool True if the HPOS feature is enabled and the new tables are currently authoritative, false if either the feature is disabled or the posts table is currently authoritative.
	 */
	public static function hpos_is_enabled() {
		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
}
