// import '#/vue/assets/scss/dismiss-notice.scss'
import '../../assets/scss/dismiss-notice.scss'

window.addEventListener('load', function () {
	let dismissBtn

	const aioseoDuplicatePostSetupButton = function (dismissBtn) {
		const notice      = document.querySelector('.notice.aioseo-duplicate-post-review-plugin-cta')
		let	delay       = false,
		 relay       = true
		const	stepOne     = notice.querySelector('.step-1')
		const	stepTwo     = notice.querySelector('.step-2')
		const	stepThree   = notice.querySelector('.step-3')

		// Add an event listener to the dismiss button.
		dismissBtn.addEventListener('click', function () {
			const httpRequest = new XMLHttpRequest()
			let postData    = ''

			// Build the data to send in our request.
			postData += '&delay=' + delay
			postData += '&relay=' + relay
			postData += '&action=aioseo-duplicate-post-dismiss-review-plugin-cta'
			postData += '&nonce=' + window.aioseoDuplicatePost.dismissNonce

			httpRequest.open('POST', window.aioseoDuplicatePost.urls.adminUrl)
			httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
			httpRequest.send(postData)
		})

		notice.addEventListener('click', function (event) {
			if (event.target.matches('.aioseo-duplicate-post-review-switch-step-3')) {
				event.preventDefault()
				stepOne.style.display   = 'none'
				stepTwo.style.display   = 'none'
				stepThree.style.display = 'block'
			}
			if (event.target.matches('.aioseo-duplicate-post-review-switch-step-2')) {
				event.preventDefault()
				stepOne.style.display   = 'none'
				stepThree.style.display = 'none'
				stepTwo.style.display   = 'block'
			}
			if (event.target.matches('.aioseo-duplicate-post-dismiss-review-notice-delay')) {
				event.preventDefault()
				delay = true
				relay = false
				dismissBtn.click()
			}
			if (event.target.matches('.aioseo-duplicate-post-dismiss-review-notice')) {
				if ('#' === event.target.getAttribute('href')) {
					event.preventDefault()
				}
				relay = false
				dismissBtn.click()
			}
		})
	}

	dismissBtn = document.querySelector('.aioseo-duplicate-post-review-plugin-cta .notice-dismiss')
	if (!dismissBtn) {
		document.addEventListener('animationstart', function (event) {
			if ('dismissBtnVisible' === event.animationName) {
				dismissBtn = document.querySelector('.aioseo-duplicate-post-review-plugin-cta .notice-dismiss')
				if (dismissBtn) {
					aioseoDuplicatePostSetupButton(dismissBtn)
				}
			}
		}, false)
	} else {
		aioseoDuplicatePostSetupButton(dismissBtn)
	}
})