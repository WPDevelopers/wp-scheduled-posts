( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const editor = wp.editor || wp.editPost;
	const { PluginPostStatusInfo } = editor;
	const { __ } = wp.i18n;
	const { createElement: el } = wp.element;

	const openSchedulePressModal = () => {
		const modal = document.getElementById( 'wpsp-post-panel-modal' );
		if ( modal ) {
			modal.classList.add( 'wpsp-post-panel-active' );
			document.body.style.overflow = 'hidden';
			return;
		}
		const metaboxBtn = document.getElementById( 'wpsp-post-panel-button' );
		if ( metaboxBtn ) {
			metaboxBtn.click();
		}
	};

	const ScheduleAndShareSlot = () =>
		el(
			PluginPostStatusInfo,
			null,
			el(
				'div',
				{ id: 'wpsp-post-panel-wrapper-gutenberg' },
				el(
					'p',
					null,
					__(
						'Manage your entire publishing workflow and social sharing from a single, centralized hub.',
						'wp-scheduled-posts'
					)
				),
				el(
					'button',
					{
						type: 'button',
						id: 'wpsp-post-panel-button-gutenberg',
						className: 'wpsp-post-panel-button',
						onClick: openSchedulePressModal,
					},
					__( 'Schedule And Share', 'wp-scheduled-posts' )
				)
			)
		);

	registerPlugin( 'schedulepress-sidebar', {
		render: ScheduleAndShareSlot,
	} );
} )( window.wp );
