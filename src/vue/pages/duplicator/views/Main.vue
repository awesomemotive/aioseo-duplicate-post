<template>
	<core-main
		:showTabs="true"
		showSaveButton
	>
		<component :is="$route.name" />
	</core-main>
</template>

<script>
import CoreMain from '#/vue/components/core/main/Index.vue'
import Settings from './Settings.vue'

export default {
	components : {
		CoreMain,
		Settings
	}
}
</script>

<style lang="scss">
	.aioseo-checkbox.margin-bottom {
		margin-bottom: 16px;
	}
</style>