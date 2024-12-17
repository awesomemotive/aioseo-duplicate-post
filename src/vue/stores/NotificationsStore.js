import { defineStore } from 'pinia'
import http from '#/vue/utils/http'
import links from '#/vue/utils/links'

const clearNotificationNotices = notifications => {
	const notificationCount = document.querySelector('.aioseo-menu-notification-counter')
	if (notificationCount) {
		if (notifications.active.length) {
			notificationCount.innerText = notifications.active.length
		} else {
			notificationCount.remove()
		}
	}
}

export const useNotificationsStore = defineStore('NotificationStore', {
	state : () => ({
		active            : [],
		new               : [],
		dismissed         : [],
		force             : false,
		showNotifications : false
	}),
	getters : {
		activeNotifications         : state => state.active,
		activeNotificationsCount    : state => state.active.length,
		dismissedNotifications      : state => state.dismissed,
		dismissedNotificationsCount : state => state.dismissed.length
	},
	actions : {
		dismissNotifications (notifications) {
			const reversed = notifications.reverse()
			reversed.forEach(slug => {
				const notificationIndex = this.active.findIndex(n => n.slug === slug)
				if (-1 !== notificationIndex) {
					this.active.splice(notificationIndex, 1)
				}
			})

			return http.post(links.restUrl('notifications/dismiss'))
				.send(notifications)
				.then(response => {
					if (!response.body.success) {
						throw new Error(response.body.message)
					}

					this.updateNotifications(response.body.notifications)
				})
		},
		updateNotifications (notifications) {
			if (notifications.new.length && window.aioseoDuplicatePostNotifications) {
				window.aioseoDuplicatePostNotifications.newNotifications = notifications.new.length
			}

			this.active    = notifications.active
			this.new       = notifications.new
			this.dismissed = notifications.dismissed

			clearNotificationNotices(notifications)
		},
		toggleNotifications () {
			this.showNotifications = !this.showNotifications
		}
	}
})