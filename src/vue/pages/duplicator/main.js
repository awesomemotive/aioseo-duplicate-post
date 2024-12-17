import { createApp } from 'vue'

import loadPlugins from '#/vue/plugins'
import { loadPiniaStores } from '#/vue/stores'

import App from './App.vue'
import startRouter from '#/vue/router'
import paths from './router/paths'

let app = createApp(App)
app     = loadPlugins(app)

const router = startRouter(paths, app)
router.app   = app
app.use(router)

loadPiniaStores(app, router)

// // Set state from the window object.
app.mount('#aioseo-duplicate-post-app')

export default app