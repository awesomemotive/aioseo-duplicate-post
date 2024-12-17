import {
	useOptionsStore,
	useRootStore
} from '#/vue/stores'

export function useSaveChanges () {
	const processSaveChanges = () => {
		const rootStore = useRootStore()
		rootStore.loading = true

		let switchBack = false,
			saved = false

		const action = 'saveChanges'

		setTimeout(() => {
			switchBack = true
			if (saved) {
				rootStore.loading = false
			}
		}, 1500)

		const optionsStore = useOptionsStore()
		optionsStore[action]()
			.then(response => {
				if (response && response.body.redirection) {
					return
				}

				if (switchBack) {
					rootStore.loading = false
				} else {
					saved = true
				}

				window.aioseoDuplicatePostBus.$emit('changes-saved')
			})
	}

	return {
		processSaveChanges
	}
}