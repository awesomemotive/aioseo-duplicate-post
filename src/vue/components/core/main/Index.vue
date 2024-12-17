<template>
	<div>
		<core-notifications/>

		<div class="aioseo-main">
			<core-header/>

			<grid-container>
				<core-main-tabs
					:key="tabsKey"
					v-if="showTabs"
					:tabs="tabs"
					:showSaveButton="shouldShowSaveButton"
				/>

				<transition name="route-fade" mode="out-in">
					<slot />
				</transition>

				<div
					v-if="shouldShowSaveButton"
					class="save-changes"
				>
					<base-button
						type="blue"
						size="medium"
						:loading="rootStore.loading"
						@click="processSaveChanges"
					>
						{{ strings.saveChanges }}
					</base-button>
				</div>
			</grid-container>
		</div>
	</div>
</template>

<script>
import '#/vue/assets/scss/main.scss'

import {
	useNotificationsStore,
	useRootStore
} from '#/vue/stores'

import { getParams, removeParam } from '#/vue/utils/params'
import { useSaveChanges } from '#/vue/composables/SaveChanges'
import { allowed } from '#/vue/utils/allowed'

import BaseButton from '@/vue/components/common/base/Button'
import CoreHeader from '#/vue/components/core/Header'
import CoreMainTabs from '#/vue/components/core/main/Tabs'
import CoreNotifications from '@/vue/components/common/core/Notifications'
import GridContainer from '@/vue/components/common/grid/Container'

import { __ } from '@wordpress/i18n'
const td = import.meta.env.VITE_TEXTDOMAIN

export default {
	setup () {
		const { processSaveChanges } = useSaveChanges()

		return {
			allowed,
			processSaveChanges,
			notificationsStore : useNotificationsStore(),
			rootStore          : useRootStore()
		}
	},
	components : {
		BaseButton,
		CoreHeader,
		CoreMainTabs,
		CoreNotifications,
		GridContainer
	},
	props : {
		showTabs : {
			type : Boolean,
			default () {
				return true
			}
		},
		showSaveButton : {
			type : Boolean,
			default () {
				return true
			}
		},
		excludeTabs : {
			type : Array,
			default () {
				return []
			}
		}
	},
	data () {
		return {
			tabsKey : 0,
			strings : {
				saveChanges : __('Save Changes', td)
			}
		}
	},
	watch : {
		excludeTabs () {
			this.tabsKey += 1
		}
	},
	computed : {
		tabs () {
			return this.$router.options.routes
				.filter(route => route.name && route.meta && route.meta.name)
				.filter(route => this.allowed(route.meta.access))
				.filter(route => !this.excludeTabs.includes(route.name))
				.map(route => {
					return {
						slug   : route.name,
						name   : route.meta.name,
						url    : { name: route.name },
						access : route.meta.access,
						pro    : !!route.meta.pro
					}
				})
		},
		shouldShowSaveButton () {
			if (this.$route?.name) {
				const route = this.$router.options.routes.find(route => route.name === this.$route.name)
				if (route && route.meta && route.meta.hideSaveButton) {
					return false
				}
			}
			return this.showSaveButton
		}
	},
	mounted () {
		if (getParams().notifications) {
			if (!this.notificationsStore.showNotifications) {
				this.notificationsStore.showNotifications = !this.notificationsStore.showNotifications
			}

			setTimeout(() => {
				removeParam('notifications')
			}, 500)
		}
	}
}
</script>