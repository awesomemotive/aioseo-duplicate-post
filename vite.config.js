import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
// import react from '@vitejs/plugin-react'
import liveReload from 'vite-plugin-live-reload'
import postcssRTLCSS from 'postcss-rtlcss'
import replace from '@rollup/plugin-replace'
import del from 'rollup-plugin-delete'
import path from 'path'
import fs from 'fs'
import * as dotenv from 'dotenv'

// i18n parser
import i18n from './build/aioseo-rollup-plugin-gettext-vue'

// Convert JSON to PHP.
import jsonToPhp from './build/aioseo-rollup-plugin-json-to-php'

const getPages = () => {
	return {
		duplicator : './src/vue/pages/duplicator/main.js',
		about      : './src/vue/pages/about/main.js'
	}
}

// Standalone Vue scripts.
const getStandalones = () => {
	return {
		'review-notice'  : './src/vue/standalone/review-notice/index.js',
		'duplicate-post' : './src/vue/standalone/duplicate-post/main.js'
	}
}

export default () => {
	dotenv.config({ path: './build/.env', override: true })

	return defineConfig({
		plugins : getPlugins(),
		base    : '',
		envDir  : './build',
		build   : {
			assetsInlineLimit : 0, // We need to disable this as it converts small images to base64 inline, but that breaks our inline image function that we use to dynamically set the image url.
			manifest          : true, // We use a manifest to load our files inside of WordPress.
			outDir            : 'dist/', // This is where we put the assets for the current build.
			assetsDir         : '',
			rollupOptions     : {
				input : {
					...getPages(),
					...getStandalones()
				},
				output : {
					dir            : 'dist/assets/',
					assetFileNames : assetInfo => {
						const images = [
							'.png',
							'.jpg',
							'.jpeg',
							'.gif'
						]

						if (images.includes(path.extname(assetInfo.name))) {
							return 'images/[name].[hash][extname]'
						}

						return '[ext]/[name].[hash][extname]'
					},
					chunkFileNames : 'js/[name].[hash].js'
				},
				plugins : [
					del({
						targets : 'dist/*',
						verbose : true,
						runOnce : true,
						hook    : 'buildStart'
					}),
					i18n({
						exclude     : 'node_modules/**',
						include     : '**/*@(vue|js|jsx)',
						textDomains : getTextDomains()
					}),
					jsonToPhp([
						{
							from : 'dist/assets/.vite/manifest.json',
							to   : 'dist/manifest.php'
						}
					])
				]
			}
		},
		optimizeDeps : {
			force   : true,
			include : [
				'lodash-es',
				'luxon',
				'quill',
				'superagent',
				'vue-scrollto',
				'react'
			],
			exclude : [
				'#/vue/plugins/constants',
				'#/vue/store'
			]
		},
		server : {
			https      : getHttps(),
			cors       : true,
			strictPort : true,
			port       : process.env.VITE_AIOSEO_DUPLICATE_POST_DEV_PORT,
			host       : process.env.VITE_AIOSEO_DUPLICATE_POST_DOMAIN,
			hmr        : {
				port : process.env.VITE_AIOSEO_DUPLICATE_POST_DEV_PORT,
				host : process.env.VITE_AIOSEO_DUPLICATE_POST_DOMAIN
			}
		},
		resolve : {
			alias : [
				{
					find        : '@',
					replacement : path.resolve(__dirname, '..', 'all-in-one-seo-pack-pro', 'src')
				},
				{
					find        : '#',
					replacement : path.resolve(__dirname, 'src')
				},
				{
					find        : '$',
					replacement : path.resolve(__dirname, 'src')
				}
			],
			extensions : [
				'.mjs',
				'.js',
				'.ts',
				'.jsx',
				'.tsx',
				'.json',
				'.vue'
			]
		},
		css : {
			postcss : {
				plugins : [
					postcssRTLCSS()
				]
			},
			preprocessorOptions : {
				scss : {
					additionalData : [
						'@use "../all-in-one-seo-pack-pro/src/vue/assets/scss/app/variables.scss" as *;',
						'@use "../all-in-one-seo-pack-pro/src/vue/assets/scss/app/mixins.scss" as *;',
						''
					].join('\n')
				}
			}
		},
		experimental : {
			renderBuiltUrl : (filename, { hostType }) => {
				return 'js' === hostType
					? { runtime: `window.__aioseoDynamicImportPreload__(${JSON.stringify(filename)})` }
					: { relative: true }
			}
		}
	})
}

const getHttps = () => {
	let https = false
	if (process.env.VITE_AIOSEO_DUPLICATE_POST_HTTP) {
		return false
	}

	try {
		// Generate a certificate using: `mkcert aioseo.local` in the build/ directory.
		if (fs.existsSync('./build/' + process.env.VITE_AIOSEO_DUPLICATE_POST_DOMAIN + '-key.pem')) {
			https = {
				key  : fs.readFileSync('./build/' + process.env.VITE_AIOSEO_DUPLICATE_POST_DOMAIN + '-key.pem'),
				cert : fs.readFileSync('./build/' + process.env.VITE_AIOSEO_DUPLICATE_POST_DOMAIN + '.pem'),
				ca   : fs.readFileSync(process.env.CRT_ROOT_CA)
			}
		}
	} catch (error) {
		console.error(error)
	}

	return https
}

const getPlugins = () => {
	const plugins = [
		replace({
			preventAssignment : true,
			values            : {
				AIOSEO_VERSION : 'pro'
			}
		}),
		vue()
	]

	const reload = [
		`${process.cwd()}/build/.env`
	]

	if (process.env.PHP_LIVE_RELOAD) {
		if (process.env.WP_CONFIG_LOCATION) {
			reload.push(`${process.cwd()}/app/**/*.php`)
			reload.push(process.env.WP_CONFIG_LOCATION)
		}
	}

	plugins.push(liveReload(reload, { root: '/' }))

	return plugins
}

const getTextDomains = () => {
	return [
		{
			path    : /aioseo-duplicate-post\/.*$/,
			output  : './languages/aioseo-duplicate-post.php',
			domain  : 'duplicate-post-page-aioseo',
			matches : [
				'this.$td',
				'td',
				'({}).VITE_TEXTDOMAIN',
				'define_import_meta_env_default.VITE_TEXTDOMAIN'
			]
		}
	]
}