import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin, { EventDragStopArg } from "@fullcalendar/interaction"; // needed for dayClick
import momentTimezonePlugin from '@fullcalendar/moment-timezone';
import FullCalendar from "@fullcalendar/react";
import apiFetch from "@wordpress/api-fetch";
import React, { useEffect, useRef, useState } from "react";
import { eventDrop, getPostFromEvent } from "./Calendar/EventRender";
import Sidebar from "./Calendar/Sidebar";
// const events = [{ title: "Meeting", start: new Date() }];
import MonthPicker from "@compeon-os/monthpicker";
import { DayCellMountArg, EventContentArg, EventDropArg } from "@fullcalendar/core";
import { format, getSettings, __experimentalGetSettings } from "@wordpress/date";
import moment from 'moment';
import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import { useBuilderContext } from "quickbuilder";
import "../../assets/sass/utils/_calendar.scss";
import CategorySelect from "./Calendar/Category";
import { ModalContent } from "./Calendar/EditPost";
import PostCard from "./Calendar/EventRender";
import { getEndDate, getTimeZone, getValues } from "./Calendar/Helpers";
import ReactSelectWrapper, { addAllOption } from "./Calendar/ReactSelectWrapper";
import { ModalProps, Option, PostType } from "./Calendar/types";

export default function Calendar(props) {
  // @ts-ignore
  const timeZone   = getTimeZone();
  const restRoute  = props.rest_route;
  const calendar   = useRef<FullCalendar>();
  const RefSidebar = useRef<HTMLDivElement>();
  // monthPicker
  const monthPicker = useRef<MonthPicker>();
  const builderContext = useBuilderContext();
  const [events, setEvents] = useState([]);
  const [draftEvents, setDraftEvents] = useState([]);
  //
  const currentDate = new Date();
  const [yearMonth, setYearMonth] = useState({
    month: currentDate.getMonth() + 1,
    year: currentDate.getFullYear(),
  });

  const [modalData, openModal] = useState<ModalProps>({ post: null, eventType: null });
  const onSubmit = (data: any, oldData) => {
    const newEvents = events.filter((event) => event.postId !== oldData?.postId);
    setEvents([...newEvents, data]);
  };

  const [sidebarToggle, setSidebarToggle] = useState(true);
  const [editAreaToggle, setEditAreaToggle] = useState({});
  const [status, setStatus] = useState( null );

  const [selectedPostType, setSelectedPostType] = useState<Option[]>(
    addAllOption(props.post_types)
  );
  const [selectedCategories, setSelectedCategories] = useState<Option[]>([]);

  const MyWrapperComponent = ({ children, ...rest }) => {
    return React.cloneElement(children, { ...rest });
  };

  const updateEvents = (post) => {
    getEvents();
    setTimeout(() => {
      setEvents((events) => {
        const index = events.findIndex((event) => event.postId === post.postId);
        if (index === -1) {
          return [...events, post];
        }
        const updatedEvents = [...events];
        updatedEvents[index] = post;
        return updatedEvents;
      });
    }, 800);
    
  };

  const getEvents = async () => {
    if(!calendar.current) return;
    const activeStart = calendar.current.getApi().view.activeStart;
    const activeEnd   = calendar.current.getApi().view.activeEnd;
    // const date = calendar.current?.getApi().view.currentStart;
    // const month = date.getMonth() + 1;
    // const year = date.getFullYear();

    const data = {
      post_type: props.postType ? [props.postType] : getValues(selectedPostType),
      taxonomy: getValues(selectedCategories, true),
      activeStart,
      activeEnd,
    };

    const results = await apiFetch<Option[]>({
      method: "POST",
      path: restRoute,
      data: data,
    });

    setEvents(results);
  };

  const updateSize = () => {
    setTimeout(() => {
      calendar.current?.doResize();
      calendar.current?.getApi().updateSize();
      calendar.current?.render();
    }, 100);
  };
  
  useEffect(() => {
    updateSize();

    getEvents();
  }, [selectedPostType, selectedCategories]);

  useEffect(() => {
    if ("layout_calendar" === props.context?.config.active) {
      updateSize();
    }
  }, [props.context?.config.active]);

  const handleSlidebarToggle = () => {
    setSidebarToggle(sidebarToggle ? false : true);
    updateSize();
  };

  /*
   * Check Dragable Event is out of calendar div
   */
  const isEventOverDiv = function (x, y) {
    const external_events = RefSidebar.current;
    if (!external_events) {
      return false;
    }
    const rect = external_events.getBoundingClientRect();
    return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
  };

  const getPostTypeColor = (postType: string) => {
    let index = (props._post_types || props.post_types).findIndex((type) => type.value === postType);
    if (index !== -1) {
      index = index % 10;
      return `wpsp-event-card-${index}`;
    }
  };

  const schedule_time = builderContext?.values?.calendar_schedule_time || props.schedule_time;
  const dateSettings = (getSettings || __experimentalGetSettings)();


  return (
    <div
      className={classNames(
        "wprf-control",
        "wprf-calender",
        `wprf-${props.name}-calender`,
        props?.classes
      )}
    >
      <div className="wpsp-calender-header">
        <div className="wpsp-post-select">
          <ReactSelectWrapper
            isDisabled={props.disablePostType}
            options={Object.values(props.post_types || [])}
            value={selectedPostType}
            onChange={setSelectedPostType}
            placeholder={__("Select Post Type", "wp-scheduled-posts")}
            showTags={true}
          />
        </div>
        <div className="wpsp-post-search">
          <input type="text" placeholder="Search" />
          <i className="wpsp-icon wpsp-search"></i>
        </div>
      </div>
      <div className="wpsp-calender-content main-content-wrapper">
        <div className={`main-content ${!sidebarToggle ? "basis-100" : ""}`}>
          <div className="toolbar">
            <div className="left">
              <CategorySelect
                selectedPostType={selectedPostType}
                onChange={(value) => {
                  setSelectedCategories([...value]);
                }}
              />
            </div>
            <div className="middle">
              {/* calendar dropdown */}
              {/* <input type="month" id="start" name="start"
              min="2018-03" value="2018-05"></input> */}

              {/* needed wrapper component so that month picker can work when Year prop is changed. */}
              <MyWrapperComponent
                locale="en"
                month={yearMonth.month}
                year={yearMonth.year}
              >
                <MonthPicker
                  ref={monthPicker}
                  onChange={({ year, month }) => {
                    setYearMonth({ month, year });
                    const date = `${year}-${month < 10 ? "0" + month : month}-01`;

                    calendar.current
                      ?.getApi()
                      .gotoDate(date);
                  }}
                >
                  <div className="calender-selected-month">
                    {calendar.current && calendar.current.getApi().view.title}
                    <span className="dashicons dashicons-arrow-down-alt2"></span>
                  </div>
                </MonthPicker>
              </MyWrapperComponent>
            </div>
            <div className="right">
              <div className="button-control-month">
                <button
                  type="button"
                  className="wpsp-prev-button wpsp-button-primary"
                  onClick={() => {
                    calendar.current?.getApi().prev();
                  }}
                >
                  <i className="wpsp-icon wpsp-prev"></i>
                </button>
                <button
                  type="button"
                  className="wpsp-next-button wpsp-button-primary"
                  onClick={() => {
                    calendar.current?.getApi().next();
                  }}
                >
                  <i className="wpsp-icon wpsp-next"></i>
                </button>
              </div>
              <button
              className="today-btn"
                onClick={() => {
                  calendar.current?.getApi().today();
                }}
              >
                Today
              </button>
              <i
                onClick={handleSlidebarToggle}
                className={`calendar-btn wpsp-icon wpsp-sidebar ${
                  !sidebarToggle ? "inactive" : ""
                }`}
              />
            </div>
          </div>
          <div className="wprf-calendar-wrapper">
            <div className="button-control-month">
              <button
                type="button"
                className="wpsp-prev-button wpsp-button-primary"
                onClick={() => {
                  calendar.current?.getApi().prev();
                }}
              >
                <i className="wpsp-icon wpsp-prev"></i>
              </button>
              <button
                type="button"
                className="wpsp-next-button wpsp-button-primary"
                onClick={() => {
                  calendar.current?.getApi().next();
                }}
              >
                <i className="wpsp-icon wpsp-next"></i>
              </button>
            </div>
            <FullCalendar
              ref={calendar}
              events={events}
              // timeZone='local'
              timeZone={timeZone}
              initialView="dayGridMonth"
              height="auto"
              plugins={[momentTimezonePlugin, dayGridPlugin, interactionPlugin]}
              // weekends={true}
              // firstDay={props.firstDay}
              // dateClick={handleDateClick}
              // Enable droppable option
              editable={true}
              droppable={true}
              defaultAllDay={false}
              eventResizableFromStart={false}
              eventDurationEditable={false}
              dragRevertDuration={0}
              // headerToolbar={false}
              dayMaxEvents={1}
              dayPopoverFormat={{ day: "numeric" }}
              moreLinkContent={(arg) => {
                return <>View {arg.num} More</>;
              }}
              eventContent={(eventInfo: EventContentArg) => {
                const post: PostType = getPostFromEvent(eventInfo.event);
                return (
                  <PostCard
                    post={post}
                    editAreaToggle={editAreaToggle}
                    setEditAreaToggle={setEditAreaToggle}
                    openModal={openModal}
                    setEvents={setEvents}
                    getPostTypeColor={getPostTypeColor}
                    status={ status }
                    setStatus={ setStatus }
                  />
                );
              }}
              dayCellDidMount={(args: DayCellMountArg) => {
                const dayTop =
                  args.el?.getElementsByClassName("fc-daygrid-day-top")[0];
                if (!dayTop) return;

                const button = document.createElement("button");
                button.innerHTML = "Add New";
                button.addEventListener("click", (event) => {
                  if (dayTop.parentElement?.parentElement?.classList.contains("fc-day-other")) {
                    event.preventDefault();
                    return;
                  }
                  openModal({
                    post: null,
                    eventType: "addEvent",
                    post_date: format("Y-m-d H:i:s", args.date),
                  });
                });

                dayTop.appendChild(button);
              }}
              // Provide a drop callback function
              eventReceive={(info) => {
                const event = info.event;
                const props = event.extendedProps;
                setDraftEvents((posts) =>
                  posts.filter((p) => p.postId !== props.postId)
                );
                if(event.allDay) {
                  // Set the event to not be an all-day event
                  event.setAllDay(false);

                  let _date;

                  // If schedule_time is provided, format the start date and append the schedule_time
                  if (schedule_time) {
                    const startDateFormatted = format('Y-m-d', event.start);
                    _date = `${startDateFormatted} ${schedule_time}`;
                  } else {
                    // If time is not provided, get the end date
                    _date = getEndDate(event.start, props._end);
                  }

                  // Create a moment object in UTC with the date
                  const dateMoment = moment.utc(_date);

                  // Adjust the moment object to the correct timezone
                  dateMoment.utcOffset(+dateSettings.timezone.offset, true);

                  // Set the end of the event to the adjusted date
                  event.setEnd(dateMoment.toDate());
                }
                eventDrop(event, 'eventDrop').then(updateEvents);
              }}
              eventDragStop={(info: EventDragStopArg) => {
                if(isEventOverDiv(info.jsEvent.clientX, info.jsEvent.clientY)) {
                  info.event.remove();
                  const post: PostType = getPostFromEvent(info.event);
                  setDraftEvents((posts) => [...posts, post]);
                  eventDrop(info.event, 'draftDrop').then((post) => {
                    setDraftEvents((events) => {
                      const index = events.findIndex((event) => event.postId === post.postId);
                      if (index === -1) {
                        return [...events, post];
                      }
                      const updatedEvents = [...events];
                      updatedEvents[index] = post;
                      return updatedEvents;
                    });
                  });
                }
              }}
              // eventLeave={(info) => {
              // }}
              eventRemove={(info) => {
                const props = info.event.extendedProps;
                setEvents((events) => events.filter((event) => event.postId !== props.postId));

              }}
              // moving events inside calendar area
              eventDrop={(eventDropInfo: EventDropArg) => {
                eventDrop(eventDropInfo.event, 'eventDrop').then(updateEvents);
              }}
              // eventClick={function (info) {
              //   console.log("Event: ", info.event.extendedProps);
              //   console.log("info: ", info);
              //   console.log(calendar.current?.getApi().view);

              //   // change the border color just for fun
              //   info.el.style.border = "1px solid red";
              // }}
              datesSet={(dateInfo) => {
                // get the current month and year
                const month = dateInfo.view.currentStart.getMonth() + 1;
                const year  = dateInfo.view.currentStart.getFullYear();
                if (yearMonth.year !== year || yearMonth.month !== month) {
                  // update the state
                  setYearMonth({
                    month: month,
                    year: year,
                  });
                }
                getEvents();
              }}
            />
          </div>
        </div>
        {sidebarToggle && (
            <Sidebar
              ref={RefSidebar}
              calendar={calendar}
              selectedPostType={selectedPostType}
              draftEvents={draftEvents}
              setDraftEvents={setDraftEvents}
              getPostTypeColor={getPostTypeColor}
              postType={props.postType}
              schedule_time={props.schedule_time}
              onSubmit={onSubmit}
            />
        )}
      </div>
      <ModalContent
        modalData={modalData}
        setModalData={openModal}
        onSubmit={onSubmit}
        selectedPostType={selectedPostType}
        schedule_time={props.schedule_time}
      />
    </div>
  );
}
