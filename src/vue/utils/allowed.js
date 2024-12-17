import {
	useRootStore
} from '#/vue/stores'

export const allowed = (permission) => {
	const rootStore = useRootStore()
	const user      = rootStore.aioseoDuplicatePost.user
	return !!user.canManage || !!(user.capabilities && user.capabilities[permission])
}