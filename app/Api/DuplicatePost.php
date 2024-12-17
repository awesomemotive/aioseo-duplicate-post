<?php
namespace AIOSEO\DuplicatePost\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the user Vue settings (toggled cards, etc.).
 *
 * @since 1.0.0
 */
class DuplicatePost {
	/**
	 * Return current WP timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request   $request The REST request object.
	 *
	 * @return \WP_REST_Response          The response.
	 */
	public static function getTimezone() {
		$timezone   = get_option( 'timezone_string' );
		$gmtOffset  = intval( get_option( 'gmt_offset' ) );

		return new \WP_REST_Response( [
			'success'   => true,
			'timezone'  => $timezone,
			'gmtOffset' => $gmtOffset
		], 200 );
	}

	/**
	 * Save the status of dismissed notifications so they dont show anymore.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function dismissNotices( $request ) {
		$postId    = $request->get_param( 'post_id' );
		$urlParams = $request->get_param( 'url_params' );

		if ( $postId ) {

			$postId = (int) untrailingslashit( wp_unslash( $postId ) );
			$notices = maybe_unserialize( get_option( 'aioseo_duplicate_post_notices' ) );

			if ( isset( $urlParams['merged'] ) ) {
				if ( empty( $notices ) ) {
					$notices = [];
					$notices[ $postId ] = [ 'merged' => 'dismissed' ];
				} else {
					$notices[ $postId ]['merged'] = 'dismissed';
				}
			}

			if ( isset( $urlParams['scheduled-merge'] ) ) {
				if ( empty( $notices ) ) {
					$notices = [];
					$notices[ $postId ] = [ 'scheduled' => 'dismissed' ];
				} else {
					$notices[ $postId ]['scheduled'] = 'dismissed';
				}
			}

			if ( aioseoDuplicatePost()->main->hooks->functions->isRevision( $postId ) ) {

				if ( empty( $notices ) ) {
					$notices = [];
					$notices[ $postId ] = [ 'revision' => 'dismissed' ];
				} else {
					$notices[ $postId ]['revision'] = 'dismissed';
				}
			}

			update_option( 'aioseo_duplicate_post_notices', $notices );

			return new \WP_REST_Response( [
				'success' => true,
			], 200 );
		} else {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}
	}

	/**
	 * Check if the notices should be disblaed for the current post.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request   $request The REST request object.
	 *
	 * @return \WP_REST_Response          The response.
	 */
	public static function checkNotices( $request ) {

		$postId = $request->get_param( 'post_id' );

		if ( $postId ) {

			$postId = (int) untrailingslashit( wp_unslash( $postId ) );
			$notices = maybe_unserialize( get_option( 'aioseo_duplicate_post_notices' ) );

			return new \WP_REST_Response( [
				'success' => true,
				'notices' => $notices
			], 200 );
		} else {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}
	}

	/**
	 * Returns the settings.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request   $request The REST request object.
	 *
	 * @return \WP_REST_Response          The response.
	 */
	public static function checkRevision( $request ) {

		$postId = $request->get_param( 'post_id' );

		if ( $postId ) {

			$postId = (int) untrailingslashit( wp_unslash( $postId ) );

			return new \WP_REST_Response( [
				'success' => true,
				'message' => aioseoDuplicatePost()->main->hooks->classicEditor->displayNotices( $postId )
			], 200 );
		} else {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}
	}

	/**
	 * Get the original post and additional metadata for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request   $request The REST request object.
	 *
	 * @return \WP_REST_Response          The response.
	 */
	public static function getOriginalPost( $request ) {

		$postId = $request->get_param( 'post_id' );

		if ( $postId ) {

			$postId = (int) untrailingslashit( wp_unslash( $postId ) );

			$originalPostId = get_post_meta( $postId, '_aioseo_original', true );
			$isRevision     = get_post_meta( $postId, '_aioseo_revision', true );
			$mergeReady     = get_post_meta( $postId, '_aioseo_merge_ready', true );

			if ( $originalPostId ) {
				$originalPost = get_post( $originalPostId );

				return new \WP_REST_Response( [
					'success'       => true,
					'original_post' => $originalPost,
					'is_revision'   => $isRevision,
					'merge_ready'   => $mergeReady,
				], 200 );
			} else {
				return new \WP_REST_Response( [
					'success' => false
				], 200 );
			}
		} else {
			return new \WP_REST_Response( [
				'success' => false
			], 200 );
		}
	}

	/**
	 * Adds metadata for the post before merging.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request   $request The REST request object.
	 *
	 * @return \WP_REST_Response          The response.
	 */
	public static function setMergeReady( $request ) {

		$postId = $request->get_param( 'post_id' );

		if ( $postId ) {

			$postId = (int) untrailingslashit( wp_unslash( $postId ) );

			// Add metadata so we know the post is ready for merging when clicking the merge button.
			update_post_meta( $postId, '_aioseo_merge_ready', true );

			return new \WP_REST_Response( [
				'success' => true
			], 200 );
		} else {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}
	}
}