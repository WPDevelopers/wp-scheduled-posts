

export class KitAfterSave extends $e.modules.hookData.After {

	register() {
		$e.hooks.registerDataAfter( this );
	}

	getCommand() {
		return 'document/save/save';
	}

	getConditions( args ) {
		const { status, document = elementor.documents.getCurrent() } = args;
		return 'publish' === status;
	}

	getId() {
		return 'wpsp-after-save';
	}

	apply( args ) {
		// On save clear cache of all edited documents and dynamic tags.
		// This is needed because when returning to the editor after saving the kit, it was still displaying the old data.
		this.clearDocumentCache();
		this.clearDynamicTagsCache();
		console.log('after ', args);

		if ( 'publish' === args.status ) {
			elementor.notifications.showToast( {
				message: __( 'Your changes have been updated.', 'elementor' ),
				buttons: [
					{
						name: 'back_to_editor',
						text: __( 'Back to Editor', 'elementor' ),
						callback() {
							$e.run( 'panel/global/close' );
						},
					},
				],
			} );
		}

		if ( elementor.activeBreakpointsUpdated ) {
			const reloadConfirm = elementorCommon.dialogsManager.createWidget( 'alert', {
				id: 'elementor-save-kit-refresh-page',
				headerMessage: __( 'Reload Elementor Editor', 'elementor' ),
				message: __( 'You have made modifications to the list of Active Breakpoints. For these changes to take effect, you need to reload Elementor Editor.', 'elementor' ),
				position: {
					my: 'center center',
					at: 'center center',
				},
				strings: {
					confirm: __( 'Reload Now', 'elementor' ),
				},
				onConfirm: () => location.reload(),
			} );

			reloadConfirm.show();
		}
	}

	clearDocumentCache() {
		Object.keys( elementor.documents.documents ).forEach( ( id ) => {
			elementor.documents.invalidateCache( id );
		} );
	}

	clearDynamicTagsCache() {
		elementor.dynamicTags.cleanCache();
		elementor.dynamicTags.loadCacheRequests();
	}
}

export default KitAfterSave;