import http from '#/vue/utils/http'
import links from '#/vue/utils/links'
import { __ } from '@wordpress/i18n'

const { dispatch, subscribe } = window.wp.data

let isEventListenerAdded = false,
	observer

async function dismissNotices () {
	subscribe(() => {
		if (isEventListenerAdded) return

		if (!observer) {
			// Create a MutationObserver to watch for changes in the DOM
			observer = new MutationObserver((mutations) => {
				mutations.forEach((mutation) => {
					if ('childList' === mutation.type) {
						const dismissBtn = document.querySelector('.components-notice .components-button')
						if (dismissBtn && 'Close' === dismissBtn.getAttribute('aria-label')) {
							const urlParams = new URLSearchParams(window.location.search)
							const paramsObject = {}

							for (const [ key, value ] of urlParams.entries()) {
								paramsObject[key] = value
							}

							dismissBtn.addEventListener('click', async () => {
								await http.post(links.restUrl('notices/dismiss'))
									.send({
										post_id    : window.aioseoDuplicatePost.postId,
										url_params : paramsObject
									})
							})
							isEventListenerAdded = true
							observer.disconnect()
						}
					}
				})
			})

			// Start observing the body for changes in the DOM
			observer.observe(document.body, {
				childList : true,
				subtree   : true
			})
		}
	})
}

dismissNotices()

export async function showNotices () {
	const td = import.meta.env.VITE_TEXTDOMAIN
	const postId = window.aioseoDuplicatePost.postId
	let notices = null

	const responseNotices = await http.get(links.restUrl(`notices/check?post_id=${postId}`))
	if (responseNotices.body.success) {
		notices = responseNotices.body.notices
	}

	const revisionMessage = await http.get(links.restUrl(`notices/check-revision?post_id=${postId}`))
	if (revisionMessage.body.success) {
		const message = revisionMessage.body.message

		if (message && (!notices || !notices[postId] || (notices[postId] && 'dismissed' !== notices[postId].revision))) {
			dispatch('core/notices').createNotice(
				'warning',
				message,
				{
					id            : 'revision_notice',
					isDismissible : true
				}
			)
		}
	}

	const urlParams = new URLSearchParams(window.location.search)
	if ('1' === urlParams.get('merged') && (!notices || (notices[postId] && 'dismissed' !== notices[postId].merged))) {
		dispatch('core/notices').createNotice(
			'success',
			__('😎 Looking good! Your revision has been updated to the original post and you are now all set to publish.', td),
			{
				id            : 'merge_success_notice',
				isDismissible : true
			}
		)
	}

	if ('1' === urlParams.get('scheduled-merge') && (!notices || (notices[postId] && 'dismissed' !== notices[postId].scheduled))) {
		dispatch('core/notices').createNotice(
			'success',
			__('😎 Looking good! Your revision has been scheduled to merge with the original post.', td),
			{
				id            : 'scheduled_success_notice',
				isDismissible : true
			}
		)
	}
}