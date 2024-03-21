// Import React and other dependencies
import { Draggable } from "@fullcalendar/interaction";
import apiFetch from "@wordpress/api-fetch";
import React, { MutableRefObject, forwardRef, useEffect, useState } from "react";
// @wordpress/component
import CategorySelect from "./Category";
import { ModalContent } from "./EditPost";
import PostCard from "./EventRender";
import { getValues } from "./Helpers";
import { ModalProps, PostType, SidebarProps } from "./types";

// Define your component
const Sidebar = ({selectedPostType, draftEvents: posts, setDraftEvents: setPosts, calendar, getPostTypeColor, postType, schedule_time}: SidebarProps, draggableRef: MutableRefObject<HTMLDivElement>
  ) => {
  // Define your state variables
  const [optionSelected, setOptionSelected] = useState([]);
  const [editAreaToggle, setEditAreaToggle] = useState([]);
  const [status, setStatus] = useState( null );

  const [modalData, openModal] = useState<ModalProps>({ post: null, eventType: null });
  const onSubmit = (data: any, oldData) => {
    const newEvents = posts.filter((event) => event.postId !== oldData?.postId);
    setPosts([...newEvents, data]);
  };


  useEffect(() => {
    // In your external element component componentDidMount
    new Draggable(draggableRef.current, {
      itemSelector: ".fc-event",
      // Associate event data with the element
      eventData: function (eventEl) {
        const post = JSON.parse(eventEl.getAttribute("data-event"));
        post._end = post.end;
        // const end = getUTCDate(post.end);
        // post.duration = {
        //   hours  : end.getHours(),
        //   minutes: end.getMinutes(),
        //   seconds: end.getSeconds(),
        // };

        // const event = calendar.current?.getApi().addEvent(post);

        return post;
      },
    });

  }, []);

  // Fetch your posts and taxonomies using useEffect hook
  useEffect(() => {
    const query = {
      post_type: postType ? [postType] : (getValues(selectedPostType) ?? ["post"]), // Use selectedPostType state or default to ["post"]
      post_status: ["draft", "pending"],
      posts_per_page: -1,
      taxonomy : getValues(optionSelected, true),
      // page: page,
    };
    // Fetch your posts using apiFetch
    apiFetch({
      method: "POST",
      path: "/wpscp/v1/posts",
      data: query,
    }).then((data: []) => {
      // Set your posts state with the fetched data
      setPosts(data);
    }).catch((error) => {
      // console.log('error', error);
    });

  }, [selectedPostType, optionSelected]); // Re-run the effect when selectedPostType changes

  return (
  <div id="wpsp-sidebar" className="sidebar" ref={draggableRef}>
    <div id="external-events">
      <div id="external-events-listing">
        <h4 className="unscheduled">
          {/* {Object.values(selectedPostType).length == 1 ? selectedPostType[0].value : "Posts"} */}
          Unscheduled Posts {" "}
          <span className="spinner"></span>
        </h4>
        <CategorySelect selectedPostType={selectedPostType} onChange={setOptionSelected} showTags />
        <div className="event-wrapper">
          {posts.sort((a, b) => (new Date(b.end)).getTime() - (new Date(a.end)).getTime()).map(
            (
              post: PostType // Loop through your posts using map method
            ) => (
              <div key={post.postId} className="fc-event" data-event={JSON.stringify(post)}>
                <PostCard
                  post={post}
                  editAreaToggle={editAreaToggle}
                  setEditAreaToggle={setEditAreaToggle}
                  openModal={(modalData) => openModal({ ...modalData, eventType: 'editDraft' })}
                  setEvents={setPosts}
                  getPostTypeColor={getPostTypeColor}
                  status={ status }
                  setStatus={ setStatus }
                />
              </div>
            )
          )}
        </div>
      </div>
      {/* Link to open the modal */}
      <p>
        <a
          className="btn-draft-post-create"
          href="#"
          rel="modal:open"
          data-type="draft"
          onClick={(e) => {
            e.preventDefault();
            openModal({
              post: null,
              eventType: "newDraft",
              openModal: true,
            });
          }}
        >
          New Draft
        </a>
      </p>
    </div>
    <ModalContent
      modalData={modalData}
      setModalData={openModal}
      onSubmit={onSubmit}
      selectedPostType={selectedPostType}
      schedule_time={schedule_time}
    />
  </div>
  );
}


export default forwardRef(Sidebar);