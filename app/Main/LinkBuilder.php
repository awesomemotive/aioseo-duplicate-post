<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the creation of links for the plugin.
 *
 * @since 1.0.0
 */
class LinkBuilder {

	/**
	 * List of allowed link types.
	 * @var array
	 */
	private $linkTypes = [
		'aioseo_clone',
		'aioseo_revision',
		'aioseo_compare'
	];

	/**
	 * Generates a link with an action to perform.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed  $post   The post object.
	 * @param  string $type   The type of link to generate.
	 * @param  array  $params Add additional parameters if needed.
	 * @return string         The generated link
	 */
	public function generateLink( $post, $type, $params = [] ) {
		$post = \get_post( $post );
		if ( ! $post instanceof \WP_Post
			|| ! \in_array( $type, $this->linkTypes, true )
		) {
			return '';
		}

		$action = '?action=' . $type . '&post=' . $post->ID;

		if ( ! empty( $params ) ) {
			$action .= '&' . http_build_query( $params );
		}

		return wp_nonce_url( admin_url( 'admin.php' . $action ), $type . '_' . $post->ID );
	}

	/**
	 * Generates a link to compare the post that will be merged to an original post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $postId         The original post object or ID.
	 * @param  int    $originalPostId The revision post object or ID.
	 * @param  string $type           The custom action type name.
	 * @return string                 The generated link
	 */
	public function generateComparePostsLink( $postId, $originalPostId, $type = 'aioseo_compare' ) {
		$post = \get_post( $postId );
		if ( ! $post instanceof \WP_Post
			|| ! \in_array( $type, $this->linkTypes, true )
		) {
			return '';
		}

		$action = '?page=aioseo_compare_page&action=' . $type . '&post=' . $post->ID . '&original_post_id=' . $originalPostId;

		return wp_nonce_url( admin_url( 'admin.php' . $action ), $type . '_' . $post->ID . '_' . $originalPostId );
	}
}