import { ref, computed, onMounted } from 'vue'

import {
	useSettingsStore
} from '#/vue/stores'

import { useScrollTo } from '@/vue/composables/ScrollTo'

export function useWpTable ({
	changeItemsPerPageSlug,
	fetchData,
	slug,
	tableId
}) {
	const filter          = ref('all')
	const orderBy         = ref(null)
	const orderDir        = ref('asc')
	const pageNumber      = ref(1)
	const resultsPerPage  = ref(20)
	const searchTerm      = ref(null)
	const selectedFilters = ref(null)
	const wpTableKey      = ref(0)
	const wpTableLoading  = ref(false)
	const settingsStore   = useSettingsStore()
	const scrollTo        = useScrollTo().scrollTo

	const refreshTable = () => {
		wpTableLoading.value = true

		return processFetchTableData()
			.then(() => {
				wpTableLoading.value = false
			})
	}

	const processAdditionalFilters = ({ filters }) => {
		wpTableLoading.value  = true

		processFetchTableData(filters)
			.then(() => (wpTableLoading.value = false))
	}

	const processSearch = (term) => {
		pageNumber.value     = 1
		searchTerm.value     = term
		wpTableLoading.value = true

		processFetchTableData()
			.then(() => (wpTableLoading.value = false))
	}

	const processPagination = (number) => {
		pageNumber.value     = number
		wpTableLoading.value = true

		processFetchTableData()
			.then(() => (wpTableLoading.value = false))
	}

	const processFilterTable = (tableFilter) => {
		filter.value         = tableFilter.slug
		searchTerm.value     = null
		pageNumber.value     = 1
		wpTableLoading.value = true

		resetSelectedFilters()

		processFetchTableData()
			.then(() => (wpTableLoading.value = false))
	}

	const processChangeItemsPerPage = (newNumber) => {
		pageNumber.value     = 1
		resultsPerPage.value = newNumber
		wpTableLoading.value = true

		settingsStore.changeItemsPerPage({
			slug  : changeItemsPerPageSlug,
			value : newNumber
		})
			.then(() => processFetchTableData()
				.then(() => scrollTo(tableId))
			)
			.then(() => (wpTableLoading.value = false))
	}

	const processSort = (column, event) => {
		event.target.blur()
		orderBy.value        = column.slug
		orderDir.value       = orderBy.value !== column.slug ? column.sortDir : ('asc' === column.sortDir ? 'desc' : 'asc')
		wpTableLoading.value = true

		processFetchTableData()
			.then(() => (wpTableLoading.value = false))
	}

	const offset = computed(() => {
		return 1 === pageNumber.value ? 0 : (pageNumber.value - 1) * resultsPerPage.value
	})

	const processFetchTableData = (additionalFilters = selectedFilters.value) => {
		return fetchData({
			slug,
			orderBy    : orderBy.value,
			orderDir   : orderDir.value,
			limit      : resultsPerPage.value,
			offset     : offset.value,
			searchTerm : searchTerm.value,
			filter     : filter.value,
			additionalFilters
		})
	}

	// TODO: Probably eliminate this altogether.
	const resetSelectedFilters = () => {
		// Implementation of resetting selected filters
	}

	onMounted(() => {
		resultsPerPage.value = settingsStore.settings.tablePagination[changeItemsPerPageSlug] || resultsPerPage.value
	})

	return {
		filter,
		orderBy,
		orderDir,
		pageNumber,
		processAdditionalFilters,
		processChangeItemsPerPage,
		processFetchTableData,
		processFilterTable,
		processPagination,
		processSearch,
		processSort,
		refreshTable,
		resetSelectedFilters,
		resultsPerPage,
		searchTerm,
		selectedFilters,
		wpTableKey,
		wpTableLoading
	}
}