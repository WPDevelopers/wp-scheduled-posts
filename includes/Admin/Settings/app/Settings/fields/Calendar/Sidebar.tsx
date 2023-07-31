// Import React and other dependencies
import { Draggable } from "@fullcalendar/interaction";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from '@wordpress/url';
import React, { MutableRefObject, forwardRef, useCallback, useEffect, useRef, useState } from "react";
// @wordpress/component
import CategorySelect from "./Category";
import PostCard from "./EventRender";
import { getValues } from "./Helpers";
import { PostType, SidebarProps } from "./types";

// Define your component
const Sidebar = ({openModal, selectedPostType, draftEvents: posts, setDraftEvents: setPosts}: SidebarProps, draggableRef: MutableRefObject<HTMLDivElement>
  ) => {
  // Define your state variables
  const [optionSelected, setOptionSelected] = useState([]);
  const [editAreaToggle, setEditAreaToggle] = useState([]);

  useEffect(() => {
    // In your external element component componentDidMount
    new Draggable(draggableRef.current, {
      itemSelector: ".fc-event",
      // @ts-ignore
      dropAccept: ".fc-event",
      // Associate event data with the element
      eventData: function (eventEl) {
        const post = JSON.parse(eventEl.getAttribute("data-event"));
        return post;
      },
    });

  }, []);

  // Fetch your posts and taxonomies using useEffect hook
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    // Get the _page value from the params object
    const page = params.get('page');

    // Define your query parameters
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


  const deletePost = useCallback((id) => {
    apiFetch({
      path: addQueryArgs("/wpscp/v1/post", {ID: id}),
      method: "DELETE",
      // data: query,
    }).then((data: []) => {
      // Set your posts state with the fetched data
      console.log(data);
    });
  }, []);

  console.log('Sidebar', posts);

  // Return your JSX element
  return (
    <div id="external-events">
      <div id="external-events-listing">
        <h4 className="unscheduled">
          {/* {Object.values(selectedPostType).length == 1 ? selectedPostType[0].value : "Posts"} */}
          Unscheduled Posts {" "}
          <span className="spinner"></span>
        </h4>
        <CategorySelect selectedPostType={selectedPostType} onChange={setOptionSelected} showTags />
        <div className="event-wrapper" ref={draggableRef}>
          {posts.sort((a, b) => (new Date(b.end)).getTime() - (new Date(a.end)).getTime()).map(
            (
              post: PostType // Loop through your posts using map method
            ) => (
              <div key={post.postId} className="fc-event" data-event={JSON.stringify(post)}>
                <PostCard
                  post={post}
                  editAreaToggle={editAreaToggle}
                  setEditAreaToggle={setEditAreaToggle}
                  openModal={openModal}
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
          href="#wpscp_quickedit"
          rel="modal:open"
          data-type="draft"
        >
          New Draft
        </a>
      </p>
    </div>
  );
}


export default forwardRef(Sidebar);