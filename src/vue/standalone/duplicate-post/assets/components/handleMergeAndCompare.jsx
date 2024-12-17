import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

const { dispatch, subscribe, select } = window.wp.data
const { Button } = window.wp.components
const { __, setLocaleData } = window.wp.i18n
const { createInterpolateElement } = window.wp.element
const td = import.meta.env.VITE_TEXTDOMAIN

/**
 * Fires when the save and compare button is clicked.
 *
 * @param   {number} originalPostId - The id of the original Post.
 * @returns {void}
 */
const saveAndCompare = () => {
	return async () => {
		try {
			await dispatch('core/editor').savePost()
			if (window.aioseoDuplicatePost.compareLink) {
				window.location.href = window.aioseoDuplicatePost.compareLink
			}
		} catch (error) {
			console.error(__('An error occurred while saving the post.', td))
		}
	}
}

/**
 * Translate the strings and create buttons for merging and comparing.
 * Additionally add custom functionality to the publish button.
 *
 * @param   {number} originalPostId - The id of the original Post.
 * @returns {void}
 */
export default function translateAndMerge (originalPostId) {
	const mergeStrings = {
		Publish       : __('Merge', td),
		'Publish:'    : __('Merge:', td),
		'Publish on:' : __('Merge on:', td),

		'Are you ready to publish?'	: __('Are you ready to merge your post?', td),
		'Double-check your settings before publishing.' :
	createInterpolateElement(
		__('After merging, your changes will be placed into the original post and you\'ll be redirected there.<br /><br />Do you want to compare your changes with the original version before merging?<br /><br /><button>Save changes and compare</button>',
			td),
		{
			button : <Button isSecondary onClick={ saveAndCompare() } />,
			br     : <br />
		}
	),

		Schedule                                 : __('Schedule merge', td),
		'Schedule…'                              : __('Schedule merge...', td),
		'post action/button label\u0004Schedule' : __('Schedule merge', td),

		'Are you ready to schedule?' : __('Are you ready to schedule the merging of your post?', td),
		'Your work will be published at the specified date and time.' :
	createInterpolateElement(
		__('You\'re about to replace the original with this rewritten post at the specified date and time.<br /><br />Do you want to compare your changes with the original version before merging?<br /><br /><button>Save changes and compare</button>',
			td),
		{
			button : <Button isSecondary onClick={ saveAndCompare() } />,
			br     : <br />
		}
	),
		'is now scheduled. It will go live on' :
	__(', the rewritten post, is now scheduled to replace the original post. It will be published on',
		td)
	}

	for (const original in mergeStrings) {
		setLocaleData({
			[original] : [
				mergeStrings[original],
				td
			]
		})
	}

	// Check the Publish button. If the strings are incorrect, change them.
	const publishButton = document.querySelector('.editor-post-publish-button__button') || document.querySelector('.editor-post-publish-button')
	if (publishButton) {
		if ('Publish' === publishButton?.innerText) {
			publishButton.innerText = __('Merge', td)
		}

		if ('Schedule' === publishButton?.innerText) {
			publishButton.innerText = __('Schedule merge', td)
		}
	}

	let isEventListenerAdded = false

	const getCurrentTimeZoneOffsetInHours = () => {
		const offset = new Date().getTimezoneOffset()
		return offset / 60
	}

	const handleClick = async (event) => {
		event.preventDefault() // Prevent the default publish action
		event.stopPropagation()

		const postId = window.aioseoDuplicatePost.postId
		try {
			const wpTimezone = await http.get(links.restUrl('get-timezone'))
			const wpOffset   = wpTimezone.body.gmtOffset
			const myOffset   = getCurrentTimeZoneOffsetInHours()
			const now        = new Date()
			const postDate   = new Date(select('core/editor').getEditedPostAttribute('date'))

			// Adjust dates to UTC
			const nowUTC      = new Date(now.getTime() + myOffset * 60 * 60 * 1000)
			const postDateUTC = new Date(postDate.getTime() - wpOffset * 60 * 60 * 1000)

			const isScheduled = postDateUTC > nowUTC

			if (isScheduled) {
				await dispatch('core/editor').editPost({ status: 'future' })
			} else {
				await http.get(links.restUrl(`set-merge-ready?post_id=${postId}`))
			}
			await dispatch('core/editor').savePost()

			// Redirect after a successful merge
			if (0 !== originalPostId) {
				if (!isScheduled) {
					window.location.href = `${window.location.origin}/wp-admin/post.php?post=${originalPostId}&action=edit&merged=1`
				}
			}
		} catch (error) {
			console.error('Error during merge and save:', error)
		}
	}

	// Function to add the event listener to the publish button.
	const addEventListenerToPublishButton = () => {
		const publishButton = document.querySelector('.editor-post-publish-button')
		if (publishButton && !isEventListenerAdded) {
			publishButton.addEventListener('click', handleClick)
			isEventListenerAdded = true
		}
	}

	// Create a MutationObserver to watch for changes in the DOM.
	const observer = new MutationObserver((mutations) => {
		mutations.forEach((mutation) => {
			if ('childList' === mutation.type) {
				addEventListenerToPublishButton()
			}
		})
	})

	// Start observing the body for changes in the DOM
	observer.observe(document.body, {
		childList : true,
		subtree   : true
	})

	// Override Merge/Publish button behavior
	subscribe(() => {
		const isPublishPanelOpened = select('core/edit-post').isPublishSidebarOpened()
		if (isPublishPanelOpened) {
			addEventListenerToPublishButton()
		} else {
			isEventListenerAdded = false // Reset the flag when the sidebar is closed
		}
	})
}