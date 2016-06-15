<?php


/**
 * Cached version of get_category_by_slug.
 *
 * @param string $slug Category slug
 * @return object|null|bool Term Row from database. Will return null if $slug doesn't match a term. If taxonomy does not exist then false will be returned.
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
if ( ! function_exists( 'wpcom_vip_get_category_by_slug' ) ) {
	function wpcom_vip_get_category_by_slug( $slug ) {
		return wpcom_vip_get_term_by( 'slug', $slug, 'category' );
	}
}

/**
 * Cached version of get_term_by.
 *
 * Many calls to get_term_by (with name or slug lookup) across on a single pageload can easily add up the query count.
 * This function helps prevent that by adding a layer of caching.
 *
 * @param string $field Either 'slug', 'name', or 'id'
 * @param string|int $value Search for this term value
 * @param string $taxonomy Taxonomy Name
 * @param string $output Optional. Constant OBJECT, ARRAY_A, or ARRAY_N
 * @param string $filter Optional. Default is 'raw' or no WordPress defined filter will applied.
 * @return mixed|null|bool Term Row from database in the type specified by $filter. Will return false if $taxonomy does not exist or $term was not found.
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
if ( ! function_exists( 'wpcom_vip_get_term_by' ) ) {
	function wpcom_vip_get_term_by( $field, $value, $taxonomy, $output = OBJECT, $filter = 'raw' ) {
		// ID lookups are cached
		if ( 'id' == $field )
			return get_term_by( $field, $value, $taxonomy, $output, $filter );

		$cache_key = $field . '|' . $taxonomy . '|' . md5( $value );
		$term_id = wp_cache_get( $cache_key, 'get_term_by' );

		if ( false === $term_id ) {
			$term = get_term_by( $field, $value, $taxonomy );
			if ( $term && ! is_wp_error( $term ) )
				wp_cache_set( $cache_key, $term->term_id, 'get_term_by' );
			else
				wp_cache_set( $cache_key, 0, 'get_term_by' ); // If we get an invalid value, cache it anyway
		} else {
			$term = get_term( $term_id, $taxonomy, $output, $filter );
		}

		if ( is_wp_error( $term ) )
			$term = false;

		return $term;
	}
}

/**
 * Properly clear wpcom_vip_get_term_by() cache when a term is updated
 */
add_action( 'edit_terms', 'wp_flush_get_term_by_cache', 10, 2 );
function wp_flush_get_term_by_cache( $term_id, $taxonomy ){
	$term = get_term_by( 'id', $term_id, $taxonomy );
	if ( ! $term ) {
		return;
	}
	foreach( array( 'name', 'slug' ) as $field ) {
		$cache_key = $field . '|' . $taxonomy . '|' . md5( $term->$field );
		$cache_group = 'get_term_by';
		wp_cache_delete( $cache_key, $cache_group );
	}
}

/**
 * Cached version of term_exists()
 *
 * Term exists calls can pile up on a single pageload.
 * This function adds a layer of caching to prevent lots of queries.
 *
 * @param int|string $term The term to check can be id, slug or name.
 * @param string $taxonomy The taxonomy name to use
 * @param int $parent Optional. ID of parent term under which to confine the exists search.
 * @return mixed Returns null if the term does not exist. Returns the term ID
 *               if no taxonomy is specified and the term ID exists. Returns
 *               an array of the term ID and the term taxonomy ID the taxonomy
 *               is specified and the pairing exists.
 */
if ( ! function_exists( 'wpcom_vip_term_exists' ) ) {
	function wpcom_vip_term_exists( $term, $taxonomy = '', $parent = null ) {
		// If $parent is not null, skip the cache
		if ( null !== $parent ){
			return term_exists( $term, $taxonomy, $parent );
		}

		if ( ! empty( $taxonomy ) ){
			$cache_key = $term . '|' . $taxonomy;
		}else{
			$cache_key = $term;
		}

		$cache_value = wp_cache_get( $cache_key, 'term_exists' );

		// term_exists frequently returns null, but (happily) never false
		if ( false  === $cache_value ) {
			$term_exists = term_exists( $term, $taxonomy );
			wp_cache_set( $cache_key, $term_exists, 'term_exists' );
		}else{
			$term_exists = $cache_value;
		}

		if ( is_wp_error( $term_exists ) )
			$term_exists = null;

		return $term_exists;
	}
}

/**
 * Properly clear wpcom_vip_term_exists() cache when a term is updated
 */
add_action( 'delete_term', 'wp_flush_term_exists', 10, 4 );
function wp_flush_term_exists( $term, $tt_id, $taxonomy, $deleted_term ){
	foreach( array( 'term_id', 'name', 'slug' ) as $field ) {
		$cache_key = $deleted_term->$field . '|' . $taxonomy ;
		$cache_group = 'term_exists';
		wp_cache_delete( $cache_key, $cache_group );
	}
}

/**
 * Optimized version of get_term_link that adds caching for slug-based lookups.
 *
 * Returns permalink for a taxonomy term archive, or a WP_Error object if the term does not exist.
 *
 * @param int|string|object $term The term object / term ID / term slug whose link will be retrieved.
 * @param string $taxonomy The taxonomy slug. NOT required if you pass the term object in the first parameter
 *
 * @return string|WP_Error HTML link to taxonomy term archive on success, WP_Error if term does not exist.
 */
if ( ! function_exists( 'wpcom_vip_get_term_link' ) ) {
	function wpcom_vip_get_term_link( $term, $taxonomy = null ) {
		// ID- or object-based lookups already result in cached lookups, so we can ignore those
		if ( is_numeric( $term ) || is_object( $term ) ) {
			return get_term_link( $term, $taxonomy );
		}

		$term_object = wpcom_vip_get_term_by( 'slug', $term, $taxonomy );
		return get_term_link( $term_object );
	}
}

/**
 * Cached version of get_page_by_title so that we're not making unnecessary SQL all the time
 *
 * @param string $page_title Page title
 * @param string $output Optional. Output type; OBJECT*, ARRAY_N, or ARRAY_A.
 * @param string $post_type Optional. Post type; default is 'page'.
 * @return WP_Post|null WP_Post on success or null on failure
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
if ( ! function_exists( 'wpcom_vip_get_page_by_title' ) ) {
	function wpcom_vip_get_page_by_title( $title, $output = OBJECT, $post_type = 'page' ) {
		$cache_key = $post_type . '_' . sanitize_key( $title );
		$page_id = wp_cache_get( $cache_key, 'get_page_by_title' );

		if ( $page_id === false ) {
			$page = get_page_by_title( $title, OBJECT, $post_type );
			$page_id = $page ? $page->ID : 0;
			wp_cache_set( $cache_key, $page_id, 'get_page_by_title' ); // We only store the ID to keep our footprint small
		}

		if ( $page_id )
			return get_page( $page_id, $output );

		return null;
	}
}

/**
 * Cached version of get_page_by_path so that we're not making unnecessary SQL all the time
 *
 * @param string $page_path Page path
 * @param string $output Optional. Output type; OBJECT*, ARRAY_N, or ARRAY_A.
 * @param string $post_type Optional. Post type; default is 'page'.
 * @return WP_Post|null WP_Post on success or null on failure
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
if ( ! function_exists( 'wpcom_vip_get_page_by_path' ) ) {
	function wpcom_vip_get_page_by_path( $page_path, $output = OBJECT, $post_type = 'page' ) {
		if ( is_array( $post_type ) )
			$cache_key = sanitize_key( $page_path ) . '_' . md5( serialize( $post_type ) );
		else
			$cache_key = $post_type . '_' . sanitize_key( $page_path );

		$page_id = wp_cache_get( $cache_key, 'get_page_by_path' );

		if ( $page_id === false ) {
			$page = get_page_by_path( $page_path, $output, $post_type );
			$page_id = $page ? $page->ID : 0;
			if ( $page_id ===0 ){
				wp_cache_set( $cache_key, $page_id, 'get_page_by_path', ( 1 * HOUR_IN_SECONDS + mt_rand(0, HOUR_IN_SECONDS) )  ); // We only store the ID to keep our footprint small
			}else{
				wp_cache_set( $cache_key, $page_id, 'get_page_by_path', ( 12 * HOUR_IN_SECONDS + mt_rand(0, HOUR_IN_SECONDS) )); // We only store the ID to keep our footprint small
			}
		}

		if ( $page_id )
			return get_page( $page_id, $output );

		return null;
	}
}

/**
 * Flush the cache for published pages so we don't end up with stale data
 *
 * @param string $new_status The post's new status
 * @param string $old_status The post's previous status
 * @param WP_Post $post The post
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
if ( ! function_exists( 'wpcom_vip_flush_get_page_by_title_cache' ) ) {
	function wpcom_vip_flush_get_page_by_title_cache( $new_status, $old_status, $post ) {
		if ( 'publish' == $new_status || 'publish' == $old_status )
			wp_cache_delete( $post->post_type . '_' . sanitize_key( $post->post_title ), 'get_page_by_title' );
	}
}
add_action( 'transition_post_status', 'wpcom_vip_flush_get_page_by_title_cache', 10, 3 );

/**
 * Flush the cache for published pages so we don't end up with stale data
 *
 * @param string  $new_status The post's new status
 * @param string  $old_status The post's previous status
 * @param WP_Post $post       The post
 *
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
if ( ! function_exists( 'wpcom_vip_flush_get_page_by_path_cache' ) ) {
	function wpcom_vip_flush_get_page_by_path_cache( $new_status, $old_status, $post ) {
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			$page_path = get_page_uri( $post->ID );
			wp_cache_delete( $post->post_type . '_' . sanitize_key( $page_path ), 'get_page_by_path' );
		}
	}
}
add_action( 'transition_post_status', 'wpcom_vip_flush_get_page_by_path_cache', 10, 3 );

/**
 * Cached version of url_to_postid, which can be expensive.
 *
 * Examine a url and try to determine the post ID it represents.
 *
 * @param string $url Permalink to check.
 * @return int Post ID, or 0 on failure.
 */
if ( ! function_exists( 'wpcom_vip_url_to_postid' ) ) {
	function wpcom_vip_url_to_postid( $url ) {
		/*
		* Can only run after init, since home_url() has not been filtered to the mapped domain prior to that,
		* which will cause url_to_postid to fail
		* @see https://vip.wordpress.com/documentation/vip-development-tips-tricks/home_url-vs-site_url/
		*/
		if ( ! did_action( 'init' ) ) {
			_doing_it_wrong( 'wpcom_vip_url_to_postid', 'wpcom_vip_url_to_postid must be called after the init action, as home_url() has not yet been filtered', '' );

			return 0;
		}

		// Sanity check; no URLs not from this site
		if ( parse_url( $url, PHP_URL_HOST ) != wpcom_vip_get_home_host() )
			return 0;

		$cache_key = md5( $url );
		$post_id = wp_cache_get( $cache_key, 'url_to_postid' );

		if ( false === $post_id ) {
			$post_id = url_to_postid( $url ); // Returns 0 on failure, so need to catch the false condition
			wp_cache_set( $cache_key, $post_id, 'url_to_postid', 3 * HOUR_IN_SECONDS );
		}

		return $post_id;
	}
}
add_action( 'transition_post_status', function( $new_status, $old_status, $post ) {
	if ( 'publish' != $new_status && 'publish' != $old_status )
		return;

	$url = get_permalink( $post->ID );
	wp_cache_delete( md5( $url ), 'url_to_postid' );
}, 10, 3 );

/**
 * Cached version of wp_old_slug_redirect.
 *
 * Cache the results of the _wp_old_slug meta query, which can be expensive.
 * @deprecated use wpcom_vip_wp_old_slug_redirect instead
 */
if ( ! function_exists( 'wpcom_vip_old_slug_redirect' ) ) {
	function wpcom_vip_old_slug_redirect() {
		global $wp_query;
		if ( is_404() && '' != $wp_query->query_vars['name'] ) :
			global $wpdb;

			// Guess the current post_type based on the query vars.
			if ( get_query_var('post_type') )
				$post_type = get_query_var('post_type');
			elseif ( !empty($wp_query->query_vars['pagename']) )
				$post_type = 'page';
			else
				$post_type = 'post';

			if ( is_array( $post_type ) ) {
				if ( count( $post_type ) > 1 )
					return;
				$post_type = array_shift( $post_type );
			}

			// Do not attempt redirect for hierarchical post types
			if ( is_post_type_hierarchical( $post_type ) )
				return;

			$query = $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta, $wpdb->posts WHERE ID = post_id AND post_type = %s AND meta_key = '_wp_old_slug' AND meta_value = %s", $post_type, $wp_query->query_vars['name']);

			// If year, monthnum, or day have been specified, make our query more precise
			// Just in case there are multiple identical _wp_old_slug values
			if ( '' != $wp_query->query_vars['year'] )
				$query .= $wpdb->prepare(" AND YEAR(post_date) = %d", $wp_query->query_vars['year']);
			if ( '' != $wp_query->query_vars['monthnum'] )
				$query .= $wpdb->prepare(" AND MONTH(post_date) = %d", $wp_query->query_vars['monthnum']);
			if ( '' != $wp_query->query_vars['day'] )
				$query .= $wpdb->prepare(" AND DAYOFMONTH(post_date) = %d", $wp_query->query_vars['day']);

			$cache_key = md5( serialize( $query ) );

			if ( false === $id = wp_cache_get( $cache_key, 'wp_old_slug_redirect' ) ) {
				$id = (int) $wpdb->get_var($query);

				wp_cache_set( $cache_key, $id, 'wp_old_slug_redirect', 5 * MINUTE_IN_SECONDS );
			}

			if ( ! $id )
				return;

			$link = get_permalink($id);

			if ( !$link )
				return;

			wp_redirect( $link, 301 ); // Permanent redirect
			exit;
		endif;
	}
}

/**
 * Cached version of count_user_posts, which is uncached but doesn't always need to hit the db
 *
 * count_user_posts is generally fast, but it can be easy to end up with many redundant queries
 * if it's called several times per request. This allows bypassing the db queries in favor of
 * the cache
 */
if ( ! function_exists( 'wpcom_vip_count_user_posts' ) ) {
	function wpcom_vip_count_user_posts( $user_id ) {
		if ( ! is_numeric( $user_id ) ) {
			return 0;
		}

		$cache_key = 'vip_' . (int) $user_id;
		$cache_group = 'user_posts_count';

		if ( false === ( $count = wp_cache_get( $cache_key, $cache_group ) ) ) {
			$count = count_user_posts( $user_id );

			wp_cache_set( $cache_key, $count, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		return $count;
	}
}

/*
 * Cached version of wp_get_nav_menu_object
 *
 * Many calls to get_term_by (with name or slug lookup as used inside the wp_get_nav_menu_object) across on a single pageload can easily add up the query count.
 * This function helps prevent that by taking advantage of wpcom_vip_get_term_by function which adds a layer of caching.
 *
 * @param string $menu Menu ID, slug, or name.
 * @uses wpcom_vip_get_term_by
 * @return mixed false if $menu param isn't supplied or term does not exist, menu object if successful.
 */
if ( ! function_exists( 'wpcom_vip_get_nav_menu_object' ) ) {
	function wpcom_vip_get_nav_menu_object( $menu ) {
		if ( ! $menu )
			return false;

		$menu_obj = get_term( $menu, 'nav_menu' );

		if ( ! $menu_obj ) {
			$menu_obj = wpcom_vip_get_term_by( 'slug', $menu, 'nav_menu' );
		}

		if ( ! $menu_obj ) {
			$menu_obj = wpcom_vip_get_term_by( 'name', $menu, 'nav_menu' );
		}

		if ( ! $menu_obj ) {
			$menu_obj = false;
		}

		return $menu_obj;
	}
}

/**
 * Require the Stampedeless_Cache class for use in our helper functions below.
 *
 * The Stampedeless_Cache helps prevent cache stampedes by internally varying the cache
 * expiration slightly when creating a cache entry in an effort to avoid multiple keys
 * expiring simultaneously and allowing a single request to regenerate the cache shortly
 * before it's expiration.
 */
if( function_exists( 'require_lib' ) && defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV )
	require_lib( 'class.stampedeless-cache' );

/**
 * Drop in replacement for wp_cache_set().
 *
 * Wrapper for the WPCOM Stampedeless_Cache class.
 *
 * @param string $key Cache key.
 * @param string|int|array|object $value Data to store in the cache.
 * @param string $group Optional. Cache group.
 * @param int $expiration Optional. Cache TTL in seconds.
 * @return bool This function always returns true.
 */
if ( ! function_exists( 'wpcom_vip_cache_set' ) ) {
	function wpcom_vip_cache_set( $key, $value, $group = '', $expiration = 0 ) {
		if( ! class_exists( 'Stampedeless_Cache' ) )
			return wp_cache_set( $key, $value, $group, $expiration );

		$sc = new Stampedeless_Cache( $key, $group );
		$sc->set( $value, $expiration );

		return true;
	}
}

/**
 * Drop in replacement for wp_cache_get().
 *
 * Wrapper for the WPCOM Stampedeless_Cache class.
 *
 * @param string $key Cache key.
 * @param string $group Optional. Cache group.
 * @return mixed Returns false if failing to retrieve cache entry or the cached data otherwise.
 */
if ( ! function_exists( 'wpcom_vip_cache_get' ) ) {
	function wpcom_vip_cache_get( $key, $group = '' ) {
		if( ! class_exists( 'Stampedeless_Cache' ) )
			return wp_cache_get( $key, $group );

		$sc = new Stampedeless_Cache( $key, $group );

		return $sc->get();
	}
}

/**
 * Drop in replacement for wp_cache_delete().
 *
 * Wrapper for WPCOM Stampedeless_Cache class.
 *
 * @param string $key Cache key.
 * @param string $group Optional. Cache group.
 * @return bool True on successful removal, false on failure.
 */
if ( ! function_exists( 'wpcom_vip_cache_delete' ) ) {
	function wpcom_vip_cache_delete( $key, $group = '' ) {
		// Delete cache itself
		$deleted = wp_cache_delete( $key, $group );

		if ( class_exists( 'Stampedeless_Cache' ) ) {
			// Delete lock
			$lock_key = $key . '_lock';
			wp_cache_delete( $lock_key, $group );
		}

		return $deleted;
	}
}

/**
 * Retrieve adjacent post.
 *
 * Can either be next or previous post. The logic for excluding terms is handled within PHP, for performance benefits.
 * Props to Elliott Stocks
 *
 * @global wpdb $wpdb
 *
 * @param bool         $in_same_term   Optional. Whether post should be in a same taxonomy term. Note - only the first term will be used from wp_get_object_terms().
 * @param int 	       $excluded_term  Optional. The term to exclude.
 * @param bool         $previous       Optional. Whether to retrieve previous post.
 * @param string       $taxonomy       Optional. Taxonomy, if $in_same_term is true. Default 'category'.
 *
 * @return null|string|WP_Post Post object if successful. Null if global $post is not set. Empty string if no corresponding post exists.
 */
if ( ! function_exists( 'wpcom_vip_get_adjacent_post' ) ) {
	function wpcom_vip_get_adjacent_post( $in_same_term = false, $excluded_terms = '', $previous = true, $taxonomy = 'category', $adjacent = '' ) {
		global $wpdb;
		if ( ( ! $post = get_post() ) || ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}
		$join = '';
		$where = '';
		$current_post_date = $post->post_date;

		if ( $in_same_term ) {
			if ( is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
				$term_array = get_the_terms( $post->ID, $taxonomy );
				if ( ! empty( $term_array ) && ! is_wp_error( $term_array ) ) {
					$term_array_ids = wp_list_pluck( $term_array, 'term_id' );
					// Remove any exclusions from the term array to include.
					$excluded_terms = explode( ',', $excluded_terms );
					if ( ! empty( $excluded_terms ) ){
						$term_array_ids = array_diff( $term_array_ids, (array) $excluded_terms );
					}
					if ( ! empty ( $term_array_ids ) ){
						$term_array_ids = array_map( 'intval', $term_array_ids );
						$term_id_to_search = array_pop( $term_array_ids ); // Only allow for a single term to be used. picked pseudo randomly
					}else{
						$term_id_to_search = false;
					}

					$term_id_to_search = apply_filters( 'wpcom_vip_limit_adjacent_post_term_id', $term_id_to_search, $term_array_ids, $excluded_terms, $taxonomy, $previous );

					if ( ! empty( $term_id_to_search ) ){ // Allow filters to short circuit by returning a empty like value
						$join = " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id"; //Only join if we are sure there is a term
						$where = $wpdb->prepare( "AND tt.taxonomy = %s AND tt.term_id IN (%d)  ", $taxonomy,$term_id_to_search ); //
					}
				}
			}
		}

		$op = $previous ? '<' : '>';
		$order = $previous ? 'DESC' : 'ASC';
		$limit = 1;
		// We need 5 posts so we can filter the excluded term later on
		if ( ! empty ( $excluded_term ) ) {
			$limit = 5;
		}
		$sort  = "ORDER BY p.post_date $order LIMIT $limit";
		$where = $wpdb->prepare( "WHERE p.post_date $op %s AND p.post_type = %s AND p.post_status = 'publish' $where", $current_post_date, $post->post_type );
		$query = "SELECT p.ID FROM $wpdb->posts AS p $join $where $sort";

		$found_post = ''; // Blank instead of false so not found is cached
		$query_key = 'wpcom_vip_adjacent_post_' . md5( $query );
		$cached_result = wp_cache_get( $query_key );

		if( "not found" === $cached_result){
			return false;
		} else if ( false !== $cached_result ) {
			return get_post( $cached_result );
		}

		if ( empty ( $excluded_term ) ) {
			$result = $wpdb->get_var( $query );
		} else {
			$result = $wpdb->get_results( $query );
		}

		// Find the first post which doesn't have an excluded term
		if ( ! empty ( $excluded_term ) ) {
			foreach ( $result as $result_post ) {
				$post_terms = get_the_terms( $result_post, $taxonomy );
				$terms_array = wp_list_pluck( $post_terms, 'term_id' );
				if ( ! in_array( $excluded_term, $terms_array ) ) {
					$found_post = $result_post->ID;
					break;
				}
			}
		} else {
			$found_post = $result;
		}

		/**
		 * If the post isn't found, lets cache a value we'll check against
		 * Adds some variation in the caching so if a site is being crawled all the caches don't get created all the time
		 */
		if ( empty( $found_post ) ){
			wp_cache_set( $query_key, "not found", 'default', 15 * MINUTE_IN_SECONDS + rand( 0, 15 * MINUTE_IN_SECONDS ) );
			return false;
		}

		wp_cache_set( $query_key, $found_post, 'default', 6 * HOUR_IN_SECONDS + rand( 0, 2 * HOUR_IN_SECONDS ) );
		$found_post = get_post( $found_post );

		return $found_post;
	}
}

if ( ! function_exists( 'wpcom_vip_attachment_url_to_postid' ) ) {
	function wpcom_vip_attachment_url_to_postid( $url ){

		$id = wp_cache_get( "wpcom_vip_attachment_url_post_id_". md5( $url ) );
		if ( false === $id ){
			$id = attachment_url_to_postid( $url );
			if ( empty( $id ) ){
				wp_cache_set( "wpcom_vip_attachment_url_post_id_". md5( $url ) , 'not_found', 'default', 12 * HOUR_IN_SECONDS + mt_rand(0, 4 * HOUR_IN_SECONDS ) );
			}else {
				wp_cache_set( "wpcom_vip_attachment_url_post_id_". md5( $url ) , $id, 'default', 24 * HOUR_IN_SECONDS + mt_rand(0, 12 * HOUR_IN_SECONDS ) );
			}
		} else if( 'not_found' === $id ){
			return false;
		}
		return $id;
	}
}

if ( ! function_exists( 'wpcom_vip_enable_old_slug_redirect_caching' ) ) {
	function wpcom_vip_enable_old_slug_redirect_caching(){
		add_action('template_redirect', 'wpcom_vip_wp_old_slug_redirect', 8 );
	}
}

/**
 * This works by first looking in the cache to see if there is a value saved based on the name query var.
 * If one is found, redirect immediately. If nothing is found, including that there is no already cached "not_found" value we then add a hook to old_slug_redirect_url so that when the 'real' wp_old_slug_redirect is run it will store the value in the cache @see wpcom_vip_set_old_slug_redirect_cache().
 * If we found a not_found we remove the template_redirect so the slow query is not run.
 */
if ( ! function_exists( 'wpcom_vip_enable_old_slug_redirect_caching' ) ) {
	function wpcom_vip_wp_old_slug_redirect(){
		global $wp_query;
		if ( is_404() && '' !== $wp_query->query_vars['name'] ) {

			$redirect = wp_cache_get('old_slug'. $wp_query->query_vars['name']  );

			if ( false === $redirect ){
				add_filter('old_slug_redirect_url', 'wpcom_vip_set_old_slug_redirect_cache');
				// If an old slug is not found the function returns early and does not apply the old_slug_redirect_url filter. so we will set the cache for not found and if it is found it will be overwritten later in wpcom_vip_set_old_slug_redirect_cache()
				wp_cache_set( 'old_slug'. $wp_query->query_vars['name'], 'not_found', 'default', 12 * HOUR_IN_SECONDS + mt_rand(0, 12 * HOUR_IN_SECONDS ) );
			} elseif ( 'not_found' === $redirect ){
				// wpcom_vip_set_old_slug_redirect_cache() will cache 'not_found' when a url is not found so we don't keep hammering the database
				remove_action( 'template_redirect', 'wp_old_slug_redirect' );
				return;
			} else {
				wp_redirect( $redirect, 301 ); // This is kept to not safe_redirect to match the functionality of wp_old_slug_redirect
				exit;
			}
		}
	}
}
if ( ! function_exists( 'wpcom_vip_set_old_slug_redirect_cache' ) ) {
	function wpcom_vip_set_old_slug_redirect_cache( $link ){
		global $wp_query;
		if ( ! empty( $link ) ){
			wp_cache_set( 'old_slug'. $wp_query->query_vars['name'], $link, 'default', 7 * DAY_IN_SECONDS );
		}
		return $link;
	}
}

/**
 * Prints the title of the most popular blog post
 *
 * @author nickmomrik
 * @param int $days Optional. Number of recent days to find the most popular posts from. Minimum of 2.
 */
if ( ! function_exists( 'wpcom_vip_top_post_title' ) ) {
	function wpcom_vip_top_post_title( $days = 2 ) {
		global $wpdb;

		// Compat for .org
		if ( ! function_exists( 'stats_get_daily_history' ) )
			return array(); // TODO: Return dummy data

		$title = wp_cache_get("wpcom_vip_top_post_title_$days", 'output');
		if ( empty($title) ) {
			if ( $days < 2 || !is_int($days) ) $days = 2; // Minimum is 2 because of how stats rollover for a new day

			$topposts = array_shift(stats_get_daily_history(false, $wpdb->blogid, 'postviews', 'post_id', false, $days, '', 11, true));
			if ( $topposts ) {
				get_posts(array('include' => join(', ', array_keys($topposts))));
				$posts = 0;
				foreach ( $topposts as $id => $views ) {
					$post = get_post($id);
					if ( empty( $post ) )
						$post = get_page($id);
					if ( empty( $post ) )
						continue;
					$title .= $post->post_title;
					break;
				}
			} else {
				$title .= '';
			}
			wp_cache_add("wpcom_vip_top_post_title_$days", $title, 'output', 1200);
		}
		echo $title;
	}
}

/**
 * Fetch a remote URL and cache the result for a certain period of time.
 *
 * This function originally used file_get_contents(), hence the function name.
 * While it no longer does, it still operates the same as the basic PHP function.
 *
 * We strongly recommend not using a $timeout value of more than 3 seconds as this
 * function makes blocking requests (stops page generation and waits for the response).
 *
 * The $extra_args are:
 *  * obey_cache_control_header: uses the "cache-control" "max-age" value if greater than $cache_time.
 *  * http_api_args: see http://codex.wordpress.org/Function_API/wp_remote_get
 *
 * @link http://lobby.vip.wordpress.com/best-practices/fetching-remote-data/ Fetching Remote Data
 * @param string $url URL to fetch
 * @param int $timeout Optional. The timeout limit in seconds; valid values are 1-10. Defaults to 3.
 * @param int $cache_time Optional. The minimum cache time in seconds. Valid values are >= 60. Defaults to 900.
 * @param array $extra_args Optional. Advanced arguments: "obey_cache_control_header" and "http_api_args".
 * @return string The remote file's contents (cached)
 */
if ( ! function_exists( 'wpcom_vip_file_get_contents' ) ) {
	function wpcom_vip_file_get_contents( $url, $timeout = 3, $cache_time = 900, $extra_args = array() ) {
		global $blog_id;

		$extra_args_defaults = array(
			'obey_cache_control_header' => true, // Uses the "cache-control" "max-age" value if greater than $cache_time
			'http_api_args' => array(), // See http://codex.wordpress.org/Function_API/wp_remote_get
		);

		$extra_args = wp_parse_args( $extra_args, $extra_args_defaults );

		$cache_key       = md5( serialize( array_merge( $extra_args, array( 'url' => $url ) ) ) );
		$backup_key      = $cache_key . '_backup';
		$disable_get_key = $cache_key . '_disable';
		$cache_group     = 'wpcom_vip_file_get_contents';

		// Temporary legacy keys to prevent mass cache misses during our key switch
		$old_cache_key       = md5( $url );
		$old_backup_key      = 'backup:' . $old_cache_key;
		$old_disable_get_key = 'disable:' . $old_cache_key;

		// Let's see if we have an existing cache already
		// Empty strings are okay, false means no cache
		if ( false !== $cache = wp_cache_get( $cache_key, $cache_group) )
			return $cache;

		// Legacy
		if ( false !== $cache = wp_cache_get( $old_cache_key, $cache_group) )
			return $cache;

		// The timeout can be 1 to 10 seconds, we strongly recommend no more than 3 seconds
		$timeout = min( 10, max( 1, (int) $timeout ) );

		if ( $timeout > 3 && ! is_admin() )
			_doing_it_wrong( __FUNCTION__, 'Using a timeout value of over 3 seconds is strongly discouraged because users have to wait for the remote request to finish before the rest of their page loads.', null );

		$server_up = true;
		$response = false;
		$content = false;

		// Check to see if previous attempts have failed
		if ( false !== wp_cache_get( $disable_get_key, $cache_group ) ) {
			$server_up = false;
		}
		// Legacy
		elseif ( false !== wp_cache_get( $old_disable_get_key, $cache_group ) ) {
			$server_up = false;
		}
		// Otherwise make the remote request
		else {
			$http_api_args = (array) $extra_args['http_api_args'];
			$http_api_args['timeout'] = $timeout;
			$response = wp_remote_get( $url, $http_api_args );
		}

		// Was the request successful?
		if ( $server_up && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$content = wp_remote_retrieve_body( $response );

			$cache_header = wp_remote_retrieve_header( $response, 'cache-control' );
			if ( is_array( $cache_header ) )
				$cache_header = array_shift( $cache_header );

			// Obey the cache time header unless an arg is passed saying not to
			if ( $extra_args['obey_cache_control_header'] && $cache_header ) {
				$cache_header = trim( $cache_header );
				// When multiple cache-control directives are returned, they are comma separated
				foreach ( explode( ',', $cache_header ) as $cache_control ) {
					// In this scenario, only look for the max-age directive
					if( 'max-age' == substr( trim( $cache_control ), 0, 7 ) )
						// Note the array_pad() call prevents 'undefined offset' notices when explode() returns less than 2 results
						list( $cache_header_type, $cache_header_time ) = array_pad( explode( '=', trim( $cache_control ), 2 ), 2, null );
				}
				// If the max-age directive was found and had a value set that is greater than our cache time
				if ( isset( $cache_header_type ) && isset( $cache_header_time ) && $cache_header_time > $cache_time )
					$cache_time = (int) $cache_header_time; // Casting to an int will strip "must-revalidate", etc.
			}

			/**
			 * The cache time shouldn't be less than a minute
			 * Please try and keep this as high as possible though
			 * It'll make your site faster if you do
			 */
			$cache_time = (int) $cache_time;
			if ( $cache_time < 60 )
				$cache_time = 60;

			// Cache the result
			wp_cache_add( $cache_key, $content, $cache_group, $cache_time );

			// Additionally cache the result with no expiry as a backup content source
			wp_cache_add( $backup_key, $content, $cache_group );

			// So we can hook in other places and do stuff
			do_action( 'wpcom_vip_remote_request_success', $url, $response );
		}
		// Okay, it wasn't successful. Perhaps we have a backup result from earlier.
		elseif ( $content = wp_cache_get( $backup_key, $cache_group ) ) {
			// If a remote request failed, log why it did
			if ( ! defined( 'WPCOM_VIP_DISABLE_REMOTE_REQUEST_ERROR_REPORTING' ) || ! WPCOM_VIP_DISABLE_REMOTE_REQUEST_ERROR_REPORTING ) {
				if ( $response && ! is_wp_error( $response ) ) {
					error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response['headers'] ) . ' ' . maybe_serialize( $response['response'] ) );
				} elseif ( $response ) { // is WP_Error object
					error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response ) );
				}
			}
		}
		// Legacy
		elseif ( $content = wp_cache_get( $old_backup_key, $cache_group ) ) {
			// If a remote request failed, log why it did
			if ( ! defined( 'WPCOM_VIP_DISABLE_REMOTE_REQUEST_ERROR_REPORTING' ) || ! WPCOM_VIP_DISABLE_REMOTE_REQUEST_ERROR_REPORTING ) {
				if ( $response && ! is_wp_error( $response ) ) {
					error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response['headers'] ) . ' ' . maybe_serialize( $response['response'] ) );
				} elseif ( $response ) { // is WP_Error object
					error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response ) );
				}
			}
		}
		// We were unable to fetch any content, so don't try again for another 60 seconds
		elseif ( $response ) {
			wp_cache_add( $disable_get_key, 1, $cache_group, 60 );

			// If a remote request failed, log why it did
			if ( ! defined( 'WPCOM_VIP_DISABLE_REMOTE_REQUEST_ERROR_REPORTING' ) || ! WPCOM_VIP_DISABLE_REMOTE_REQUEST_ERROR_REPORTING ) {
				if ( $response && ! is_wp_error( $response ) ) {
					error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response['headers'] ) . ' ' . maybe_serialize( $response['response'] ) );
				} elseif ( $response ) { // is WP_Error object
					error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response ) );
				}
			}
			// So we can hook in other places and do stuff
			do_action( 'wpcom_vip_remote_request_error', $url, $response );
		}
		return $content;
	}
}
