<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Get the timestamp of the last time any post was modified.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is just when the last post was modified. The
 * 'gmt' is when the last post was modified in GMT time.
 *
 * @since 1.2.0
 * @since 4.4.0 The `$post_type` argument was added.
 *
 * @param string $timezone  Optional. The timezone for the timestamp. See {@see get_lastpostdate()}
 *                          for information on accepted values.
 *                          Default 'server'.
 * @param string $post_type Optional. The post type to check. Default 'any'.
 * @return string The timestamp.
 */
function get_lastpostmodified( $timezone = 'server', $post_type = 'any' ) {

	// Allow us to pre-filter the return value of this in mu-plugins/wpcom-feed-cache instead of running a possibly lame query and then overwriting the result
    $pre_lastpostmodified = apply_filters( 'pre_get_lastpostmodified', false, $timezone );
    if ( false !== $pre_lastpostmodified ) { 
    	return $pre_lastpostmodified;
    }

    $lastpostmodified = _get_last_post_time( $timezone, 'modified', $post_type );
	
	$lastpostdate = get_lastpostdate($timezone);
	if ( $lastpostdate > $lastpostmodified ) {
		$lastpostmodified = $lastpostdate;
	}

/**
* Filter the date the last post was modified.
*
* @since 2.3.0
*
* @param string $lastpostmodified Date the last post was modified.
* @param string $timezone         Location to use for getting the post modified date.
*                                 See {@see get_lastpostdate()} for accepted `$timezone` values.
*/
return apply_filters( 'get_lastpostmodified', $lastpostmodified, $timezone );
}