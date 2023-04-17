<?php
/**
 * DataLoader - WC_Order_Loader
 *
 * Loads Models for WooCommerce orders
 *
 * @package WPGraphQL\WooCommerce\Data\Loader
 * @since 0.0.1
 */

namespace WPGraphQL\WooCommerce\Data\Loader;

use GraphQL\Deferred;
use WPGraphQL\Data\Loader\AbstractDataLoader;
use WPGraphQL\WooCommerce\Model\Order;
use WPGraphQL\WooCommerce\Model\Refund;

/**
 * Class WC_Order_Loader
 */
class WC_Order_Loader extends AbstractDataLoader {
	protected function loadKeys(array $keys)
	{
		$result = [];

		foreach( $keys as $order_id ) {
			$order = wc_get_order( $order_id );

			if( false === $order ) {
				$result[ $order_id ] = null;
				continue;
			}

			$customer_id = null;
			$parent_id   = null;

			if( $order instanceof \WC_Order ) {
				$customer_id = $order->get_customer_id();
				if( $customer_id ) {
					$this->context->get_loader( 'wc_customer' )->buffer( [ $customer_id ] );
				}
			}
			else if( $order instanceof \WC_Order_Refund ) {
				$parent_id = $order->get_parent_id();
				$this->buffer( [ $parent_id ] );
			}

			$context = $this->context;

			/**
			 * This is a deferred function that allows us to do batch loading
			 * of dependant resources. When the Model Layer attempts to determine
			 * access control of a Post, it needs to know the owner of it, and
			 * if it's a revision, it needs the Parent.
			 *
			 * This deferred function allows for the objects to be loaded all at once
			 * instead of loading once per entity, thus reducing the n+1 problem.
			 */
			$load_dependencies = new Deferred(
				function() use ( $customer_id, $parent_id, $context ) {
					if ( ! empty( $customer_id ) ) {
						$context->get_loader( 'wc_customer' )->load( $customer_id );
					}
					if ( ! empty( $parent_id ) ) {
						$this->load( $parent_id );
					}
				}
			);

			/**
			 * Once dependencies are loaded, return the order object.
			 */
			$result[ $order_id ] = $load_dependencies->then(
				function() use ( $order ) {
					return $order instanceof \WC_Order_Refund ? new Refund( $order ) : new Order( $order );
				}
			);
		}

		return $result;
	}
}