/* global window, document */
if (!window._babelPolyfill) {
    require('@babel/polyfill/noConflict')
}

import React from 'react'
import ReactDOM from 'react-dom'
import Admin from './containers/Admin'
document.addEventListener('DOMContentLoaded', function () {
    ReactDOM.render(
        <Admin wpspObject={window.wpspSettingsGlobal} />,
        document.getElementById('wpsp-dashboard-body')
    )
})
