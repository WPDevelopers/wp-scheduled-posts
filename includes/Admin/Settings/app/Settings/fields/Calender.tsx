import React, { useEffect, useRef, useState } from "react";
import apiFetch from '@wordpress/api-fetch';
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction"; // needed for dayClick
import Sidebar from "./Calender/Sidebar";
import renderEventContent from "./Calender/EventRender";

// const events = [{ title: "Meeting", start: new Date() }];


export default function Calender() {
  // @ts-ignore
  const url = wpscp_calendar_ajax_object.calendar_rest_route;
  const [events, setEvents] = useState([]);
  const calender = useRef<FullCalendar>();

  useEffect(() => {
    //
    apiFetch({
      path: url
    }).then((data: []) => {
      setEvents(data);
    });


  }, []);

  console.log(calender);


  return (
    <>
      <div className="sidebar" style={{width: "500px"}}>
        <Sidebar />
      </div>
      <div>
        <div className="toolbar">
          <div className="left"></div>
          <div className="middle">
            {/* calender dropdown */}
            {calender.current && calender.current.getApi().view.title}
          </div>
          <div className="right"></div>
        </div>
        <FullCalendar
          ref={calender}
          plugins={[dayGridPlugin, interactionPlugin]}
          initialView="dayGridMonth"
          weekends={false}
          events={events}
          eventContent={renderEventContent}
          // dateClick={handleDateClick}
          eventClick={function(info) {
            console.log('Event: ', info.event.extendedProps);
            console.log('info: ', info);

            // change the border color just for fun
            info.el.style.borderColor = 'red';
          }}
          // Enable droppable option
          editable={true}
          droppable={true}
          headerToolbar={false}
          // Provide a drop callback function
          // drop={handleDrop}
        />
      </div>
    </>
  );
}
