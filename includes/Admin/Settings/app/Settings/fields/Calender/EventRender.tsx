import React from "react";
// a custom render function
const renderEventContent = (eventInfo) => {
  const { title, start, end, allDay } = eventInfo.event;
  const { postId, href, edit, status, postType, postTime } =
    eventInfo.event.extendedProps;


  return (
    <div className="wpscp-event-post">
      <div className="postlink">
        <span>
          <span className="posttime">[{postTime}]</span> {title} [{status}]
        </span>
      </div>
      <div className="postactions" style={{ display: "none" }}>
        <div>
          <div className="edit">
            <button data-href={edit}>
              <i className="dashicons dashicons-edit"></i>Edit
            </button>
            <button className="wpscpquickedit" data-type="quickedit">
              <i className="dashicons dashicons-welcome-write-blog"></i>Quick
              Edit
            </button>
          </div>
          <div className="deleteview">
            <button className="wpscpEventDelete">
              <i className="dashicons dashicons-trash"></i> Delete
            </button>
            <button data-href={href}>
              <i className="dashicons dashicons-admin-links"></i> View
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default renderEventContent;
