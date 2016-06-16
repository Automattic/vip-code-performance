<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * include_children is always defaulted to true, but it is flipped to false if it's a shared term.
 * This can cause problems when a simple 1 term query ends up being a 10+ term query
 */
add_action('pre_get_posts', 'wpcom_vip_default_include_children_false');
function wpcom_vip_default_include_children_false( $query ){
    if ( ! empty( $query['tax_query'] ) ) { // Do we have a tax query?
        if ( empty ('relation') && empty( $query['tax_query']['include_children'] ) ) { // Is it a 'simple' tax query & is include_children not set?
            $query['tax_query']['include_children'] = false; // Set it to false to improve performance
        }   //TODO: recurse thru complex tax queries?
    }
}