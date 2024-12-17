import { defineStore } from 'pinia'
import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

import {
	useNotificationsStore,
	useSettingsStore
} from '#/vue/stores'

export const useOptionsStore = defineStore('OptionsStore', {
	state : () => ({
		internalOptions : {},
		options         : {}
	}),
	actions : {
		getObjects (payload) {
			return http.post(links.restUrl('objects'))
				.send(payload)
				.then(response => {
					if (!response.body.success) {
						throw new Error(response.body.message)
					}

					return response
				})
		},
		saveChanges () {
			return http.post(links.restUrl('options'))
				.send({
					options : this.options
				})
				.then(response => {
					const notificationsStore = useNotificationsStore()
					notificationsStore.updateNotifications(response.body.notifications)

					return response
				})
		},
		refreshOptions () {
			return http.get(links.restUrl('options'))
				.send()
				.then(response => {
					if (!response.body.success) {
						throw new Error(response.body.message)
					}

					const settingsStore = useSettingsStore()

					this.options           = response.body.options
					this.internalOptions   = response.body.internalOptions
					settingsStore.settings = response.body.settings
				})
		},
		updateOption (store, { groups, key, value }) {
			let options = this[store]
			groups.forEach(group => {
				options = options[group]
			})

			options[key] = value
		}
	}
})