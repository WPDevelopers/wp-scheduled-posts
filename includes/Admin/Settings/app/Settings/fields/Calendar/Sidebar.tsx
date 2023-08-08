// Import React and other dependencies
import { Draggable } from "@fullcalendar/interaction";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from '@wordpress/url';
import React, { MutableRefObject, forwardRef, useCallback, useEffect, useRef, useState } from "react";
// @wordpress/component
import CategorySelect from "./Category";
import PostCard from "./EventRender";
import { getUTCDate, getValues } from "./Helpers";
import { ModalProps, PostType, SidebarProps } from "./types";
import { ModalContent } from "./EditPost";

// Define your component
const Sidebar = ({selectedPostType, draftEvents: posts, setDraftEvents: setPosts, calendar, getPostTypeColor}: SidebarProps, draggableRef: MutableRefObject<HTMLDivElement>
  ) => {
  // Define your state variables
  const [optionSelected, setOptionSelected] = useState([]);
  const [editAreaToggle, setEditAreaToggle] = useState([]);

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
      post_type: getValues(selectedPostType) ?? ["post"], // Use selectedPostType state or default to ["post"]
      post_status: ["draft", "pending"],
      posts_per_page: -1,
      taxonomy : (optionSelected),
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
      console.log('error', error);
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
            console.log('openModal', e);

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
    />
  </div>
  );
}


export default forwardRef(Sidebar);