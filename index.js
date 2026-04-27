( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const editor = wp.editor || wp.editPost;
	const { PluginDocumentSettingPanel } = editor;
	const { __ } = wp.i18n;
	const { createElement: el, useEffect } = wp.element;

	const PANEL_NAME = 'schedulepress-panel';
	const PANEL_LABEL = __( 'SchedulePress', 'wp-scheduled-posts' );

	const assetsURI =
		( window.WPSPSidebar && window.WPSPSidebar.assetsURI ) ||
		( window.WPSchedulePostsFree && window.WPSchedulePostsFree.assetsURI ) ||
		'';

	const scheduleIcon = el( 'img', {
		src: assetsURI + 'images/wpsp-logo.png',
		alt: '',
		width: 18,
		height: 18,
		style: { marginRight: '6px', verticalAlign: 'middle', flexShrink: 0 },
	} );

	const PANEL_TITLE = el(
		'span',
		{ style: { display: 'inline-flex', alignItems: 'center' } },
		scheduleIcon,
		PANEL_LABEL
	);

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

	// Move our panel to the top of the document sidebar.
	const moveToTop = () => {
		const panel = document.querySelector(
			'.edit-post-sidebar .components-panel__body.' + PANEL_NAME +
			', .editor-sidebar .components-panel__body.' + PANEL_NAME
		);
		if ( ! panel ) return;
		const container = panel.parentElement;
		if ( container && container.firstElementChild !== panel ) {
			container.insertBefore( panel, container.firstElementChild );
		}
	};

	const SchedulePressPanel = () => {
		useEffect( () => {
			moveToTop();
			const observer = new MutationObserver( moveToTop );
			const target =
				document.querySelector( '.edit-post-sidebar' ) ||
				document.querySelector( '.editor-sidebar' );
			if ( target ) {
				observer.observe( target, { childList: true, subtree: true } );
			}
			return () => observer.disconnect();
		}, [] );

		return el(
			PluginDocumentSettingPanel,
			{
				name: PANEL_NAME,
				title: PANEL_TITLE,
				className: PANEL_NAME,
			},
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
	};

	registerPlugin( 'schedulepress-sidebar', {
		render: SchedulePressPanel,
	} );
} )( window.wp );
