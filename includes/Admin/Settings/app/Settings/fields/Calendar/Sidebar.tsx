// Import React and other dependencies
import React, { useCallback, useEffect, useRef, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { Draggable } from "@fullcalendar/interaction";
import { addQueryArgs } from '@wordpress/url';
// @wordpress/component
import { Button } from "@wordpress/components";
import { default as ReactSelect } from "react-select";
import { selectStyles } from "../../helper/styles";
import { components } from "react-select";
import { __ } from "@wordpress/i18n";
import PostCard from "./EventRender";
import useEditPost from "./EditPost";
import CategorySelect from "./Category";
import { getValues } from "./Helpers";

// Define your component
export default function Sidebar({openModal, selectedPostType}) {
  // Define your state variables
  const [posts, setPosts] = useState([]);
  const [taxTerms, setTaxTerms] = useState({});
  const draggableRef = useRef<HTMLDivElement>();
  const [optionSelected, setOptionSelected] = useState([]);
  const [editAreaToggle, setEditAreaToggle] = useState([]);

  useEffect(() => {
    // In your external element component componentDidMount
    new Draggable(draggableRef.current, {
      itemSelector: ".fc-event",
      // Associate event data with the element
      eventData: function (eventEl) {
        const post = JSON.parse(eventEl.getAttribute("data-event"));
        return {...post, setPosts};
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

  console.log(posts);

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
        <div ref={draggableRef}>
          {posts.map(
            (
              post // Loop through your posts using map method
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
