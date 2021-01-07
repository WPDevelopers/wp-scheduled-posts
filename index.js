const { registerPlugin } = wp.plugins
import { default as AdminPublishButton } from './assets/gutenberg'

registerPlugin('wps-publish-button', {
    render: AdminPublishButton,
})
