import { createPinia } from 'pinia'

import { useNotificationsStore } from '#/vue/stores/NotificationsStore'
import { useOptionsStore } from '#/vue/stores/OptionsStore'
import { usePluginsStore } from '#/vue/stores/PluginsStore'
import { useRootStore } from '#/vue/stores/RootStore'
import { useSettingsStore } from '#/vue/stores/SettingsStore'

import { markRaw } from 'vue'
import { merge, mergeWith } from 'lodash-es'

// This customizer ensures that arrays are not merged, but replaced.
// This is needed to prevent the array options on the window from being merged instead of replaced with the new values.
// Otherwise, if an included post type is unchecked, it will still be included when you navigate back to the relevant settings page.
const mergeCustomizer = (_a, b) => {
	if (Array.isArray(b)) {
		return b
	}
	return undefined
}

const pinia = createPinia()

const loadPiniaStores = (app, router = null) => {
	loadPinia(app, router)

	const rootStore = useRootStore()

	// If the stores have been instantiated, bail.
	if (rootStore.loaded) {
		return pinia
	}

	const aioseoDuplicatePost = JSON.parse(JSON.stringify(window.aioseoDuplicatePost || {}))

	// Pinia stores.
	const notificationsStore   = useNotificationsStore()
	const optionsStore         = useOptionsStore()
	const pluginsStore         = usePluginsStore()
	const settingsStore        = useSettingsStore()

	// Options stores.
	optionsStore.internalOptions = mergeWith({ ...optionsStore.internalOptions }, { ...aioseoDuplicatePost.internalOptions || {} }, mergeCustomizer)
	optionsStore.options         = mergeWith({ ...optionsStore.options }, { ...aioseoDuplicatePost.options || {} }, mergeCustomizer)

	// Other stores.
	notificationsStore.$state         = mergeWith({ ...notificationsStore.$state }, { ...aioseoDuplicatePost.notifications || {} }, mergeCustomizer)
	pluginsStore.plugins              = mergeWith({ ...pluginsStore.plugins }, { ...aioseoDuplicatePost.plugins || {} }, mergeCustomizer)
	settingsStore.settings            = mergeWith({ ...settingsStore.settings }, { ...aioseoDuplicatePost.settings || {} }, mergeCustomizer)

	// Default root store without the above data.
	delete aioseoDuplicatePost.internalOptions
	delete aioseoDuplicatePost.notifications
	delete aioseoDuplicatePost.options
	delete aioseoDuplicatePost.plugins
	delete aioseoDuplicatePost.settings

	// Add additional properties.
	aioseoDuplicatePost.publicPath   = '/'
	aioseoDuplicatePost.translations = {}

	rootStore.aioseoDuplicatePost = merge({ ...rootStore.aioseoDuplicatePost }, { ...aioseoDuplicatePost || {} })

	rootStore.loaded = true

	return pinia
}

const loadPinia = (app, router = null) => {
	if (router) {
		pinia.use(({ store }) => {
			store.$router = markRaw(router)
		})
	}

	app.use(pinia)

	return pinia
}

export {
	pinia,
	loadPinia,
	loadPiniaStores,
	// All the stores.
	useNotificationsStore,
	useOptionsStore,
	usePluginsStore,
	useRootStore,
	useSettingsStore
}