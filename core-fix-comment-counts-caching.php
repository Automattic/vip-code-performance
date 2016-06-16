<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Improves performance of all the wp-admin pages that load comment counts in the menu. This caches them for 30 minutes.
 * It does not impact the per page comment count, only the total comment count that shows up in the admin menu.
 */
if ( function_exists('wpcom_vip_enable_cache_full_comment_counts') ) { 
	wpcom_vip_enable_cache_full_comment_counts();
}