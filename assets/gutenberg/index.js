/**
 * WordPress dependencies
 */
const { compose, ifCondition, withInstanceId } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { PluginDocumentSettingPanel,PluginSidebar } = wp.editPost;
const { Component, createElement, useState, Fragment } = wp.element;
const { CheckboxControl } = wp.components;
const {
  publishImmediately,
  publishFutureDate,
  currentTime,
  publish_button_off,
  allowedPostTypes,
} = WPSchedulePostsFree;
import CustomSocialTemplate from "./utils/CustomSocialTemplate";
import PublishButton from "./publish-button";
import PublishFutureButton from "./publish-future-button";
import SocialShare from "./social-share";
import DummyProFeatures from "./utils/DummyProFeatures";
import WpspProSlot from "./wpsp-pro-slot";
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
  componentDidMount() {
    jQuery(document).ready(function() {
      // Click event on wpsp_options
      jQuery(document).on('click','.edit-post-header__settings button[aria-label="wpsp_options"]', function(event) {
        event.stopPropagation();
        jQuery('.edit-post-header__settings button:not(".is-pressed")[aria-label="Settings"]').trigger('click');
        setTimeout(() => {
          jQuery('.schedulepress-options:not(".is-opened") .components-button.components-panel__body-toggle').trigger('click');
          setTimeout( () => {
            const targetElement = document.querySelector('.interface-navigable-region.interface-interface-skeleton__sidebar');
            const status_position = jQuery('.interface-complementary-area.edit-post-sidebar .edit-post-post-status');
            const wpsp_position = jQuery('.interface-complementary-area.edit-post-sidebar .schedulepress-options');
            targetElement.scrollTo({
              top: Math.abs( status_position.offset().top - wpsp_position.offset().top ),
              behavior: 'smooth',
            });
          }, 0)
        }, 0);
      });
    });
  }

  render() {
    return (
     <>
      <PluginSidebar title="wpsp_options" icon= {
        <svg width="25" height="25" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 18C4.04128 18 0 13.9587 0 9C0 4.04128 4.04128 0 9 0C10.5695 0 12.1011 0.401853 13.4431 1.18281C13.8602 1.42544 14.0042 1.94861 13.7616 2.35046C13.519 2.76748 12.9958 2.91154 12.5939 2.66891C11.5097 2.04718 10.2586 1.71356 9 1.71356C4.99663 1.72115 1.72873 4.98147 1.72873 8.98484C1.72873 12.9882 4.98905 16.2561 9 16.2561C11.0396 16.2561 12.92 15.4372 14.3075 13.9587C14.6335 13.6099 15.1794 13.5948 15.5206 13.9132C15.8694 14.2393 15.8846 14.7852 15.5661 15.1264C13.8677 16.9537 11.4794 17.9924 8.99242 17.9924L9 18Z" fill="url(#paint0_radial_1898_55)"/>
          <path d="M7.89382 7.24825L6.06653 5.58776C5.71775 5.26931 5.17184 5.29206 4.85339 5.64084C4.53494 5.98962 4.55768 6.53553 4.90646 6.85398L6.8854 8.65095C7.03704 8.0747 7.40099 7.5667 7.89382 7.24825Z" fill="#24E2AC"/>
          <path d="M18.8641 1.89593C18.5684 1.5244 18.0225 1.45616 17.651 1.75187L10.5996 7.32474C11.0697 7.67352 11.4109 8.1891 11.517 8.78809L18.7201 3.0939C19.1068 2.7982 19.1598 2.25987 18.8641 1.88834V1.89593Z" fill="#24E2AC"/>
          <path d="M9.93336 7.86338C9.71348 7.74207 9.46327 7.67383 9.19789 7.67383C8.95526 7.67383 8.73538 7.7269 8.53825 7.81789C7.99992 8.0681 7.64355 8.60643 7.64355 9.22817V9.32674C7.69663 10.138 8.36386 10.7825 9.19789 10.7825C10.0319 10.7825 10.6537 10.1835 10.7446 9.40256C10.7598 9.34948 10.7598 9.28124 10.7598 9.213C10.7598 8.63676 10.4338 8.12117 9.94094 7.8558L9.93336 7.86338Z" fill="#3DEAB5"/>
          <path d="M20.9114 7.00586H18.7429C18.5989 7.00586 18.4775 7.12717 18.4775 7.27123V9.1971C18.4775 9.34116 18.5989 9.46247 18.7429 9.46247H20.9114C21.0555 9.46247 21.1768 9.34116 21.1768 9.1971V7.27123C21.1768 7.12717 21.0555 7.00586 20.9114 7.00586Z" fill="#6C62FF"/>
          <path d="M17.3709 7.00586H15.2024C15.0583 7.00586 14.937 7.12717 14.937 7.27123V9.1971C14.937 9.34116 15.0583 9.46247 15.2024 9.46247H17.3709C17.5149 9.46247 17.6363 9.34116 17.6363 9.1971V7.27123C17.6438 7.12717 17.5301 7.00586 17.3709 7.00586Z" fill="#CCCCFF"/>
          <path d="M17.3709 10.1826H15.2024C15.0583 10.1826 14.937 10.3039 14.937 10.448V12.3739C14.937 12.5179 15.0583 12.6392 15.2024 12.6392H17.3709C17.5149 12.6392 17.6363 12.5179 17.6363 12.3739V10.448C17.6438 10.2963 17.5301 10.1826 17.3709 10.1826Z" fill="#CCCCFF"/>
          <path d="M20.9114 10.1826H18.7429C18.5989 10.1826 18.4775 10.3039 18.4775 10.448V12.3739C18.4775 12.5179 18.5989 12.6392 18.7429 12.6392H20.9114C21.0555 12.6392 21.1768 12.5179 21.1768 12.3739V10.448C21.1768 10.2963 21.0555 10.1826 20.9114 10.1826Z" fill="#CCCCFF"/>
          <defs>
          <radialGradient id="paint0_radial_1898_55" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.9301 15.6268) scale(7.50632)">
          <stop stopColor="#F3F3FF"/>
          <stop offset="0.09" stopColor="#E3E2FF"/>
          <stop offset="0.4" stopColor="#B0ACFF"/>
          <stop offset="0.66" stopColor="#8B84FF"/>
          <stop offset="0.87" stopColor="#746CFF"/>
          <stop offset="1" stopColor="#6C63FF"/>
          </radialGradient>
          </defs>
        </svg>
      }>

      </PluginSidebar>
      <PluginDocumentSettingPanel name="schedulepress-options" title="SchedulePress" className="schedulepress-options" icon= {
        <svg width="25" height="25" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 18C4.04128 18 0 13.9587 0 9C0 4.04128 4.04128 0 9 0C10.5695 0 12.1011 0.401853 13.4431 1.18281C13.8602 1.42544 14.0042 1.94861 13.7616 2.35046C13.519 2.76748 12.9958 2.91154 12.5939 2.66891C11.5097 2.04718 10.2586 1.71356 9 1.71356C4.99663 1.72115 1.72873 4.98147 1.72873 8.98484C1.72873 12.9882 4.98905 16.2561 9 16.2561C11.0396 16.2561 12.92 15.4372 14.3075 13.9587C14.6335 13.6099 15.1794 13.5948 15.5206 13.9132C15.8694 14.2393 15.8846 14.7852 15.5661 15.1264C13.8677 16.9537 11.4794 17.9924 8.99242 17.9924L9 18Z" fill="url(#paint0_radial_1898_55)"/>
          <path d="M7.89382 7.24825L6.06653 5.58776C5.71775 5.26931 5.17184 5.29206 4.85339 5.64084C4.53494 5.98962 4.55768 6.53553 4.90646 6.85398L6.8854 8.65095C7.03704 8.0747 7.40099 7.5667 7.89382 7.24825Z" fill="#24E2AC"/>
          <path d="M18.8641 1.89593C18.5684 1.5244 18.0225 1.45616 17.651 1.75187L10.5996 7.32474C11.0697 7.67352 11.4109 8.1891 11.517 8.78809L18.7201 3.0939C19.1068 2.7982 19.1598 2.25987 18.8641 1.88834V1.89593Z" fill="#24E2AC"/>
          <path d="M9.93336 7.86338C9.71348 7.74207 9.46327 7.67383 9.19789 7.67383C8.95526 7.67383 8.73538 7.7269 8.53825 7.81789C7.99992 8.0681 7.64355 8.60643 7.64355 9.22817V9.32674C7.69663 10.138 8.36386 10.7825 9.19789 10.7825C10.0319 10.7825 10.6537 10.1835 10.7446 9.40256C10.7598 9.34948 10.7598 9.28124 10.7598 9.213C10.7598 8.63676 10.4338 8.12117 9.94094 7.8558L9.93336 7.86338Z" fill="#3DEAB5"/>
          <path d="M20.9114 7.00586H18.7429C18.5989 7.00586 18.4775 7.12717 18.4775 7.27123V9.1971C18.4775 9.34116 18.5989 9.46247 18.7429 9.46247H20.9114C21.0555 9.46247 21.1768 9.34116 21.1768 9.1971V7.27123C21.1768 7.12717 21.0555 7.00586 20.9114 7.00586Z" fill="#6C62FF"/>
          <path d="M17.3709 7.00586H15.2024C15.0583 7.00586 14.937 7.12717 14.937 7.27123V9.1971C14.937 9.34116 15.0583 9.46247 15.2024 9.46247H17.3709C17.5149 9.46247 17.6363 9.34116 17.6363 9.1971V7.27123C17.6438 7.12717 17.5301 7.00586 17.3709 7.00586Z" fill="#CCCCFF"/>
          <path d="M17.3709 10.1826H15.2024C15.0583 10.1826 14.937 10.3039 14.937 10.448V12.3739C14.937 12.5179 15.0583 12.6392 15.2024 12.6392H17.3709C17.5149 12.6392 17.6363 12.5179 17.6363 12.3739V10.448C17.6438 10.2963 17.5301 10.1826 17.3709 10.1826Z" fill="#CCCCFF"/>
          <path d="M20.9114 10.1826H18.7429C18.5989 10.1826 18.4775 10.3039 18.4775 10.448V12.3739C18.4775 12.5179 18.5989 12.6392 18.7429 12.6392H20.9114C21.0555 12.6392 21.1768 12.5179 21.1768 12.3739V10.448C21.1768 10.2963 21.0555 10.1826 20.9114 10.1826Z" fill="#CCCCFF"/>
          <defs>
          <radialGradient id="paint0_radial_1898_55" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.9301 15.6268) scale(7.50632)">
          <stop stopColor="#F3F3FF"/>
          <stop offset="0.09" stopColor="#E3E2FF"/>
          <stop offset="0.4" stopColor="#B0ACFF"/>
          <stop offset="0.66" stopColor="#8B84FF"/>
          <stop offset="0.87" stopColor="#746CFF"/>
          <stop offset="1" stopColor="#6C63FF"/>
          </radialGradient>
          </defs>
        </svg>
      }
      >
        {(publish_button_off === "" || !(this.props.isScheduled && !this.props.isPublished)) ? (
          ""
        ) : (
          <div className="sc-publish-future">
            <div>
              <CheckboxControl
                label="Publish future post immediately"
                checked={this.state.publishImmediately}
                onChange={(checked) => {
                  this.setState({ publishImmediately: checked });
                  if (checked === false) {
                    this.setState({ showHelp: false });
                  }
                }}
              />
              <a
                id="wpscp-future-post-help-handler"
                className="dashicons dashicons-info"
                href="#"
                title="Show/Hide Help"
                onClick={(event) => {
                  event.preventDefault();
                  this.setState({ showHelp: !this.state.showHelp });
                }}
              ></a>
            </div>
            {this.state.publishImmediately && (
              <div className="sc-publish-future-buttons">
                <PublishButton
                  {...this.props}
                  currentTime={currentTime}
                  publish={publishImmediately}
                />
                <PublishFutureButton
                  {...this.props}
                  currentTime={currentTime}
                  publish={publishFutureDate}
                />
              </div>
            )}
            {this.state.showHelp && (
              <div style={{ marginTop: 5, color: "#757575" }}>
                If you choose to publish this future post with the Future Date, it will be published immediately but the post’s date time will not set the current date rather it will be your scheduled future date time.
              </div>
            )}
          </div>
        )}
        <CustomSocialTemplate/>
        <WpspProSlot.Slot/>
        { !WPSchedulePostsFree?.is_pro && <DummyProFeatures/> }
        <SocialShare is_pro_active={ WPSchedulePostsFree?.is_pro ? true : false  } />
      </PluginDocumentSettingPanel>
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
  ifCondition(({ postType }) => {
    if (allowedPostTypes.includes(postType) !== false) {
      return true;
    } else {
      return true;
    }
  }),
])(AdminPublishButton);