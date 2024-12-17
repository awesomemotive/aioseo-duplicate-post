if (!window.wp?.blockEditor && window.wp?.blocks && window.wp.oldEditor) {
	window.wp.blockEditor = window.wp.editor
}

export const isBlockEditor = () => {
	return document.body.classList.contains('block-editor-page') && window.wp.data && canLoadBlocks()
}

export const isClassicEditor = () => {
	return !!document.querySelector('#wp-content-wrap.tmce-active, #wp-content-wrap.html-active')
}

export const isClassicNoEditor = () => {
	return document.querySelector('#post input#title') && !document.querySelector('#wp-content-wrap')
}

export const isElementorEditor = () => {
	return !!(document.body.classList.contains('elementor-editor-active') && window.elementor)
}

export const isDiviEditor = () => {
	return !!(document.body.classList.contains('et_pb_pagebuilder_layout') && window.ET_Builder)
}

export const isSeedProdEditor = () => {
	return !!(document.body.classList.contains('seedprod-builder') && window.seedprod_data)
}

export const isWPBakeryEditor = () => {
	return !!(window.vc && window.vc_mode)
}

export const isAvadaEditor = () => {
	return (window.FusionApp || window.FusionPageBuilderApp)?.builderActive
}

export const isThriveArchitectEditor = () => {
	return !!(window.TVE && window.TVE.Editor_Page)
}

export const isSiteOriginEditor = () => {
	const visible = (el) => !!(el?.offsetWidth || el?.offsetHeight || el?.getClientRects().length)

	const isBlockEditorPanelsEnabled   = document.querySelectorAll('.block-editor-page').length && 'undefined' !== typeof window.soPanelsBuilderView
	const isClassicEditorPanelsEnabled = visible(document.querySelector('#so-panels-panels.attached-to-editor'))

	return isBlockEditorPanelsEnabled || isClassicEditorPanelsEnabled
}

export const isPageBuilderEditor = () => {
	return (
		isElementorEditor() ||
		isDiviEditor() ||
		isSeedProdEditor() ||
		isWPBakeryEditor() ||
		isAvadaEditor() ||
		isSiteOriginEditor() ||
		isThriveArchitectEditor()
	)
}

export const canLoadBlocks = () => {
	const wp = window.wp
	return ('undefined' !== typeof wp && 'undefined' !== typeof wp.blocks && 'undefined' !== typeof wp.blockEditor)
}