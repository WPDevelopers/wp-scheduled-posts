import React, { useEffect, useRef, useState } from "react";
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction"; // needed for dayClick
import Sidebar from "./Calendar/Sidebar";
import renderEventContent from "./Calendar/EventRender";
import { useBuilderContext } from "quickbuilder";
import EditPost from "./Calendar/Edit";
import { s } from "@fullcalendar/core/internal-common";
// const events = [{ title: "Meeting", start: new Date() }];
import { Button } from "@wordpress/components";
import { default as ReactSelect } from "react-select";
import { selectStyles } from "../helper/styles";
import { components } from "react-select";
import Monthpicker from '@compeon-os/monthpicker'
import classNames from "classnames";
import { __ } from "@wordpress/i18n";

export default function Calendar(props) {
  // @ts-ignore
  const [events, setEvents] = useState([]);
  const restRoute = props.rest_route;
  const calendar = useRef<FullCalendar>();
  const builderContext = useBuilderContext();
  const [yearMonth, setYearMonth] = useState("11.2022") // @todo
  const [editAreaToggle,setEditAreaToggle] = useState([]);

  // EditPost state
  const [postData, setPostData] = useState({});
  const [isModalOpen, setIsModalOpen] = useState(false);

  const handleOpenModal = (post?) => {
    setPostData(post || {});
    setIsModalOpen(true);
  };
  const handleCloseModal = () => {
    setPostData({});
    setIsModalOpen(false);
  };

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
  // window.calendar = calendar;
  // console.log(props);
  // Prepare options with checkbox
  const Option = (props) => {
    return (
      <div>
        <components.Option {...props}>
          <input
            type="checkbox"
            checked={props.isSelected}
            onChange={() => null}
          />{" "}
          <label>{props.label}</label>
        </components.Option>
      </div>
    );
  };


  const options = [
    {label : "Option 1",value : "options-1"},
    {label : "Option 2",value : "options-2"}
  ]
  const [month, year] = yearMonth.split('.');

  const [sidebarToogle,setSidebarToggle] = useState(true);

  const handleSlidebarToggle = () => {
    setSidebarToggle( sidebarToogle ? false : true );
  }
  const [optionSelected, setOptionSelected] = useState([]);

  // Add and remove
  const handleChange = (selected) => {
    setOptionSelected(selected);
  };
  const removeItem = (item) => {
    const updatedItems = optionSelected.filter((i) => i !== item);
    setOptionSelected(updatedItems);
  };

  return (
    <div className={classNames('wprf-control', 'wprf-calender', `wprf-${props.name}-calender`, props?.classes)}>
      <div className="wpsp-calender-header">
          <div className="wpsp-post-select">
            <ReactSelect
              options={options}
              styles={selectStyles}
              closeMenuOnSelect={false}
              hideSelectedOptions={false}
              placeholder={__("Select Post Type",'wp-scheduled-posts')}
              autoFocus={false}
              isMulti
              components={{
                Option
              }}
              value={optionSelected}
              onChange={handleChange}
              controlShouldRenderValue={false}
              className="main-select"
            />
            <div className="selected-options">
                <ul>
                  { optionSelected?.map( (item, index) => (
                    <li key={index}> { item?.label } <button onClick={() => removeItem(item)}> <i className='wpsp-icon wpsp-close'></i> </button> </li>
                  ))}
                </ul>
            </div>
          </div>
          <div className="wpsp-post-search">
              <input type="text" placeholder="Search" />
          </div>
      </div>
      <div className="wpsp-calender-content main-content-wrapper">
        <div className={`main-content ${!sidebarToogle ? 'basis-100' : ''}`}>
          <div className="toolbar">
            <div className="left">
              <ReactSelect
                placeholder={ __("Select Category","wp-scheduled-posts") }
                options={options}
                styles={selectStyles}
                closeMenuOnSelect={false}
                hideSelectedOptions={false}
                autoFocus={false}
                isMulti
                components={{
                  Option
                }}
                controlShouldRenderValue={false}
                className="main-select"
              />
            </div>
            <div className="middle">
              {/* calendar dropdown */}
              {/* <input type="month" id="start" name="start"
              min="2018-03" value="2018-05"></input> */}
              <Monthpicker
                locale="en"
                format='MM.yyyy'
                month={parseInt(month)}
                year={parseInt(year)}
                onChange={(event) => {
                setYearMonth(event)
              }}>
                <div className="calender-selected-month">
                  { calendar.current && calendar.current.getApi().view.title }
                  <span className="dashicons dashicons-arrow-down-alt2"></span>
                </div>
              </Monthpicker>
            </div>
            <div className="right">
              <button>Today</button>
              <i onClick={handleSlidebarToggle} className={`wpsp-icon wpsp-manual-sc ${ !sidebarToogle ? 'inactive' : '' }`} />
            </div>
          </div>
          <div className="wprf-calendar-wrapper">
            <div className="button-control-month">
              <button type="button" className="wpsp-prev-button wpsp-button-primary">
                <i className="wpsp-icon wpsp-prev"></i>
              </button>
              <button type="button" className="wpsp-next-button wpsp-button-primary">
                <i className="wpsp-icon wpsp-next"></i>
              </button>
            </div>
            <FullCalendar
              ref={calendar}
              plugins={[dayGridPlugin, interactionPlugin]}
              initialView="dayGridMonth"
              dayMaxEvents={1}
              dayPopoverFormat={{ day: 'numeric' }}
              moreLinkContent={(arg) => {
                return (
                  <>
                    View {arg.num} More
                  </>
                )
              }}
              // weekends={true}
              events={events}
              // firstDay={props.firstDay}
              eventContent={renderEventContent(editAreaToggle,setEditAreaToggle,handleOpenModal)}
              dayCellDidMount={(args) => {
                console.log('dayCellDidMount', args);
                const dayTop = args.el.getElementsByClassName('fc-daygrid-day-top');
                // add a button on dayTop element as child
                const button = document.createElement('button');
                button.innerHTML = 'Add New';
                if(args.isOther) {
                  button.disabled = true;
                }
                button.addEventListener('click', (event) => {
                  console.log('click', event, args);
                  handleOpenModal();
                });
                dayTop[0].appendChild(button);
              }}
              // dateClick={handleDateClick}
              // Enable droppable option
              editable={true}
              droppable={true}
              // headerToolbar={false}
              // Provide a drop callback function
              eventReceive={info => {
                const props = info.event.extendedProps;
                props.setPosts(posts => posts.filter((p) => p.postId !== props.postId));
                console.log('drop', info, props);
              }}
              eventClick={function(info) {
                console.log('Event: ', info.event.extendedProps);
                console.log('info: ', info);
                console.log(calendar.current?.getApi().view);

                // change the border color just for fun
                info.el.style.border = '1px solid red';
              }}
              datesSet={(dateInfo) => {

              }}
            />
          </div>
        </div>
        { sidebarToogle && (
            <div className="sidebar">
                <Sidebar props={props} handleOpenModal={handleOpenModal} />
            </div>
          ) }
        <EditPost post={postData} isOpen={isModalOpen} closeModal={handleCloseModal} />
      </div>

    </div>
  );
}
