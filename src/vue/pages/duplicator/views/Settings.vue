<template>
	<div class='aioseo-duplicator-settings'>
		<core-card
				slug="generalSettings"
				:header-text="strings.settings"
			>

			<!-- Post Elements -->
			<core-settings-row
					:name="strings.postElements"
				>
				<template #content>
					<base-checkbox
						size="medium margin-bottom"
						v-model="optionsStore.options.general.postElements.all"
					>
						{{ strings.duplicateAllElements }}
					</base-checkbox>

					<core-multi-select-options
						id="postElements"
						v-if="!optionsStore.options.general.postElements.all"
						:options="optionsStore.options.general"
						:rootOptions="rootStore.aioseoDuplicatePost"
						md="3"
						type="postElements"
					/>

				</template>

			</core-settings-row>

			<!-- Exclude Post Meta -->
			<core-settings-row
				:name="strings.dontCopyMeta"
			>
				<template #content>

					<base-select
						multiple
						taggable
						:options="getJsonValue(optionsStore.options.general.dontCopyMeta, []) || []"
						:modelValue="getJsonValue(optionsStore.options.general.dontCopyMeta, []) || []"
						@update:modelValue="values => optionsStore.options.general.dontCopyMeta = setJsonValue(values)"
						:tag-placeholder="strings.tagPlaceholder"
					/>

					<div class="aioseo-description">
						<p v-html="strings.dontCopyMetaDescription"></p>
					</div>
				</template>
			</core-settings-row>

			<!-- Show Links -->
			<core-settings-row
					:name="strings.showLinks"
				>
				<template #content>

					<core-multi-select-options
						id="showLinks"
						:options="optionsStore.options.general"
						:rootOptions="rootStore.aioseoDuplicatePost"
						md="6"
						type="showLinks"
					/>

				</template>

			</core-settings-row>

			<!-- Show Original Item -->
			<core-settings-row
					:name="strings.showOriginal"
				>
				<template #content>

					<!-- <core-multi-select-options
						id="showOriginal"
						:options="optionsStore.options.general"
						:rootOptions="rootStore.aioseoDuplicatePost"
						md="12"
						type="showOriginal"
					/> -->

					<div class="show-original-toggle">
						<base-toggle
							v-model="optionsStore.options.general.showOriginal.metabox"
						/>

						<label>{{ strings.showOriginalMetaboxLabel}}</label>

						<div class="aioseo-description">
							{{ strings.showOriginalMetaboxDescription }}
						</div>
					</div>

					<div class="show-original-toggle">
						<base-toggle
							v-model="optionsStore.options.general.showOriginal.column"
						/>

						<label>{{ strings.showOriginalColumnLabel}}</label>

						<div class="aioseo-description">
							{{ strings.showOriginalColumnDescription }}
						</div>
					</div>

					<div class="show-original-toggle">
						<base-toggle
							v-model="optionsStore.options.general.showOriginal.title"
						/>

						<label>{{ strings.showOriginalTitleLabel}}</label>
					</div>
				</template>

			</core-settings-row>

			<core-settings-row
				:name="strings.postTypes"
			>
				<template #content>
					<base-checkbox
						size="medium margin-bottom"
						v-model="optionsStore.options.general.postTypes.all"
					>
						{{ strings.includeAllPostTypes }}
					</base-checkbox>

					<core-post-type-options
						id="postTypes"
						v-if="!optionsStore.options.general.postTypes.all"
						:options="optionsStore.options.general"
						:excluded="[ 'attachment' ]"
						:registeredPostTypes="rootStore.aioseoDuplicatePost"
						type="postTypes"
					/>

					<div class="aioseo-description">
						{{ strings.selectPostTypes }}
					</div>
				</template>
			</core-settings-row>

			<core-settings-row
				:name="strings.taxonomies"
			>
				<template #content>
					<base-checkbox
						size="medium margin-bottom"
						v-model="optionsStore.options.general.taxonomies.all"
					>
						{{ strings.includeAllTaxonomies }}
					</base-checkbox>

					<core-multi-select-options
						id="taxonomies"
						v-if="!optionsStore.options.general.taxonomies.all"
						:options="optionsStore.options.general"
						:rootOptions="rootStore.aioseoDuplicatePost"
						:showSlugs="showSlugs"
						md="6"
						type="taxonomies"
					/>

					<div class="aioseo-description">
						{{ strings.selectTaxonomies }}
					</div>

				</template>
			</core-settings-row>

			<core-settings-row
				:name="strings.roles"
			>
				<template #content>
					<base-checkbox
						size="medium margin-bottom"
						v-model="optionsStore.options.general.roles.all"
					>
						{{ strings.includeAllRoles }}
					</base-checkbox>

					<core-multi-select-options
						id="roles"
						v-if="!optionsStore.options.general.roles.all"
						:options="optionsStore.options.general"
						:rootOptions="rootStore.aioseoDuplicatePost"
						md="3"
						type="roles"
					/>

					<div class="aioseo-description">
						{{ strings.selectRoles }}
					</div>

				</template>
			</core-settings-row>

			<!-- Title Prefix -->
			<core-settings-row
				:name="strings.titlePrefix"
			>
				<template #content>
					<base-input
						:modelValue="sanitizeString(optionsStore.options.general.titlePrefix)"
						@update:modelValue="value => optionsStore.options.general.titlePrefix = sanitizeString(value)"
						size="medium"
					/>

					<div class="aioseo-description">
						{{ strings.titlePrefixDescription }}
					</div>
				</template>
			</core-settings-row>

			<!-- Title Suffix -->
			<core-settings-row
				:name="strings.titleSuffix"
			>
				<template #content>
					<base-input
						:modelValue="sanitizeString(optionsStore.options.general.titleSuffix)"
						@update:modelValue="value => optionsStore.options.general.titleSuffix = sanitizeString(value)"
						size="medium"
					/>

					<div class="aioseo-description">
						{{ strings.titleSuffixDescription }}
					</div>
				</template>
			</core-settings-row>
		</core-card>
	</div>
</template>

<script>
import {
	useOptionsStore,
	useRootStore
} from '#/vue/stores'

import { GLOBAL_STRINGS } from '#/vue/plugins/constants'
import links from '#/vue/utils/links'
import { ref } from 'vue'

import { sanitizeString } from '@/vue/utils/strings'
import { useJsonValues } from '@/vue/composables/JsonValues'

import CoreCard from '@/vue/components/common/core/Card'
import CoreSettingsRow from '@/vue/components/common/core/SettingsRow'
import BaseCheckbox from '@/vue/components/common/base/Checkbox'
import BaseInput from '@/vue/components/common/base/Input'
import BaseSelect from '@/vue/components/common/base/Select'
import BaseToggle from '@/vue/components/common/base/Toggle'
import CoreMultiSelectOptions from '#/vue/components/core/MultiSelectOptions'
import CorePostTypeOptions from '@/vue/components/common/core/PostTypeOptions'

import { __, sprintf } from '@wordpress/i18n'
const td = import.meta.env.VITE_TEXTDOMAIN

export default {
	setup () {
		const { getJsonValue, setJsonValue } = useJsonValues()

		const showSlugs = ref(true)

		const strings = {
			settings                : __('Settings', td),
			postElements            : __('Elements to Duplicate', td),
			showLinks               : __('Show These Links', td),
			showOriginal            : __('Show Original Post', td),
			duplicateAllElements    : __('Duplicate all elements', td),
			dontCopyMeta            : __('Fields To Ignore', td),
			tagPlaceholder          : __('Press enter to add field', td),
			dontCopyMetaDescription : __('Enter a list of meta fields that should not be copied over. <br>You can use * as a wildcard to match zero or more alphanumeric characters or underscores: e.g. "field*"', td),
			titlePrefix             : __('Title Prefix', td),
			titlePrefixDescription  : __('Prefix to be added before the title, e.g. "Copy of" (blank for no prefix).', td),
			titleSuffix             : __('Title Suffix', td),
			titleSuffixDescription  : __('Suffix to be added after the title, e.g. "(dup)" (blank for no prefix).', td),
			advancedSettings        : __('Advanced', td),
			postTypes              	: __('Post Types', td),
			includeAllPostTypes     : __('Include all post types', td),
			selectPostTypes         : sprintf(
				// Translators: 1 - The plugin name ("Duplicate Post").
				__('Select which post types you want to enable %1$s for.', td),
				import.meta.env.VITE_NAME
			),
			postStatuses           : __('Post Statuses', td),
			includeAllPostStatuses : __('Include all post statuses', td),
			selectPostStatuses     : sprintf(
				// Translators: 1 - The plugin name ("Duplicate Post").
				__('Select which post statuses you want to enable %1$s for.', td),
				import.meta.env.VITE_NAME
			),
			roles           : __('Roles', td),
			includeAllRoles : __('Include all roles', td),
			selectRoles     : sprintf(
				// Translators: 1 - The plugin name ("Duplicate Post").
				__('Select which user roles you want to enable %1$s for.', td),
				import.meta.env.VITE_NAME
			),
			taxonomies           : __('Taxonomies', td),
			includeAllTaxonomies : __('Include all taxonomies', td),
			selectTaxonomies     : sprintf(
				__('Select which taxonomies you want to copy when you duplicate a post/page.', td),
				import.meta.env.VITE_NAME
			),
			showOriginalMetaboxLabel       : __('In a metabox in the Edit screen', td),
			showOriginalMetaboxDescription : __('You\'ll also be able to delete the reference to the original item with a checkbox.', td),
			showOriginalColumnLabel        : __('In a column in the Post list', td),
			showOriginalColumnDescription  : __('You\'ll also be able to delete the reference to the original item with a checkbox in Quick Edit.', td),
			showOriginalTitleLabel         : __('After the title in the Post list', td)
		}

		return {
			globalStrings : GLOBAL_STRINGS,
			links,
			optionsStore  : useOptionsStore(),
			rootStore     : useRootStore(),
			getJsonValue,
			setJsonValue,
			strings,
			showSlugs
		}
	},
	components : {
		CoreCard,
		CoreSettingsRow,
		BaseCheckbox,
		BaseInput,
		BaseSelect,
		BaseToggle,
		CoreMultiSelectOptions,
		CorePostTypeOptions
	},
	methods : {
		checkInteger (value) {
			if (Number.isInteger(parseInt(value))) {
				return 0 > value ? 0 : value
			}
			return null
		},
		sanitizeString
	}
}
</script>

<style lang="scss" scoped>
.aioseo-description p {
	margin: 0;
}

.show-original-toggle {
	margin: 18px 0;

	&:first-of-type {
		margin-top: 0;
	}

	&:last-of-type {
		margin-bottom: 0;
	}
}
</style>