import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction"; // needed for dayClick
import FullCalendar from "@fullcalendar/react";
import apiFetch from "@wordpress/api-fetch";
import { useBuilderContext } from "quickbuilder";
import React, { useEffect, useRef, useState } from "react";
import { PostCardProps } from "./Calendar/EventRender";
import Sidebar from "./Calendar/Sidebar";
// const events = [{ title: "Meeting", start: new Date() }];
import MonthPicker from "@compeon-os/monthpicker";
import { EventContentArg } from "@fullcalendar/core";
import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import { getMonth, getYear } from "date-fns";
import CategorySelect from "./Calendar/Category";
import { ModalContent } from "./Calendar/EditPost";
import PostCard from "./Calendar/EventRender";
import { getValues } from "./Calendar/Helpers";
import ReactSelectWrapper, { Option, addAllOption, getOptionsFlatten } from "./Calendar/ReactSelectWrapper";

export default function Calendar(props) {
  // @ts-ignore
  const restRoute = props.rest_route;
  const calendar = useRef<FullCalendar>();
  // monthPicker
  const monthPicker = useRef<MonthPicker>();
  const builderContext = useBuilderContext();
  const [events, setEvents] = useState([]);
  //
  const currentDate = new Date();
  const [yearMonth, setYearMonth] = useState({
    month: getMonth(currentDate) + 1,
    year: getYear(currentDate),
  });

  const [modalData, openModal] = useState<{post: any, eventType: string, post_date?: Date}>({post: null, eventType: null});
  const onSubmit = (data: any, oldData) => {
    const newEvents = events.filter((event) => event.postId !== oldData.postId);
    console.log(newEvents);

    setEvents([...newEvents, data]);
  };

  const [sidebarToggle, setSidebarToggle] = useState(true);
  const [editAreaToggle, setEditAreaToggle] = useState({});

  const [selectedPostType, setSelectedPostType] = useState<Option[]>(addAllOption(getOptionsFlatten(props.post_types)));
  const [selectedCategories, setSelectedCategories] = useState<Option[]>([]);


  const MyWrapperComponent = ({ children, ...rest }) => {
    return React.cloneElement(children, { ...rest });
  };

  const getEvents = async () => {
    const date  = calendar.current?.getApi().view.currentStart;
    const month = date.getMonth() + 1;
    const year  = date.getFullYear();

    const data = {
      post_type: getValues(selectedPostType),
      taxonomy : (selectedCategories),
      month    : month,
      year     : year,
    };

    const results = await apiFetch<Option[]>({
      method: 'POST',
      path  : restRoute,
      data  : data,
    });

    setEvents(results);
  };

  useEffect(() => {
    calendar.current?.doResize();
    calendar.current?.render();

    getEvents();
  }, [selectedPostType, selectedCategories]);

  useEffect(() => {
    // console.log(builderContext.config.active);
    if ("layout_calendar" === builderContext.config.active) {
      calendar.current?.getApi().updateSize();
    }
  }, [builderContext.config.active]);

  const handleSlidebarToggle = () => {
    setSidebarToggle(sidebarToggle ? false : true);
  };

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
                  console.log(value);

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
                    calendar.current
                      ?.getApi()
                      .gotoDate(new Date(year, month - 1));
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
              <button
                onClick={() => {
                  calendar.current?.getApi().today();
                }}
              >
                Today
              </button>
              <i
                onClick={handleSlidebarToggle}
                className={`wpsp-icon wpsp-manual-sc ${
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
              plugins={[dayGridPlugin, interactionPlugin]}
              initialView="dayGridMonth"
              // dateClick={handleDateClick}
              // Enable droppable option
              editable={true}
              droppable={true}
              eventResizableFromStart={false}
              eventDurationEditable={false}
              // headerToolbar={false}
              dayMaxEvents={1}
              dayPopoverFormat={{ day: "numeric" }}
              moreLinkContent={(arg) => {
                return <>View {arg.num} More</>;
              }}
              // weekends={true}
              events={events}
              // firstDay={props.firstDay}
              eventContent={(eventInfo: EventContentArg) => {
                const { title, start, end, allDay } = eventInfo.event;
                const { postId, href, edit, status, postType, postTime } =
                  eventInfo.event.extendedProps;
                const post: PostCardProps["post"] = {
                  postId: postId,
                  postTime: postTime,
                  postType: postType,
                  status: status,
                  title: title,
                  href: href,
                  edit: edit,
                };
                return (
                  <PostCard
                    post={post}
                    editAreaToggle={editAreaToggle}
                    setEditAreaToggle={setEditAreaToggle}
                    openModal={openModal}
                  />
                );
              }}
              dayCellDidMount={(args) => {
                const dayTop = args.el?.getElementsByClassName("fc-daygrid-day-top")[0];
                if (!dayTop) return;

                const button = document.createElement("button");
                button.innerHTML = "Add New";
                button.disabled = args.isOther;
                button.addEventListener("click", () => {
                  console.log("click", args);
                  openModal({ post: null, eventType: "addEvent", post_date: args.date });
                });

                dayTop.appendChild(button);
              }}
              // Provide a drop callback function
              eventReceive={(info) => {
                const props = info.event.extendedProps;
                props.setPosts((posts) =>
                  posts.filter((p) => p.postId !== props.postId)
                );
                console.log("drop", info, props);
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
                const year = dateInfo.view.currentStart.getFullYear();
                console.log("datesSet", { year, month });
                if (yearMonth.year !== year || yearMonth.month !== month) {
                  // update the state
                  setYearMonth({
                    month: month,
                    year: year,
                  });
                }
              }}
            />
          </div>
        </div>
        {sidebarToggle && (
          <div className="sidebar">
            <Sidebar
              openModal={openModal}
              selectedPostType={selectedPostType}
            />
          </div>
        )}
      </div>
      {<ModalContent modalData={modalData} setModalData={openModal} onSubmit={onSubmit} />}
    </div>
  );
}
