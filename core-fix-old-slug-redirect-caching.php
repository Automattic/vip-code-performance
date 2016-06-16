<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Caches the results of the _wp_old_slug meta query which can be expensive
 */
if ( function_exists( 'wpcom_vip_enable_old_slug_redirect_caching' ) ) {
	wpcom_vip_enable_old_slug_redirect_caching();
}