<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * This disables the adjacent_post links in the header that are almost never beneficial and are very slow to compute
 */
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
