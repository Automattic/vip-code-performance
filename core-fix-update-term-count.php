<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
* Update the custom taxonomies' term counts when a post's status is changed. For example, default posts term counts (for custom taxonomies) don't include private / draft posts.
*
* @access private
* @param string $new_status
* @param string $old_status
* @param object $post
* @since 3.3.0
*/
function _update_term_count_on_transition_post_status( $new_status, $old_status, $post ) {
	global $wpdb;
	$tc_blog_id = 24588526;
	if ( $wpdb->blogid == $tc_blog_id ) $tc_before = microtime(true);
	if ( $wpdb->blogid == $tc_blog_id ) {
        $tc_get_times = array();
        $tc_update_times = array();
        $tc_coap_times = array();
	}
	// Update counts for the post's terms.
	foreach ( (array) get_object_taxonomies( $post->post_type ) as $taxonomy ) {	       
		if ( $wpdb->blogid == $tc_blog_id ) $tc_before_get = microtime(true);
        $tt_ids = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'tt_ids' ) );
        if ( $wpdb->blogid == $tc_blog_id ) $tc_after_get = microtime(true);
        if ( $wpdb->blogid == $tc_blog_id ) $tc_get_times[] = ($tc_after_get - $tc_before_get);
        if ( $wpdb->blogid == $tc_blog_id ) $tc_before_update = microtime(true);
        wp_update_term_count( $tt_ids, $taxonomy );
        if ( $wpdb->blogid == $tc_blog_id ) $tc_after_update = microtime(true);
        if ( $wpdb->blogid == $tc_blog_id ) $tc_update_times[] = ($tc_after_update - $tc_before_update);
        global $tc_coap_time;
        if ( $wpdb->blogid == $tc_blog_id ) $tc_coap_times[] = (int)$tc_coap_time;
	}
	if ( $wpdb->blogid == $tc_blog_id ) $tc_after = microtime(true);
	if ( $wpdb->blogid == $tc_blog_id && 'post' == $post->post_type ) {
        $tc_total = ($tc_after - $tc_before);
        $tc_update = array_sum($tc_update_times);
        $tc_updates = implode(", ", $tc_update_times);
        $tc_update_ratio = $tc_update / $tc_total;
        $tc_get = array_sum($tc_get_times);
        $tc_gets = implode(", ", $tc_get_times);
        $tc_get_ratio = $tc_get / $tc_total;
        $tc_coap = array_sum($tc_coap_times);
        $tc_coaps = implode(", ", $tc_coap_times);
        $tc_coap_ratio = $tc_coap / $tc_total;
        $tc_report = "total: $tc_total\n\nget: $tc_get ($tc_get_ratio)\n$tc_gets\n\nupdate: $tc_update ($tc_update_ratio)\n$tc_updates\n\ncoap: $tc_coap ($tc_coap_ratio)\n$tc_coaps";
        $tc_post_id = $post->ID;
        wp_mail( 'nikolay@automattic.com', "TechCrunch updated $tc_post_id", $tc_report );
	}
}

/**
 * Update the custom taxonomies' term counts when a post's status is changed && when posts are greater than 2000.
 *
 * For example, default posts term counts (for custom taxonomies) don't include
 * private / draft posts.
 *
 * @since 3.3.0
 * @access private
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
if ( count( $posts ) > 2000 ) {
	_update_term_count_on_transition_post_status( $new_status, $old_status, $post );
}