import { defineStore } from 'pinia'
import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

export const useSettingsStore = defineStore('SettingsStore', {
	state : () => ({
		settings : {}
	}),
	actions : {
		toggleCard ({ slug, shouldSave }) {
			this.settings.toggledCards[slug] = !this.settings.toggledCards[slug]

			if (shouldSave) {
				http.post(links.restUrl('settings/toggle-card'))
					.send({
						card : slug
					})
					.then(() => {})
			}
		},
		toggleRadio ({ slug, value }) {
			this.settings.toggledRadio[slug] = value

			http.post(links.restUrl('settings/toggle-radio'))
				.send({
					radio : slug,
					value : value
				})
				.then(() => {})
		},
		changeItemsPerPage ({ slug, value }) {
			this.settings.tablePagination[slug] = value

			return http.post(links.restUrl('settings/items-per-page'))
				.send({
					table : slug,
					value : value
				})
				.then(() => {})
		}
	}
})