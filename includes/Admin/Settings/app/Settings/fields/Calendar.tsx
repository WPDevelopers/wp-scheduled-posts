import React, { useEffect, useRef, useState } from "react";
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction"; // needed for dayClick
import Sidebar from "./Calendar/Sidebar";
import renderEventContent from "./Calendar/EventRender";
import { useBuilderContext } from "quickbuilder";
// const events = [{ title: "Meeting", start: new Date() }];


export default function Calendar(props) {
  // @ts-ignore
  const [events, setEvents] = useState([]);
  const restRoute = props.rest_route;
  const calendar = useRef<FullCalendar>();
  const builderContext = useBuilderContext();


  const getUrl = () => {
    const date  = calendar.current?.getApi().view.currentStart;
    const month = date.getMonth() + 1;
    const year  = date.getFullYear();
    console.warn(date, month, year);

    const queryParams = {
      post_type: 'post',
      month: month,
      year: year,
    }
    return addQueryArgs( restRoute, queryParams );
  }

  useEffect(() => {
    calendar.current?.doResize();
    calendar.current?.render();

    //
    apiFetch({
      path: getUrl()
    }).then((data: []) => {
      setEvents(data);
    });
  }, []);

  useEffect(() => {
    console.log(builderContext.config.active);
    if('layout_calendar' === builderContext.config.active) {
      calendar.current?.getApi().updateSize();
    }
  }, [builderContext.config.active]);

  // @ts-ignore
  window.calendar = calendar;
  console.log(props);


  return (
    <>
      <div className="sidebar">
        <Sidebar />
      </div>
      <div className="main-content">
        <div className="toolbar">
          <div className="left">
            <select name="type" id="type" className="select-type">
              <option>Type</option>
              <option>Type</option>
              <option>Type</option>
            </select>
            <select name="category" id="category" className="select-category">
              <option>category</option>
              <option>category</option>
              <option>category</option>
            </select>
          </div>
          <div className="middle">
            {/* calendar dropdown */}
            {calendar.current && calendar.current.getApi().view.title}
            <input type="month" id="start" name="start"
            min="2018-03" value="2018-05"></input>
          </div>
          <div className="right">
            <button>Today</button>
            <i className="wpsp-icon wpsp-auto-sc" />
          </div>
        </div>
        <FullCalendar
          ref={calendar}
          plugins={[dayGridPlugin, interactionPlugin]}
          initialView="dayGridMonth"
          // weekends={true}
          events={events}
          // firstDay={props.firstDay}
          eventContent={renderEventContent}
          // dateClick={handleDateClick}
          // Enable droppable option
          editable={true}
          droppable={true}
          // headerToolbar={false}
          // Provide a drop callback function
          // drop={handleDrop}
          eventClick={function(info) {
            console.log('Event: ', info.event.extendedProps);
            console.log('info: ', info);
            console.log(calendar.current?.getApi().view);

            // change the border color just for fun
            info.el.style.border = '1px solid red';
          }}
        />
      </div>
    </>
  );
}
