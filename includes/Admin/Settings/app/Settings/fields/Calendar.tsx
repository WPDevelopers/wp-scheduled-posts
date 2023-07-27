import React, { ReactElement, useEffect, useRef, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import FullCalendar from "@fullcalendar/react";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction"; // needed for dayClick
import Sidebar from "./Calendar/Sidebar";
import renderEventContent, { PostCardProps } from "./Calendar/EventRender";
import { useBuilderContext } from "quickbuilder";
// const events = [{ title: "Meeting", start: new Date() }];
import { ActionMeta, MultiValue, default as ReactSelect } from "react-select";
import { selectStyles } from "../helper/styles";
import { components } from "react-select";
import MonthPicker from "@compeon-os/monthpicker";
import classNames from "classnames";
import { __ } from "@wordpress/i18n";
import { EventContentArg } from "@fullcalendar/core";
import PostCard from "./Calendar/EventRender";
import useEditPost, { ModalContent } from "./Calendar/EditPost";
import CategorySelect from "./Calendar/Category";
import { getYear, getMonth } from "date-fns";

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
  const [sidebarToogle, setSidebarToggle] = useState(true);
  const [editAreaToggle, setEditAreaToggle] = useState({});
  const allOption = [
    { label: "All", value: "all" },
    ...Object.values(props.post_types || []),
  ];
  const [selectedPostType, setSelectedPostType] =
    useState<MultiValue<any>>(allOption);

  const editPostModalProps = useEditPost();

  const getUrl = () => {
    const date = calendar.current?.getApi().view.currentStart;
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    console.warn(date, month, year);

    const queryParams = {
      post_type: "post",
      month: month,
      year: year,
    };
    return addQueryArgs(restRoute, queryParams);
  };

  const MyWrapperComponent = ({ children, ...rest }) => {
    return React.cloneElement(children, { ...rest });
  };

  useEffect(() => {
    calendar.current?.doResize();
    calendar.current?.render();
    //
    apiFetch({
      path: getUrl(),
    }).then((data: []) => {
      setEvents(data);
    });
  }, []);

  useEffect(() => {
    // console.log(builderContext.config.active);
    if ("layout_calendar" === builderContext.config.active) {
      calendar.current?.getApi().updateSize();
    }
  }, [builderContext.config.active]);

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

  const handleSlidebarToggle = () => {
    setSidebarToggle(sidebarToogle ? false : true);
  };

  // Add and remove
  const handleChange = (
    newValue: MultiValue<any>,
    actionMeta: ActionMeta<any>
  ) => {
    console.log(actionMeta, newValue);
    if (actionMeta.action === "select-option") {
      if (actionMeta.option.value === "all") {
        newValue = allOption;
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
        if (newValue.length === Object.values(props.post_types).length) {
          newValue = allOption;
        }
      }
    } else if (actionMeta.action === "deselect-option") {
      if (actionMeta.option.value === "all") {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
        if (newValue.length === 0) {
          newValue = allOption;
        }
      }
    }
    setSelectedPostType(newValue);
  };
  const removeItem = (item) => {
    const updatedItems = selectedPostType.filter((i) => i !== item);
    handleChange(updatedItems, {
      action: "deselect-option",
      option: item,
    });
  };

  console.log("monthPicker", monthPicker);

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
          <ReactSelect
            options={allOption}
            styles={selectStyles}
            closeMenuOnSelect={false}
            hideSelectedOptions={false}
            placeholder={__("Select Post Type", "wp-scheduled-posts")}
            autoFocus={false}
            isMulti
            components={{
              Option,
            }}
            value={selectedPostType}
            onChange={handleChange}
            controlShouldRenderValue={false}
            className="main-select"
          />
          <div className="selected-options">
            <ul>
              {selectedPostType?.map((item, index) => {
                return (
                  <li key={index}>
                    {" "}
                    {item?.label}{" "}
                    <button onClick={() => removeItem(item)}>
                      {" "}
                      <i className="wpsp-icon wpsp-close"></i>{" "}
                    </button>{" "}
                  </li>
                );
              })}
            </ul>
          </div>
        </div>
        <div className="wpsp-post-search">
          <input type="text" placeholder="Search" />
          <i className="wpsp-icon wpsp-search"></i>
        </div>
      </div>
      <div className="wpsp-calender-content main-content-wrapper">
        <div className={`main-content ${!sidebarToogle ? "basis-100" : ""}`}>
          <div className="toolbar">
            <div className="left">
              <CategorySelect
                selectedPostType={selectedPostType}
                Option={Option}
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
                  !sidebarToogle ? "inactive" : ""
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
                    openModal={editPostModalProps.openModal}
                  />
                );
              }}
              dayCellDidMount={(args) => {
                // console.log('dayCellDidMount', args);
                const dayTop =
                  args.el.getElementsByClassName("fc-daygrid-day-top");
                // add a button on dayTop element as child
                const button = document.createElement("button");
                button.innerHTML = "Add New";
                if (args.isOther) {
                  button.disabled = true;
                }
                button.addEventListener("click", (event) => {
                  console.log("click", event, args);
                });
                dayTop[0].appendChild(button);
              }}
              // dateClick={handleDateClick}
              // Enable droppable option
              editable={true}
              droppable={true}
              // headerToolbar={false}
              // Provide a drop callback function
              eventReceive={(info) => {
                const props = info.event.extendedProps;
                props.setPosts((posts) =>
                  posts.filter((p) => p.postId !== props.postId)
                );
                console.log("drop", info, props);
              }}
              eventClick={function (info) {
                console.log("Event: ", info.event.extendedProps);
                console.log("info: ", info);
                console.log(calendar.current?.getApi().view);

                // change the border color just for fun
                info.el.style.border = "1px solid red";
              }}
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
        {sidebarToogle && (
          <div className="sidebar">
            <Sidebar
              openModal={editPostModalProps.openModal}
              selectedPostType={selectedPostType}
              Option={Option}
            />
          </div>
        )}
      </div>
      {<ModalContent {...editPostModalProps} />}
    </div>
  );
}
