const { registerPlugin } = wp.plugins;
import { default as AdminPublishButton } from './admin/assets/gutenberg';

registerPlugin(
	'wps-publish-button',
	{
		render: AdminPublishButton
	}
);
