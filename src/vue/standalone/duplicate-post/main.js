import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

import { isBlockEditor } from '#/vue/utils/context'
import { showNotices } from './assets/js/notices.js'
import mergeAndCompare from './assets/js/mergeAndCompare.js'

import './assets/scss/main.scss'

mergeAndCompare()

const ping = async () => {
	let ping = true
	try {
		await http.get(links.restUrl('ping'))
	} catch (error) {
		ping = false
	}

	return ping
}

document.addEventListener('DOMContentLoaded', async () => {
	const pingTest = await ping()
	if (!isBlockEditor() || !pingTest) {
		return
	}

	showNotices()
})