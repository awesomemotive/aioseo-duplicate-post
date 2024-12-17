<template>
	<core-main :showSaveButton="false">
		<component :is="$route.name" />
	</core-main>
</template>

<script>
import AboutUs from './AboutUs'
import CoreMain from '#/vue/components/core/main/Index.vue'

export default {
	components : {
		AboutUs,
		CoreMain
	}
}
</script>