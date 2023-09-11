/**
 * WordPress dependencies
 */
const { compose, ifCondition, withInstanceId } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { PluginPostStatusInfo } = wp.editPost;
const { Component, createElement, useState } = wp.element;
const { CheckboxControl } = wp.components;
const {
  publishImmediately,
  publishFutureDate,
  currentTime,
  publish_button_off,
  allowedPostTypes,
} = WPSchedulePostsFree;
import { IconButton } from "@wordpress/components";
import PublishButton from "./publish-button";
import PublishFutureButton from "./publish-future-button";
import { ComplementaryArea } from '@wordpress/interface';
import {SettingsHeader} from '@wordpress/edit-post';
import { cog } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Animate, Button, Panel, Slot, Fill } from '@wordpress/components';
import classnames from 'classnames';
import { useDispatch, useSelect, select } from '@wordpress/data';
import { closeSmall } from '@wordpress/icons';
console.log(ComplementaryArea);

function ComplementaryAreaFill( { scope, children, className } ) {
	return (
		<Fill name={ `ComplementaryArea/${ scope }` }>
			<Animate type="slide-in" options={ { origin: 'left' } }>
				{ () => <div className={ className }>{ children }</div> }
			</Animate>
		</Fill>
	);
}

class AdminPublishButton extends Component {
  constructor(props) {
    super(props);
    this.handleChange = this.handleChange.bind(this);
    this.state = {
      showHelp: false,
      publishImmediately: false,
    };
  }

  componentWillReceiveProps = (nextProps) => {
    if(this.props.post.status == 'publish' && nextProps.post.status == 'future'){
      this.setState({ publishImmediately: false });
      console.log(this.props.post.status, nextProps.post.status);
    }
  }

  handleChange(checked) {}
  render() {
    const scope = "core/edit-post";
    let sidebarName = select( 'core/interface' ).getActiveComplementaryArea(
      'core/edit-post'
    );
    const toggleShortcut = select(
      'core/keyboard-shortcuts'
    ).getShortcutRepresentation( 'core/edit-post/toggle-sidebar' );

    const openGeneralSidebar = (sidebarName) => {
      wp.data.dispatch( 'core/interface' ).enableComplementaryArea( 'core/edit-post', sidebarName );
      this.setState({ sidebarName: sidebarName });
    }

    const openDocumentSettings = () => openGeneralSidebar( 'edit-post/document' );
    const openBlockSettings    = () => openGeneralSidebar( 'edit-post/block' );
    const openWPSPSettings     = () => openGeneralSidebar( 'edit-post/wpsp' );

    const [ documentAriaLabel, documentActiveClass ] =
      sidebarName === 'edit-post/document'
        ? // translators: ARIA label for the Document sidebar tab, selected. %s: Document label.
          [ sprintf( __( '%s (selected)' ), "Post" ), 'is-active' ]
        : [ "Post", '' ];

    const [ blockAriaLabel, blockActiveClass ] =
      sidebarName === 'edit-post/block'
        ? // translators: ARIA label for the Block Settings Sidebar tab, selected.
          [ __( 'Block (selected)' ), 'is-active' ]
        : // translators: ARIA label for the Block Settings Sidebar tab, not selected.
          [ __( 'Block' ), '' ];
    const [ wpspAriaLabel, wpspActiveClass ] =
      sidebarName === 'edit-post/wpsp'
        ? // translators: ARIA label for the Block Settings Sidebar tab, selected.
          [ __( 'WPSP (selected)' ), 'is-active' ]
        : // translators: ARIA label for the Block Settings Sidebar tab, not selected.
          [ __( 'Block' ), '' ];


    return (
      <>

				<ComplementaryAreaFill
					className={ classnames(
						'interface-complementary-area',
            'edit-post-sidebar',
					) }
					scope={ scope }
				>
          <div
            className={ classnames(
              'components-panel__header',
              'interface-complementary-area-header',
              'edit-post-sidebar__panel-tabs',
            ) }
            tabIndex={ -1 }
          >
              <ul>
                <li>
                  <Button
                    onClick={ openDocumentSettings }
                    className={ `edit-post-sidebar__panel-tab ${documentActiveClass}` }
                  >
                    Post
                  </Button>
                </li>
                <li>
                  <Button
                    onClick={ openBlockSettings }
                    className={ `edit-post-sidebar__panel-tab ${blockActiveClass}` }
                    // translators: Data label for the Block Settings Sidebar tab.
                    data-label={ __( 'Block' ) }
                  >
                    {
                      // translators: Text label for the Block Settings Sidebar tab.
                      __( 'Block' )
                    }
                  </Button>
                </li>
                <li>
                  <Button
                    onClick={ openWPSPSettings }
                    className={ `edit-post-sidebar__panel-tab ${wpspActiveClass}` }
                    aria-label={ "documentAriaLabel" }
                    data-label={ "documentLabel" }
                  >
                    Button
                  </Button>
                </li>
              </ul>

              <Button
                icon={ closeSmall }
                onClick={ () => {
                  openGeneralSidebar('')
                } }
                label="Close"
              />
            </div>
					<Panel className={ 'panelClassName' }>
            {sidebarName === 'edit-post/wpsp' && (

              "Hello World"

            )}
          </Panel>
				</ComplementaryAreaFill>
      </>
    );
  }
}

export default compose([
  withSelect((select) => {
    const {
      getCurrentPostType,
      getEditedPostAttribute,
      isCurrentPostScheduled,
      isCurrentPostPublished,
      getCurrentPost,
    } = select("core/editor");

    return {
      postType: getCurrentPostType(),
      meta: getEditedPostAttribute("meta"),
      isScheduled: isCurrentPostScheduled(),
      isPublished: isCurrentPostPublished(),
      post: getCurrentPost(),
    };
  }),
  withDispatch((dispatch, { meta }) => {
    const { editPost, savePost } = dispatch("core/editor");
    return {
      editPost(newMeta) {
        var new_date = JSON.parse(newMeta);
        if (typeof new_date === "string") {
          new_date = JSON.parse(new_date);
        }
        editPost({
          date: new_date.date,
          date_gmt: new_date.date_gmt,
          status: new_date.status,
        });
      },
      setMetaValue: function (metaValue) {
        editPost({ meta: metaValue });
      },
      savePost,
    };
  }),
])(AdminPublishButton);
