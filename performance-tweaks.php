<?php
/**
 * Performance patches for core
 */


//Allow sites and plugins to disable each enhancements on a case by case basis.
if ( ! apply_filter( 'wpcom_vip_disable_media_query_cache ', false ) ){
	include_once 'core-fix-media-query-caching.php';
}