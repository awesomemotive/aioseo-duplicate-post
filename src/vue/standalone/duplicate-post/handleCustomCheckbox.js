import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

document.addEventListener('DOMContentLoaded', function () {
	// Check if `#the-list` exists.
	if (!document.querySelector('#the-list') || !document.querySelector('#bulk_edit')) {
		return
	}

	// Bind to the Quick Edit save event.
	document.querySelector('#the-list').addEventListener('click', function (event) {
		if (event.target.classList.contains('editinline')) {
			// Get the post ID.
			const postRow = event.target.closest('tr')
			const postId = postRow.id.replace('post-', '')

			setTimeout(async () => {
				// Get the custom checkbox value.
				const editRow = document.querySelector('#edit-' + postId)
				const customCheckbox = editRow.querySelector('input[name="aioseo_remove_original"]').checked ? '1' : ''

				const response = await http.get(links.restUrl(`get-original-post?post_id=${postId}`))
				if (response.body.success) {
					// Check the condition and remove the custom fieldset if the condition is met.
					const customFieldset = editRow.querySelector('.aioseo_remove_original_fieldset')
					if (customFieldset) {
						customFieldset.style.opacity = '1'
					}
				}

				// Add the custom checkbox value to the inline edit form.
				editRow.querySelector('input[name="aioseo_remove_original"]').value = customCheckbox
			}, 0)
		}
	})

	// Ensure the custom checkbox value is included in the AJAX request.
	document.querySelector('#bulk_edit').addEventListener('click', function (event) {
		if ('bulk_edit_save' === event.target.id) {
			const bulkEditRow = document.querySelector('#bulk-edit')
			const bulkEditCheckbox = bulkEditRow.querySelector('input[name="aioseo_custom_checkbox"]').checked ? '1' : ''
			document.querySelectorAll('input[name="aioseo_custom_checkbox"]').forEach(function (input) {
				input.value = bulkEditCheckbox
			})
		}
	})
})

export default {}