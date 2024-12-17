<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main functions that are used for the plugin like duplicate, merge, rewrite.
 *
 * @since 1.0.0
 */
class Functions {
	use Traits\Helpers;

	/**
	 * Options to clone/merge.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $options;

	/**
	 * The post elements to clone/merge.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $postElements;

	/**
	 * Taxonomies to clone/merge
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $taxonomies;


	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setOptions();
	}

	/**
	 * Set options.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setOptions() {
		if ( aioseoDuplicatePost()->options->general->postElements->all ) {
			$this->postElements = 'all';
		} else {
			$this->postElements = array_flip( aioseoDuplicatePost()->options->general->postElements->included );
		}

		$this->options['titlePrefix'] = aioseoDuplicatePost()->options->general->titlePrefix;
		$this->options['titleSuffix'] = aioseoDuplicatePost()->options->general->titleSuffix;

		$this->options['dontCopyMeta'] = array_column( (array) json_decode( aioseoDuplicatePost()->options->general->dontCopyMeta ), 'value', 'label' );

		if ( aioseoDuplicatePost()->options->general->taxonomies->all ) {
			$this->taxonomies = 'all';
		} else {
			$this->taxonomies = aioseoDuplicatePost()->options->general->taxonomies->included;
		}

		$this->options['menuOrder'] = aioseoDuplicatePost()->options->general->menuOrder;

		if ( $this->postElements ) {
			$this->options['postElements'] = $this->postElements;
		}

		if ( $this->taxonomies ) {
			$this->options['taxonomies'] = $this->taxonomies;
		}
	}

	/**
	 * Clone the post with the specific options given.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post         The post object.
	 * @param bool     $isRevision   Add different metadata if cloned post is a revision that will be merged later.
	 *
	 * @return int The id of the clones object.
	 */
	public function clonePost( $post, $isRevision = false ) {
		// Get the original post data
		$postData = get_post( $post );
		$postId   = $post->ID;

		// Remove the post ID to create a new post
		unset( $postData->ID );

		if ( ! isset( $this->options['postElements']['status'] ) ) {
			// Check if the post is password protected
			if ( post_password_required( $post ) ) {
				$postData->post_status = 'publish';
			} else {
				$postData->post_status = 'draft';
			}
		}

		// Add prefix and suffix and check basic elements only for non-revisions
		if ( ! $isRevision ) {

			if ( ! isset( $this->options['postElements'] ) || 'all' !== $this->options['postElements'] ) {
				$postData = $this->checkBasicElements( $postData );
			}

			$titlePrefix = $this->options['titlePrefix'] ? sanitize_text_field( $this->options['titlePrefix'] ) : '';
			$titleSuffix = $this->options['titleSuffix'] ? sanitize_text_field( $this->options['titleSuffix'] ) : '';
			$postData->post_title = $titlePrefix . ' ' . $postData->post_title . ' ' . $titleSuffix;
		}

		// Insert the new post and get the new post ID
		$newPostId = wp_insert_post( $postData );

		if ( is_wp_error( $newPostId ) || 0 === $newPostId ) {
			wp_die( esc_html__( 'Clone operation failed, cannot create post.', 'duplicate-post-page-aioseo' ) );
		}

		do_action( 'dp_duplicate_post', $newPostId, $postId );
		do_action( 'dp_duplicate_page', $newPostId, $postId );

		do_action( 'aioseo_duplicate_post', $newPostId, $postId );

		// Copy post format
		$this->maybeCopyPostFormat( $postId, $newPostId, false, $isRevision );

		// Copy featured image
		$this->maybeCopyFeaturedImage( $postId, $newPostId, false, $isRevision );

		// Copy taxonomies
		$this->maybeCopyTaxonomies( $postId, $newPostId, false, $isRevision );

		// Copy template
		$this->maybeCopyPostTemplate( $postId, $newPostId, false, $isRevision );

		// Copy attachemnts
		$this->maybeCopyAttachments( $postId, $newPostId, false, $isRevision );

		// Copy comments
		$this->maybeCopyPostComments( $postId, $newPostId, false, $isRevision );

		// Copy children
		$this->maybeCopyChildren( $postId, $newPostId, false, $isRevision );

		// Copy postmeta data
		$this->maybeCopyPostMeta( $postId, $newPostId, false, $isRevision );

		// Check if the original post is sticky
		if ( is_sticky( $postId ) ) {
			stick_post( $newPostId );
		}

		// Add metadata to the new post
		delete_post_meta( $newPostId, '_aioseo_original' );
		add_post_meta( $newPostId, '_aioseo_original', $postId );

		if ( $isRevision ) {
			delete_post_meta( $newPostId, '_aioseo_revision' );
			add_post_meta( $newPostId, '_aioseo_revision', $newPostId );
		} else {
			delete_post_meta( $newPostId, '_aioseo_clone' );
			add_post_meta( $newPostId, '_aioseo_clone', $newPostId );
		}

		return $newPostId;
	}

	/**
	 * Merge revision to the original post.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $revisionPost The post object for the revision.
	 * @param \WP_Post $originalPost The post object for the original post to merge into.
	 *
	 * @return int The id of the original post.
	 */
	public function mergePosts( $revisionPost, $originalPost ) {
		// Get the revision post data.
		$postData = get_post( $revisionPost );
		$postId   = $revisionPost->ID;

		$originalPostId = $originalPost->ID;

		// Set the original post ID
		$postData->ID = $originalPostId;

		// Maintain the original post status
		$postData->post_status = $originalPost->post_status;

		// Update postdata
		$originalPostId = wp_update_post( $postData );

		if ( is_wp_error( $originalPostId ) || 0 === $originalPostId ) {
			wp_die( esc_html__( 'Merge operation failed, cannot merge posts.', 'duplicate-post-page-aioseo' ) );
		}

		do_action( 'dp_duplicate_post', $originalPostId, $postId );
		do_action( 'dp_duplicate_page', $originalPostId, $postId );

		do_action( 'aioseo_duplicate_post_before', $postId, $originalPostId );

		// Copy post format
		$this->maybeCopyPostFormat( $postId, $originalPostId, true );

		// Copy featured image
		$this->maybeCopyFeaturedImage( $postId, $originalPostId, true );

		// Copy taxonomies
		$this->maybeCopyTaxonomies( $postId, $originalPostId, true );

		// Copy template
		$this->maybeCopyPostTemplate( $postId, $originalPostId, true );

		// Copy attachemnts
		$this->maybeCopyAttachments( $postId, $originalPostId, true );

		// Copy comments
		$this->maybeCopyPostComments( $postId, $originalPostId, true );

		// Copy children
		$this->maybeCopyChildren( $postId, $originalPostId, true );

		// Copy postmeta data
		$this->maybeCopyPostMeta( $postId, $originalPostId, true );

		do_action( 'duplicate_post_after_rewriting', $postId, $originalPostId );
		do_action( 'aioseo_duplicate_post_after', $originalPostId, $postId );

		return $originalPostId;
	}
}