<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Use this function to cache the comment counting in the wp menu that can be slow on sites with lots of comments
 * use like this:
 *
 * @param $post_id
 *
 * @see wp_count_comments()
 * @return bool|false|mixed|string
 */
function wpcom_vip_cache_full_comment_counts( $counts = false , $post_id = 0 ){
	//We are only caching the global comment counts for now since those are often in the millions while the per page one is usually more reasonable.
	if ( $post_id !== 0 ){
		return $counts;
	}
	$cache_key = "vip-comments-{$post_id}";
	$stats_object = wp_cache_get( $cache_key );

	//retrieve comments in the same way wp_count_comments() does
	if ( false === $stats_object ){
		$stats = get_comment_count( $post_id );
		$stats['moderated'] = $stats['awaiting_moderation'];
		unset( $stats['awaiting_moderation'] );
		$stats_object = (object) $stats;

		wp_cache_set( $cache_key, $stats_object, 'default', 30 * MINUTE_IN_SECONDS );
	}

	return $stats_object;

}

/**
 * Improves performance of all the wp-admin pages that load comment counts in the menu. This caches them for 30 minutes.
 * It does not impact the per page comment count, only the total comment count that shows up in the admin menu.
 */
function wpcom_vip_enable_cache_full_comment_counts() {
	add_filter( 'wp_count_comments', 'wpcom_vip_cache_full_comment_counts', 10, 2 );
}