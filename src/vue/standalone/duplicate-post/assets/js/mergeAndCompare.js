import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

import translateAndMerge from '../components/handleMergeAndCompare'

/**
 * Checks if the current post is a revision and has an original post.
 * Adds the merge and compare strings and buttons.
 * Handles compare and merge functionality.
 *
 * @returns {void} Returns nothing.
 */
export default async function mergeAndCompare () {
	const postId = window.aioseoDuplicatePost.postId

	// Translate strings only if it has an original post and it is a revision
	let originalPostId = 0,
	 isRevision = false
	try {
		const response = await http.get(links.restUrl(`get-original-post?post_id=${postId}`))
		if (response.body.success) {
			originalPostId = response.body.original_post.ID
			isRevision = response.body.is_revision
		} else {
			originalPostId = 0
		}
	} catch (error) {
		originalPostId = 0
	}

	if (0 !== originalPostId && isRevision) {
		translateAndMerge(originalPostId)
	}
}