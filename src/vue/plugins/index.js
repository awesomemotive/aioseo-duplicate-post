import emitter from 'tiny-emitter/instance'
import VueScrollTo from 'vue-scrollto'

import * as constants from './constants'
import translate from './translations'
import links from '#/vue/utils/links'

window.aioseoDuplicatePost    = window.aioseoDuplicatePost || {}
window.aioseoDuplicatePostBus = window.aioseoDuplicatePostBus || {
	$on   : (...args) => emitter.on(...args),
	$once : (...args) => emitter.once(...args),
	$off  : (...args) => emitter.off(...args),
	$emit : (...args) => emitter.emit(...args)
}

if (import.meta.env.PROD) {
	window.__aioseoDynamicImportPreload__ = filename => {
		return `${window.aioseoDuplicatePost.urls.publicPath || '/'}dist/assets/${filename}`
	}
}

export default app => {
	app.use(VueScrollTo, {
		container  : 'body',
		duration   : 1000,
		easing     : 'ease-in-out',
		offset     : 0,
		force      : true,
		cancelable : true,
		onStart    : false,
		onDone     : false,
		onCancel   : false,
		x          : false,
		y          : true
	})

	// TODO: Remove all the lines below once the main plugin no longer has these set as global props.
	// We can't remove them here until then since we import files from the main plugin that use them.
	app.provide('$constants', constants)
	app.provide('$td', import.meta.env.VITE_TEXTDOMAIN)
	app.provide('$links', links)
	app.provide('$t', translate)

	app.config.globalProperties.$constants = constants
	app.config.globalProperties.$td        = import.meta.env.VITE_TEXTDOMAIN
	app.config.globalProperties.$tdPro     = import.meta.env.VITE_TEXTDOMAIN_PRO

	app.$links = app.config.globalProperties.$links = links
	app.$t     = app.config.globalProperties.$t     = translate

	return app
}