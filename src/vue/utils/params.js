export const getParams = url => {
	return (url || document.location.search).replace(/(^\?)/, '').split('&').map(function (n) {
		n          = n.split('=')
		this[n[0]] = n[1]
		return this
	}.bind({}))[0]
}

export const removeParam = (param, url) => {
	url = url || document.location.href
	const hashParts = url.split('#')
	const urlParts  = hashParts[0].split('?')
	if (2 > urlParts.length) {
		return url
	}

	const prefix = encodeURIComponent(param) + '='
	const pars   = urlParts[1].split(/[&;]/g)
	for (let i = pars.length; 0 < i--;) {
		if (-1 !== pars[i].lastIndexOf(prefix, 0)) {
			pars.splice(i, 1)
		}
	}

	const allHashParts = hashParts[1] ? '#' + hashParts[1] : ''
	const newUrl       = urlParts[0] + (0 < pars.length ? '?' + pars.join('&') : '') + allHashParts
	window.history.replaceState(null, null, newUrl)
}

export const addParam = (url, param, val) => {
	if (!url || !param) {
		return url
	}
	const re = new RegExp('([?|&])' + param + '=.*?(&|#|$)', 'i')
	if (url.match(re)) {
		url = url.replace(re, '$1' + param + '=' + val + '$2')
	} else {
		const sep = -1 !== url.indexOf('?') ? '&' : '?'
		let hash  = ''
		if (-1 !== url.indexOf('#')) {
			hash = url.replace(/.*#/, '#')
			url = url.replace(/#.*/, '')
		}
		url = url + sep + param + '=' + val + hash
	}

	window.location.href = url
}