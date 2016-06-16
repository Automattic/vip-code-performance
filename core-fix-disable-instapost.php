<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Disables instapost since it is rarely used
 */
function wpcom_vip_disable_instapost() {
	remove_action( 'init', array( 'Instapost', 'init' ) );
}