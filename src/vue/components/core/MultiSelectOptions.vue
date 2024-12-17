<template>
	<div class="aioseo-multiselect-options-toggle">
		<div
			class="multiselect-options-settings"
		>
			<grid-row
				v-if="0 < postTypes.length"
			>
				<grid-column
					:md="md"
					v-for="(type, index) in postTypes"
					:key="index"
				>
					<base-highlight-toggle
						size="medium"
						:active="isActive(type)"
						:name="type.name"
						type="checkbox"
						:modelValue="getValue(type)"
						@update:modelValue="checked => updateValue(checked, type)"
					>
						{{ type.label }} <template v-if="showSlugs">({{ type.slug }})</template>
					</base-highlight-toggle>

					<div class="aioseo-description" v-if="type.description">
						{{ type.description }}
					</div>

				</grid-column>
			</grid-row>
		</div>
	</div>
</template>

<script>
import {
	useRootStore
} from '$/vue/stores'

import BaseHighlightToggle from '@/vue/components/common/base/HighlightToggle'
import GridColumn from '@/vue/components/common/grid/Column'
import GridRow from '@/vue/components/common/grid/Row'

export default {
	setup () {
		return {
			rootStore : useRootStore()
		}
	},
	components : {
		BaseHighlightToggle,
		GridColumn,
		GridRow
	},
	props : {
		type : {
			type     : String,
			required : true
		},
		options : {
			type     : Object,
			required : true
		},
		rootOptions : Object,
		excluded    : {
			type : Array,
			default () {
				return []
			}
		},
		showSlugs : {
			type    : Boolean,
			default : false
		},
		md : {
			type    : String,
			default : '3'
		}
	},
	data () {
		return {
			strings : {
				label : this.$t.__('Label:', this.$td),
				name  : this.$t.__('Slug:', this.$td)
			}
		}
	},
	computed : {
		getRootStoreOptions () {
			return this.rootOptions || this.rootStore.aioseo.postData
		},
		postTypes () {
			return this.getRootStoreOptions[this.type].filter(postType => {
				return !this.excluded.includes(postType.name)
			})
		}
	},
	methods : {
		emitInput (value) {
			this.$emit('input', value)
		},
		updateValue (checked, type) {
			if (checked) {
				const included = this.options[this.type].included
				included.push(type.slug)
				this.options[this.type].included = included
				return
			}

			const index = this.options[this.type].included.findIndex(t => t === type.slug)
			if (-1 !== index) {
				this.options[this.type].included.splice(index, 1)
			}
		},
		getValue (type) {
			return this.options[this.type].included.includes(type.slug)
		},
		isActive (type) {
			const index = this.options[this.type].included.findIndex(t => t === type.slug)
			if (-1 !== index) {
				return true
			}

			return false
		}
	}
}
</script>

<style lang="scss">
.aioseo-multiselect-options-toggle {
	+ div.aioseo-description {
		margin-top: 16px;
	}
}
</style>