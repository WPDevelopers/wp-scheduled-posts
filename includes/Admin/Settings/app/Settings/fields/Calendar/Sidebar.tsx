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

// Define your component
export default function Sidebar({openModal, selectedPostType, Option}) {
  // Define your state variables
  const [posts, setPosts] = useState([]);
  const [postType, setPostType] = useState(null);
  const [allowCategories, setAllowCategories] = useState([]);
  const [taxTerms, setTaxTerms] = useState({});
  const draggableRef = useRef<HTMLDivElement>();
  const [optionSelected, setOptionSelected] = useState([]);

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
      post_type: postType ? postType : ["post", "page"], // Use postType state or default to ["post", "page"]
      post_status: ["draft", "pending"],
      posts_per_page: -1,
      page: page,
    };
    // Fetch your posts using apiFetch
    apiFetch({
      path: addQueryArgs("/wpscp/v1/posts", query),
      // data: query,
    }).then((data: []) => {
      // Set your posts state with the fetched data
      setPosts(data);
    });

    // Fetch your taxonomies using apiFetch
    apiFetch({
      path: "/wpscp/v1/get_tax_terms",
    }).then((data) => {
      // Set your taxTerms state with the fetched data
      setTaxTerms(data);
    });
  }, [postType]); // Re-run the effect when postType changes

  // Define your handleDragStart function
  function handleDragStart(e) {
    // Get the id of the dragged item
    let id = e.target.id;
    // Set the dataTransfer object with the id
    e.dataTransfer.setData("text/plain", id);
  }

  // Define your handleSelectChange function
  function handleSelectChange(e) {
    // Get the selected options from the event target
    let options = e.target.options;
    // Create an array to store the selected values
    let values = [];
    // Loop through the options and push the selected values to the array
    for (let i = 0; i < options.length; i++) {
      if (options[i].selected) {
        values.push(options[i].value);
      }
    }
    // Set the allowCategories state with the selected values
    setAllowCategories(values);
  }

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

  // Add and remove
  const handleChange = (selected) => {
    setOptionSelected(selected);
  };
  const removeItem = (item) => {
    const updatedItems = optionSelected.filter((i) => i !== item);
    setOptionSelected(updatedItems);
  };

  const [editAreaToggle,setEditAreaToggle] = useState([]);

  // Return your JSX element
  return (
    <div id="external-events">
      <div id="external-events-listing">
        <h4 className="unscheduled">
          Unscheduled {postType ? postType : "Posts"}{" "}
          <span className="spinner"></span>
        </h4>
        <CategorySelect selectedPostType={selectedPostType} Option={Option} showTags />
        <div className="selected-options">
            <ul>
              { optionSelected?.map( (item, index) => (
                <li key={index}> { item?.label } <button onClick={() => removeItem(item)}> <i className='wpsp-icon wpsp-close'></i> </button> </li>
              ))}
            </ul>
        </div>
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
