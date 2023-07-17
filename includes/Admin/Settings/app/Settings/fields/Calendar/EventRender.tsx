import React from 'react';
import { EventContentArg, sliceEvents, createPlugin } from "@fullcalendar/core";
import { Button } from "@wordpress/components";

function customView(props) {
  const segs = sliceEvents(props, true); // allDay=true
  console.log('segs', segs);

  return (
    <>
      <div className='view-title'>
        {props.dateProfile.currentRange.start.toUTCString()}
      </div>
      <div className='view-events'>
        {segs.length} events
      </div>
    </>
  )
}
export const customViewPlugin = createPlugin({
  name: 'custom',
  views: {
    custom: customView
  }
});

// a custom render function
const renderEventContent = (eventInfo: EventContentArg) => {
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
              <i className="dashicons dashicons-welcome-write-blog"></i>Quick Edit
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
