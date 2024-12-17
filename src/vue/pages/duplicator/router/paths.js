import { __ } from '@wordpress/i18n'

const td       = import.meta.env.VITE_TEXTDOMAIN
const loadView = view => {
	return () => import(`../views/${view}.vue`)
}

export default [
	{
		path     : '/:pathMatch(.*)*',
		redirect : '/settings'
	},
	{
		path      : '/settings',
		name      : 'settings',
		component : loadView('Main'),
		meta      : {
			access         : 'aioseo_duplicate_post_settings',
			name           : __('Settings', td),
			hideSaveButton : false
		}
	}
]