import * as translate from '@wordpress/i18n'

if (window.aioseoDuplicatePost.translations) {
	translate.setLocaleData(window.aioseoDuplicatePost.translations.translations, import.meta.env.VITE_TEXTDOMAIN)
} else {
	console.warn('Translations couldn\'t be loaded.')
}
export default translate