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
    if (
      publish_button_off == "" ||
      !(this.props.isScheduled && !this.props.isPublished)
    ) {
      return "";
    }

    return (
      <PluginPostStatusInfo>
        {/* style={{ display: "flex", flexWrap: "wrap", gap: 5 }} */}
        <div className="sc-publish-future">
          {/*  style={{display: 'flex', alignItems: 'center', gap: 5}} */}
          <div>
            <CheckboxControl
              label="Publish future post immediately"
              checked={this.state.publishImmediately}
              onChange={(checked) => {
                this.setState({ publishImmediately: checked });
                if (checked == false) {
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
            >

            </a>
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
      </PluginPostStatusInfo>
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
