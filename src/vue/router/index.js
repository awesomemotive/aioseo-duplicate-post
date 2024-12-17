import { createRouter, createWebHashHistory } from 'vue-router'

import {
	loadPiniaStores,
	useRootStore
} from '#/vue/stores'

export default (paths, app) => {
	const router = createRouter({
		history : createWebHashHistory(`wp-admin/admin.php?page=aioseo-duplicate-post-${window.aioseoDuplicatePost.page}`),
		routes  : paths,
		scrollBehavior (to, from, savedPosition) {
			if (savedPosition) {
				return savedPosition
			}
			if (to.hash) {
				return { selector: to.hash }
			}
			return { left: 0, top: 0 }
		}
	})

	router.beforeEach(async (to, from, next) => {
		const rootStore  = useRootStore()
		if (!rootStore.loaded) {
			loadPiniaStores(app)
		}

		// TODO: Add this in later.
		// Make sure the API is available.
		// rootStore.ping()

		return next()
	})

	return router
}