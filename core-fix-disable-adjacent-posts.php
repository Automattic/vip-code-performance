<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Display relational links for the posts adjacent to the current post for single post pages.
 *
 * This is meant to be attached to actions like 'wp_head'. Do not call this directly in plugins or theme templates.
 * @since 3.0.0
 *
 */
function adjacent_posts_rel_link_wp_head() {
	if ( ! is_single() || is_attachment() ) {
		return;
	}
	adjacent_posts_rel_link();
}

/**
 * This disables the adjacent_post links in the header that are almost never beneficial and are very slow to compute
 */
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);