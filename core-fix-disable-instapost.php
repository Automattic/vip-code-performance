<?php
/**
 * @todo: Add reasoning for this, link to core trac ticket.
 */

/**
 * Disables instapost since it is rarely used
 */
if ( function_exists('wpcom_vip_disable_instapost') ) {
	wpcom_vip_disable_instapost();
}