import { __ } from '@wordpress/i18n'

const td = import.meta.env.VITE_TEXTDOMAIN

export const GLOBAL_STRINGS = {
	no        : __('No', td),
	yes       : __('Yes', td),
	off       : __('Off', td),
	on        : __('On', td),
	show      : __('Show', td),
	hide      : __('Hide', td),
	learnMore : __('Learn More', td),
	disabled  : __('Disabled', td),
	enabled   : __('Enabled', td),
	include   : __('Include', td),
	remove    : __('Remove', td)
}