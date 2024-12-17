import { defineStore } from 'pinia'
import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

export const usePluginsStore = defineStore('PluginsStore', {
	state : () => ({
		plugins : {}
	}),
	actions : {
		installPlugins (plugins) {
			return http.post(links.restUrl('plugins/install'))
				.send({
					network : false,
					plugins : plugins
				})
				.then(response => {
					if (!response.body.success) {
						throw new Error(response.body.message)
					}

					return response
				})
		}
	}
})