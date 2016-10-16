<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */
/**
 * 
 * This fix requires a core patch that would add
$lastpostmodified = apply_filters( 'pre_get_lastpostmodified', false, $timezone, $post_type );
if ( false !== $lastpostmodified ) {
		return $lastpostmodified;
}
* to the begining of get_lastpostmodified() in /wp-includes/post-functions.php
* 
*/


function wpcom_invalidate_feed_cache() {
	update_option( 'feed_invalidated', time() );
}

add_action( 'update_option_blog_public', 'wpcom_invalidate_feed_cache' );

// Invalidate the feed when feed related settings change
add_action( 'update_option_posts_per_rss', 'wpcom_invalidate_feed_cache' );
add_action( 'update_option_rss_use_excerpt', 'wpcom_invalidate_feed_cache' );
add_action( 'update_option_enhanced_feeds', 'wpcom_invalidate_feed_cache' );


function wpcom_get_lastpostmodified( $date, $timezone ) {
	if ( !$last = get_option( 'feed_invalidated' ) )
		return $date;

	// Get $date in GMT
	switch ( $timezone ) {
	case 'gmt' :
		$offset = 0;
		break;
	case 'blog' :
		$offset = 3600 * -1 * get_option( 'gmt_offset' );
		break;
	case 'server' :
		$offset = -1 * date( 'Z' );
		break;
	}

	$date = strtotime( "{$date}+0000 {$offset} seconds" );
	if ( $date < $last )
		$date = $last;

	// Put $date in $timezone
	$date -= $offset;

	return gmdate( 'Y-m-d H:i:s', $date );
}
add_filter( 'pre_get_lastpostmodified', 'wpcom_get_lastpostmodified', 10, 2 );

add_action( 'transition_post_status', 'wpcom_maybe_invalidate_feed_cache_on_post_transition', 10, 3 );
function wpcom_maybe_invalidate_feed_cache_on_post_transition( $new_status, $old_status, $post ) {
	if ( ! in_array( 'publish', array( $old_status, $new_status ) ) )
		return;

	if ( ! in_array( $post->post_type, get_post_types( array( 'public' => true ) ) ) )
		return;
	wpcom_invalidate_post_data( $post->ID );
}


