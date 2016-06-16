<?php
/**
 * Performance patches for core
 */


// Allow sites and plugins to disable each enhancement on a case by case basis
if ( ! apply_filter( 'wpcom_vip_disable_media_query_cache', false ) ) {
	include_once 'core-fix-media-query-caching.php';
}

if ( ! apply_filter( 'wpcom_vip_enable_comment_counts_cache', false ) ) {
	include_once 'core-fix-comment-counts-caching.php';
}

if ( ! apply_filter( 'wpcom_vip_disable_adjacent_posts', false ) ) {
	include_once 'core-fix-disable-adjacent-posts.php';
}

if ( ! apply_filter( 'wpcom_vip_disable_instapost', false ) ) {
	include_once 'core-fix-disable-instapost.php';
}

if ( ! apply_filter( 'wpcom_vip_enable_old_slug_redirect_cache', false ) ) {
	include_once 'core-fix-old-slug-redirect-caching.php';
}