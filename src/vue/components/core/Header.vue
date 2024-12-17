<template>
	<div class="aioseo-header">
		<core-api-bar
			v-if="!rootStore.pong"
		/>

		<grid-container
			:full-width="fullWidth"
			:small="small"
		>
			<div class="aioseo-header-content">
				<div class="logo">
					<svg-logo/>
				</div>

				<div
					v-if="actions"
					class="header-actions"
				>
					<span
						class="round"
						@click.stop="notificationsStore.toggleNotifications"
					>
						<span class="round number"
							v-if="notificationsStore.activeNotificationsCount"
						>
							{{ notificationsStore.activeNotificationsCount > 9 ? '!' : notificationsStore.activeNotificationsCount }}
						</span>

						<svg-notifications
							@click.stop="notificationsStore.toggleNotifications"
						/>
					</span>
				</div>
			</div>
		</grid-container>
	</div>
</template>

<script>
import {
	useNotificationsStore,
	useRootStore
} from '#/vue/stores'

import CoreApiBar from '@/vue/components/common/core/ApiBar'
import GridContainer from '@/vue/components/common/grid/Container'
import SvgLogo from '#/vue/components/svg/Logo'
import SvgNotifications from '@/vue/components/common/svg/Notifications'

export default {
	setup () {
		return {
			notificationsStore : useNotificationsStore(),
			rootStore          : useRootStore()
		}
	},
	components : {
		CoreApiBar,
		GridContainer,
		SvgLogo,
		SvgNotifications
	},
	props : {
		fullWidth : Boolean,
		small     : Boolean,
		pageName  : String,
		actions   : {
			type : Boolean,
			default () {
				return true
			}
		},
		upgradeBar : {
			type : Boolean,
			default () {
				return true
			}
		}
	},
	methods : {
		debounce (fn) {
			let frame
			return (...params) => {
				if (frame) {
					cancelAnimationFrame(frame)
				}
				frame = requestAnimationFrame(() => {
					fn(...params)
				})
			}
		},
		storeScroll () {
			document.documentElement.dataset.scroll = window.scrollY
		},
		toggleModal () {
			const modal = document.getElementById('aioseo-help-modal')
			modal.classList.toggle('visible')
			document.body.classList.toggle('modal-open')
		}
	},
	mounted () {
		this.storeScroll()
		document.addEventListener('scroll', this.debounce(this.storeScroll), { passive: true })
	}
}
</script>

<style lang="scss">
@use 'sass:color';

html:not([data-scroll='0']) {
	.aioseo-header {
		box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.05);
		transition: box-shadow 0.6s;
	}
}

.aioseo-header {
	position: fixed;
	z-index: 1051;
	right: 0;
	left: 0;
	background-color: #fff;
	height: var(--aioseo-header-height, 72px);
	color: $black;

	.mascot {
		width: 35px;
		height: auto;
		margin-right: 10px;
		gap: 4px;
	}

	.aioseo-header-content {
		padding: 0;
		display: flex;
		height: 72px;
		align-items: center;

		a:focus {
			box-shadow: none;
		}

		div.logo {
			flex: 1 0 auto;
		}

		svg.aioseo-logo {
			height: 20px;
		}

		.header-actions {
			display: flex;

			.round {
				position: relative;
				background-color: $background;
				border-radius: 50%;
				width: 40px;
				height: 40px;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-left: 10px;
				cursor: pointer;
				transition: background-color 0.2s ease;

				svg {
					width: 20px;
					height: 20px;
				}

				&:hover {
					background-color: color.adjust($background, $lightness: -5%);
				}
			}

			.number {
				position: absolute;
				background-color: $red;
				width: 16px;
				height: 16px;
				font-weight: 600;
				font-size: 10px;
				color: #fff;
				top: -8px;
				left: 50%;
				transform: translateX(-50%);
				margin: 0;
				animation: bounce 2s 5;

				&:hover {
					background-color: $red;
				}

				@keyframes bounce {
					0%, 25%, 50%, 75%, 100% {
						transform: translateX(-50%) translateY(0);
					}
					40% {
						transform: translateX(-50%) translateY(-8px);
					}
					60% {
						transform: translateX(-50%) translateY(-4px);
					}
				}
			}
		}
	}

	.fade-percent-circle-enter-active, .fade-percent-circle-leave-active {
		transition: opacity .5s;
	}
	.fade-percent-circle-enter, .fade-percent-circle-leave-to {
		opacity: 0;
	}
}
</style>