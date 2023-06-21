import React, { useEffect, useState } from "react";
import apiFetch from '@wordpress/api-fetch';
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction"; // needed for dayClick

// const events = [{ title: "Meeting", start: new Date() }];


export default function Calender() {
  // @ts-ignore
  const url = wpscp_calendar_ajax_object.calendar_rest_route;
  const [events, setEvents] = useState([]);

  useEffect(() => {
    //
    apiFetch({
      path: url
    }).then((data: []) => {
      setEvents(data);
      console.log(data);
    });

  }, []);

  // a custom render function
  function renderEventContent(eventInfo) {
    console.log(eventInfo.event);


    const { title, start, end, allDay } = eventInfo.event;
    const { postId, href, edit, status, postType, postTime } = eventInfo.event.extendedProps;
    console.log({ title, start, end, allDay, postId, href, edit, status, postType, postTime });


    return (
      <div className="wpscp-event-post">
        <div className="postlink">
          <span>
            <span className="posttime">[{postTime}]</span> {title} [{status}]
          </span>
        </div>
        <div className="postactions">
          <div>
            <div className="edit">
              <a href={edit}>
                <i className="dashicons dashicons-edit"></i>Edit
              </a>
              <a className="wpscpquickedit" href="#" data-type="quickedit">
                <i className="dashicons dashicons-welcome-write-blog"></i>Quick Edit
              </a>
            </div>
            <div className="deleteview">
              <a className="wpscpEventDelete" href="#">
                <i className="dashicons dashicons-trash"></i> Delete
              </a>
              <a href={href}>
                <i className="dashicons dashicons-admin-links"></i> View
              </a>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const handleDateClick = (arg) => {
    // bind with an arrow function
    alert(arg.dateStr);
  };

  return (
    <div>
      <h1>Demo App</h1>
      <FullCalendar
        plugins={[dayGridPlugin]}
        initialView="dayGridMonth"
        weekends={false}
        events={events}
        eventContent={renderEventContent}
        dateClick={handleDateClick}
      />
    </div>
  );
}
