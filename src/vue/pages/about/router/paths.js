import { __ } from '@wordpress/i18n'

const td       = import.meta.env.VITE_TEXTDOMAIN
const loadView = view => {
	return () => import(`../views/${view}.vue`)
}

export default [
	{
		path     : '/:pathMatch(.*)*',
		redirect : '/about-us'
	},
	{
		path      : '/about-us',
		name      : 'about-us',
		component : loadView('Main'),
		meta      : {
			access : 'aioseo_duplicate_post_about_us_page',
			name   : __('About Us', td)
		}
	}
]