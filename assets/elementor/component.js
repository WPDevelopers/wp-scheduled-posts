// import * as hooks from './hooks';
// import * as commands from './commands/';
// import Repeater from './repeater';
import after from './after';

export default class extends $e.modules.ComponentBase {
	pages = {};

	__construct( args ) {
		super.__construct( args );
		elementor.on( 'panel:init', (e) => {
			console.log('panel:init', args);
			// args.manager.addPanelPages();

			args.manager.addPanelMenuItem();
		} );

		// elementor.hooks.addFilter( 'panel/header/behaviors', args.manager.addHeaderBehavior );

		// elementor.addControlView( 'global-style-repeater', Repeater );
	}

	getNamespace() {
		return 'wpsp/schedule';
	}

	defaultRoutes() {
		return {
			menu: () => {
				elementor.getPanelView().setPage( 'kit_menu' );
			},
		};
	}

	// defaultCommands() {
	// 	return this.importCommands( commands );
	// }

	defaultShortcuts() {
		return {
			open: {
				keys: 'ctrl+k',
				dependency: () => {
					return 'kit' !== elementor.documents.getCurrent().config.type;
				},
			},
			back: {
				keys: 'esc',
				scopes: [ 'panel' ],
				dependency: () => {
					return elementor.documents.isCurrent( elementor.config.kit_id ) && ! jQuery( '.dialog-widget:visible' ).length;
				},
			},
		};
	}

	defaultHooks() {
		return this.importHooks( {after} );
	}

	renderTab( tab ) {
		console.log('tab', tab);
		elementor.getPanelView().setPage( 'kit_settings' ).content.currentView.activateTab( tab );
	}
}