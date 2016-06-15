<?php
/**
 * Performance patches for core
 */

/**
 * Adds caching to media_has_audio
 */
function wpcom_media_has_audio_cache( $current_value ) {
	$has_audio = get_transient( 'wpcom_media_has_audio' );
	if ( false === $has_audio ) {
		$has_audio = $wpdb->get_var( "
        SELECT ID
        FROM $wpdb->posts
        WHERE post_type = 'attachment'
        AND post_mime_type LIKE 'audio%'
        LIMIT 1
	" );
		if ( empty( $has_audio ) ) { // Check into return value
			set_transient( 'wpcom_media_has_audio', 'not_found' );
		} else {
			set_transient( 'wpcom_media_has_audio', $has_audio );
		}
	} elseif ( 'not_found' === $has_audio ) { // Setting to 0 instead of false so that it doesn't query again after the filter is run and we still get the false value used later
		$has_audio = 0;
	}
	return $has_audio;
}

/**
 * Adds caching to media_has_video
 */
function wpcom_media_has_video_cache( $current_value ) {
	$has_video = get_transient( 'wpcom_media_has_video' );
	if ( false === $has_video ) {
		$has_video = $wpdb->get_var( "
        SELECT ID
        FROM $wpdb->posts
        WHERE post_type = 'attachment'
        AND post_mime_type LIKE 'video%'
        LIMIT 1
	" );
		if ( empty( $has_video ) ) { // Check into return value
			set_transient( 'wpcom_media_has_video', 'not_found' );
		} else {
			set_transient( 'wpcom_media_has_video', $has_video );
		}
	} elseif ( 'not_found' === $has_video ) { // Setting to 0 instead of false so that it doesn't query again after the filter is run and we still get the false value used later
		$has_video = 0;
	}
	return $has_video;
}

/**
 * Adds caching to media_months_array
 */
function wpcom_media_months_array_cache( $current_value ) {
	global $wpdb;
	$months = get_transient( 'media_months_array' );
	if ( false === $months ){
	$months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			ORDER BY post_date DESC
			LIMIT 1
		", 'attachment' ) );
		if ( empty( $months ) ) { // Check into return value
			set_transient( 'wpcom_media_months_array', 'not_found' );
		} else {
			set_transient( 'wpcom_media_months_array', $months );
		}
	} elseif ( 'not_found' === $months ) { // Setting to empty instead of false so that it doesn't query again after the filter is run and we still get the false value used later
		$months = [];
	}
	return $months;
}

/**
 * Filter clears out transients from wpcom_media_has_audio and wpcom_media_has_video
 * Function wpcom_vip_bust_media_months_cache clears out transients from wpcom_media_months_array
 */
add_filter( 'add_attachment', 'wpcom_vip_media_clear_caches' );
function wpcom_vip_media_clear_caches( $id ) {
	$upload_post = get_post( $id );
	if ( strpos( $upload_post->post_mime_type, "video" ) === 0 ) { // Check if it starts with video
		delete_transient( 'wpcom_media_has_video' );
	} else if ( strpos( $upload_post->post_mime_type, "audio" ) === 0 ) { // Check if it starts with audio
		delete_transient( 'wpcom_media_has_audio' );
	}
}
add_action( 'add_attachment', 'wpcom_vip_bust_media_months_cache' );
function wpcom_vip_bust_media_months_cache( $post_id ) {

	// Determines what month/year the most recent attachment is
	global $wpdb;
	$months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			ORDER BY post_date DESC
			LIMIT 1
		", 'attachment' ) );

	// Simplifies by assigning the object to $months
	$months = array_shift( array_values( $months ) );

	// Compares the dates of the new and the most recent attachment
	if (
		! $months->year == get_the_time( 'Y', $post_id ) &&
		! $months->month == get_the_time( 'm', $post_id )
	) {
		// The new attachment is not in the same month/year as the most recent attachment, so the transient is refreshed
		delete_transient( 'wpcom_media_months_array' );
	}
}