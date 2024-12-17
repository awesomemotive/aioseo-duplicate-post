import { sprintf } from '@wordpress/i18n'
const marketingSite = window.aioseoDuplicatePost.urls.marketingSite
const docLinks      = {
	home : `${marketingSite}docs/`

}

const upsellLinks = {
	home    : marketingSite,
	pricing : `${marketingSite}pricing-duplicate-post/`
}

const getUpsellUrl = (medium, content = null, link) => {
	return utmUrl(medium, content, upsellLinks[link])
}

const getDocUrl = (link) => {
	return utmUrl('documentation', link, docLinks[link])
}

const getUpsellLink = (medium, text, link, addArrow = false) => {
	const arrow = addArrow
		? sprintf(
			'<a href="%1$s" class="no-underline" target="_blank">&nbsp;&rarr;</a>',
			utmUrl(medium, link, upsellLinks[link])
		)
		: ''
	return sprintf(
		'<a href="%1$s" target="_blank">%2$s</a>%3$s',
		utmUrl(medium, link, upsellLinks[link]),
		text,
		arrow
	)
}

const getPlainLink = (text, url, addArrow = false) => {
	const arrow = addArrow
		? sprintf(
			'<a href="%1$s" class="no-underline" target="_blank">&nbsp;&rarr;</a>',
			url
		)
		: ''

	return sprintf(
		'<a href="%1$s" target="_blank">%2$s</a>%3$s',
		url,
		text,
		arrow
	)
}

const getDocLink = (text, link, addArrow = false) => {
	const arrow = addArrow
		? sprintf(
			'<a href="%1$s" class="no-underline" target="_blank">&nbsp;&rarr;</a>',
			utmUrl('documentation', link, docLinks[link])
		)
		: ''
	return sprintf(
		'<a href="%1$s" target="_blank">%2$s</a>%3$s',
		utmUrl('documentation', link, docLinks[link]),
		text,
		arrow
	)
}

const getPricingUrl = (feature, medium, content, url = `${marketingSite}pricing-duplicate-post/`) => {
	return utmUrl(medium, content, url) + '&features[]=' + feature
}

const utmUrl = (medium, content = null, url = `${marketingSite}pricing-duplicate-post/`) => {
	const urlParts = url.split('#')

	// Generate the new arguments.
	const args = [
		{ key: 'utm_source', value: 'WordPress' },
		{ key: 'utm_campaign', value: 'plugin' },
		{ key: 'utm_medium', value: medium }
	]

	// Content is not used by default.
	if (content) {
		args.push({ key: 'utm_content', value: content })
	}

	// Append the marketing site domain if this is a relative url.
	const pattern = /^https?:\/\//i
	if (!pattern.test(urlParts[0])) {
		urlParts[0] = marketingSite + urlParts[0]
	}

	// Build the new URL.
	const newUrlParts = urlParts[0].split('?')
	urlParts[0]       = newUrlParts[0] + (newUrlParts[1] ? '?' + newUrlParts[1] + '&' : '?')
	urlParts[0]      += args
		.map(arg => `${arg.key}=${arg.value}`)
		.join('&')

	url = urlParts[0]
	if (urlParts[1]) {
		url = url + '#' + urlParts[1]
	}

	return url
}

const unForwardSlashIt = str => {
	return str ? str.replace(/^\//, '') : str
}

const unTrailingSlashIt = str => {
	return str ? str.replace(/\/$/, '') : str
}

const trailingSlashIt = str => {
	return unTrailingSlashIt(str) + '/'
}

const restUrl = (path, namespace = 'aioseoDuplicatePost/v1') => {
	path = window.aioseoDuplicatePost.hasUrlTrailingSlash ? trailingSlashIt(path) : unTrailingSlashIt(path)
	return trailingSlashIt(window.aioseoDuplicatePost.urls.restUrl) + trailingSlashIt(namespace) + unForwardSlashIt(path)
}

export default {
	docLinks,
	getDocLink,
	getDocUrl,
	getPlainLink,
	getPricingUrl,
	getUpsellLink,
	getUpsellUrl,
	restUrl,
	trailingSlashIt,
	unForwardSlashIt,
	unTrailingSlashIt,
	utmUrl
}