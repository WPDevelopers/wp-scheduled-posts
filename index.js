const { registerPlugin } = wp.plugins;
import { default as AdminPanel } from './admin/gutenpost';

registerPlugin(
	'wps-publish-date',
	{
		render: AdminPanel
	}
);
