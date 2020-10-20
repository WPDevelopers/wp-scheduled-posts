/* global window, document */
if (!window._babelPolyfill) {
    require('@babel/polyfill/noConflict')
}

import React from 'react'
import ReactDOM from 'react-dom'
import { Provider } from 'react-redux'
import store from './redux/store'
import Admin from './containers/Admin'
document.addEventListener('DOMContentLoaded', function () {
    console.log(window.wpspSettingsGlobal)
    ReactDOM.render(
        <Provider store={store}>
            <Admin wpspObject={window.wpspSettingsGlobal} />
        </Provider>,
        document.getElementById('wpsp-dashboard-body')
    )
})
