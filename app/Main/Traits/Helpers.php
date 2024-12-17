<?php
namespace AIOSEO\DuplicatePost\Main\Traits;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper functions for the core.
 *
 * @since 1.0.0
 */
trait Helpers {

	/**
	 * Checks for the basic elements options so that we can modify only selected ones.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $postData     The post data to modify.
	 * @return mixed $postData The modified post data.
	 */
	public function checkBasicElements( $postData ) {
		$postElements = $this->options['postElements'] ?? [];

		if ( ! isset( $postElements['title'] ) ) {
			$postData->post_title = esc_attr__( 'Untitled Post', 'duplicate-post-page-aioseo' );
		}

		if ( ! isset( $postElements['content'] ) ) {
			$postData->post_content = '';
		}

		if ( ! isset( $postElements['slug'] ) ) {
			$postData->post_name = '';
		}

		if ( ! isset( $postElements['excerpt'] ) ) {
			$postData->post_excerpt = '';
		}

		if ( ! isset( $postElements['parents'] ) ) {
			$postData->post_parent = '';
		}

		if ( ! isset( $postElements['password'] ) ) {
			$postData->post_password = '';
		}

		if ( ! isset( $postElements['author'] ) ) {
			$postData->post_author = get_current_user_id();
		}

		if ( ! isset( $postElements['date'] ) ) {
			$postData->post_date     = current_time( 'mysql' );
			$postData->post_date_gmt = current_time( 'mysql', 1 );

			$postData->post_modified     = current_time( 'mysql' );
			$postData->post_modified_gmt = current_time( 'mysql', 1 );
		}

		if ( ! isset( $postElements['menu_order'] ) ) {
			$postData->menu_order = 0;
		}

		// Advanced setting that will increaase the menuOrder by the given number
		if ( isset( $this->options['menuOrder'] ) ) {
			$increaseBy = (int) sanitize_text_field( $this->options['menuOrder'] );
			if ( is_int( $increaseBy ) ) {
				$postData->menu_order = $postData->menu_order + $increaseBy;
			}
		}

		return $postData;
	}

	/**
	 * Copy post meta from the original post to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyPostMeta( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		$postMeta = get_post_meta( $oldPostId );
		$dontCopyMeta = $this->options['dontCopyMeta'];

		foreach ( $postMeta as $metaKey => $metaValues ) {
			foreach ( $metaValues as $metaValue ) {
				if ( ! $merge && ! $isRevision ) {
					if ( in_array( $metaKey, $dontCopyMeta, true ) ) {
						delete_post_meta( $newPostId, $metaKey );
						continue;
					}
					// Check for wildcard pattern matches
					foreach ( $dontCopyMeta as $pattern ) {
						if ( fnmatch( $pattern, $metaKey, FNM_NOESCAPE ) ) {
							continue 2; // Continue the outer loop
						}
					}

					// Exception for featured image since we have it in the General Settings too
					if ( ! isset( $this->options['postElements']['featured_image'] ) ) {
						if ( '_thumbnail_id' === $metaKey ) {
							delete_post_meta( $newPostId, $metaKey );
							continue;
						}
					}
				}

				if ( '_aioseo_revision' === $metaKey ||
					'_aioseo_original' === $metaKey ||
					'_aioseo_merge_ready' === $metaKey ) {
					continue;
				}

				update_post_meta( $newPostId, $metaKey, maybe_unserialize( $metaValue ) );
			}
		}
	}

	/**
	 * Copy the post format from the original post to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyPostFormat( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['postElements'] ) ) {
			return;
		}

		if ( 'all' === $this->options['postElements'] ||
			isset( $this->options['postElements']['post_format'] ) ||
			$merge ||
			$isRevision
		) {
			$postFormat = get_post_format( $oldPostId );
			if ( false !== $postFormat ) {
				set_post_format( $newPostId, $postFormat );
			}
		}
	}

	/**
	 * Maybe copy the featured image from the old post to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyFeaturedImage( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['postElements'] ) ) {
			return;
		}

		if ( 'all' === $this->options['postElements'] ||
			isset( $this->options['postElements']['featured_image'] ) ||
			$merge ||
			$isRevision
		) {
			// Get the featured image ID of the original post
			$thumbnailId = get_post_thumbnail_id( $oldPostId );

			// Check if the original post has a featured image
			if ( $thumbnailId ) {
				// Set the featured image for the new post
				set_post_thumbnail( $newPostId, $thumbnailId );
			} else {
				if ( $merge ) {
					// Remove the featured image from the new post
					delete_post_thumbnail( $newPostId );
				}
			}
		}
	}

	/**
	 * Maybe copy the taxonomies for the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyTaxonomies( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['taxonomies'] ) ) {
			return;
		}

		if ( 'all' === $this->options['taxonomies'] || $merge || $isRevision ) {
			$taxonomies = wp_list_pluck( aioseoDuplicatePost()->helpers->getAllTaxonomies(), 'slug' );
		} else {
			$taxonomies = $this->options['taxonomies'];
		}

		foreach ( $taxonomies as $taxonomy ) {
			// Check if the taxonomy exists for the old post
			$terms = wp_get_post_terms( $oldPostId, $taxonomy );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$termIds = wp_list_pluck( $terms, 'term_id' );
				wp_set_post_terms( $newPostId, $termIds, $taxonomy );
			}
		}
	}

	/**
	 * Copy the template from the old post to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyPostTemplate( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['postElements'] ) ) {
			return;
		}

		if ( 'all' === $this->options['postElements'] ||
			isset( $this->options['postElements']['template'] ) ||
			$merge ||
			$isRevision
		) {
			// Get the template of the original post
			$template = get_post_meta( $oldPostId, '_wp_page_template', true );

			// Check if there is a template to copy
			if ( ! empty( $template ) ) {
				// Assign the template to the new post
				update_post_meta( $newPostId, '_wp_page_template', $template );
			}
		}
	}

	/**
	 * Copy the attachments from the old post to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyAttachments( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['postElements'] ) ) {
			return;
		}

		if ( 'all' === $this->options['postElements'] ||
			isset( $this->options['postElements']['attachments'] ) ||
			$merge ||
			$isRevision
		) {
			// Get all attachments for the original post
			$attachments = get_children( [
				'post_parent'    => $oldPostId,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image', // You can change this to other mime types if needed
				'numberposts'    => -1,
			] );

			// Loop through each attachment and clone it
			foreach ( $attachments as $attachment ) {
				// Get the attachment metadata
				$attachmentMeta = wp_get_attachment_metadata( $attachment->ID );

				// Prepare the attachment data for the new post
				$attachmentData = [
					'post_mime_type' => $attachment->post_mime_type,
					'guid'           => $attachment->guid,
					'post_parent'    => $newPostId,
					'post_title'     => $attachment->post_title,
					'post_content'   => $attachment->post_content,
					'post_excerpt'   => $attachment->post_excerpt,
					'post_status'    => $attachment->post_status,
				];

				// Insert the new attachment
				$newAttachmentId = wp_insert_attachment( $attachmentData, get_attached_file( $attachment->ID ), $newPostId );

				// Copy the attachment metadata
				if ( ! is_wp_error( $newAttachmentId ) ) {
					wp_update_attachment_metadata( $newAttachmentId, $attachmentMeta );
				}
			}
		}
	}

	/**
	 * Copy all comments from the original post to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyPostComments( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['postElements'] ) ) {
			return;
		}

		if ( 'all' === $this->options['postElements'] ||
			isset( $this->options['postElements']['comments'] ) ||
			$merge ||
			$isRevision
		) {
			// Get all comments for the original post
			$comments = get_comments( [
				'post_id' => $oldPostId
			] );

			// Loop through each comment and clone it
			foreach ( $comments as $comment ) {
				// Do not copy pingbacks or trackbacks.
				if ( 'pingback' === $comment->comment_type || 'trackback' === $comment->comment_type ) {
					continue;
				}

				// Prepare the comment data for the new post
				$commentData = [
					'comment_post_ID'      => $newPostId,
					'comment_author'       => $comment->comment_author,
					'comment_author_email' => $comment->comment_author_email,
					'comment_author_url'   => $comment->comment_author_url,
					'comment_content'      => $comment->comment_content,
					'comment_type'         => $comment->comment_type,
					'comment_parent'       => $comment->comment_parent,
					'user_id'              => $comment->user_id,
					'comment_author_IP'    => $comment->comment_author_IP,
					'comment_agent'        => $comment->comment_agent,
					'comment_date'         => $comment->comment_date,
					'comment_approved'     => $comment->comment_approved,
				];

				// Insert the new comment
				$newCommentId = wp_insert_comment( $commentData );

				// Copy comment meta data
				$commentMeta = get_comment_meta( $comment->comment_ID );
				foreach ( $commentMeta as $metaKey => $metaValues ) {
					foreach ( $metaValues as $metaValue ) {
						update_comment_meta( $newCommentId, $metaKey, maybe_unserialize( $metaValue ) );
					}
				}
			}
		}
	}

	/**
	 * Make copies of all of the children of the old post and attach them to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $oldPostId  The ID of the original post.
	 * @param int  $newPostId  The ID of the new post.
	 * @param bool $merge      Whether we are merging or not.
	 * @param bool $isRevision Check if we're creating a revision.
	 *
	 * @return void
	 */
	public function maybeCopyChildren( $oldPostId, $newPostId, $merge = false, $isRevision = false ) {
		if ( empty( $this->options['postElements'] ) ) {
			return;
		}

		if ( 'all' === $this->options['postElements'] ||
			isset( $this->options['postElements']['children'] ) ||
			$merge ||
			$isRevision
		) {
			// Get children.
			$children = get_posts(
				[
					'post_type'   => 'any',
					'numberposts' => -1,
					'post_status' => 'any',
					'post_parent' => $oldPostId,
				]
			);

			foreach ( $children as $child ) {
				if ( 'attachment' === $child->post_type ) {
					continue;
				}
				$newChildId = $this->clonePost( $child );
				if ( $newChildId ) {
					// Set the parent to be the new post
					wp_update_post(
						[
							'ID'          => $newChildId,
							'post_parent' => $newPostId,
						]
					);
				}
			}
		}
	}

	/**
	 * Checks if a post is a revision.
	 *
	 * @since 1.0.0
	 *
	 *
	 * @param \WP_Post|mixed $post The post object.
	 * @return bool          Whether the post is a revision or not.
	 */
	public function isRevision( $post ) {
		// Check if $post is an object or an ID
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		// If $post is not a valid post object, return false
		if ( ! $post || is_wp_error( $post ) ) {
			return false;
		}

		$isRevision     = get_post_meta( $post->ID, '_aioseo_revision', true );
		$originalPostId = get_post_meta( $post->ID, '_aioseo_original', true );

		// Check if the original post exists
		if ( $originalPostId ) {
			$originalPost = get_post( $originalPostId );
			// Check if the original post exists
			if ( ! $originalPost || is_wp_error( $originalPost ) ) {
				return false;
			}
		}

		return $isRevision ? true : false;
	}

	/**
	 * Checks if a post has an original post.
	 *
	 * @since 1.0.0
	 *
	 *
	 * @param \WP_Post|mixed $post The post object or ID.
	 * @return bool          The id of the original post or false if not found.
	 */
	public function hasOriginal( $post ) {
		// Check if $post is an object or an ID
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		// If $post is not a valid post object, return false
		if ( ! $post || is_wp_error( $post ) ) {
			return false;
		}

		$originalPostId = get_post_meta( $post->ID, '_aioseo_original', true );

		// Check if the original post exists
		if ( $originalPostId ) {
			$originalPost = get_post( $originalPostId );
			// Check if the original post exists
			if ( ! $originalPost || is_wp_error( $originalPost ) ) {
				return false;
			}
		}

		return $originalPostId ? $originalPostId : false;
	}
}