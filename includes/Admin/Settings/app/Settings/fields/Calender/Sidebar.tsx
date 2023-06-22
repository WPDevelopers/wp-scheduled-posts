// Import React and other dependencies
import React, { useEffect, useRef, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { Draggable } from "@fullcalendar/interaction";

// Define your component
export default function Sidebar() {
  // Define your state variables
  const [posts, setPosts] = useState([]);
  const [postType, setPostType] = useState(null);
  const [allowCategories, setAllowCategories] = useState([]);
  const [taxTerms, setTaxTerms] = useState({});
  const draggableRef = useRef<HTMLDivElement>();

  useEffect(() => {
    // In your external element component componentDidMount
    new Draggable(draggableRef.current, {
      itemSelector: ".fc-event",
      // Associate event data with the element
      eventData: function (eventEl) {
        console.log(eventEl);

        return {
          title: eventEl.innerHTML,
        };
      },
    });
  }, []);

  // Fetch your posts and taxonomies using useEffect hook
  useEffect(() => {
    // Define your query parameters
    const query = {
      post_type: postType ? postType : ["post", "page"], // Use postType state or default to ["post", "page"]
      post_status: ["draft", "pending"],
      posts_per_page: -1,
    };
    // Fetch your posts using apiFetch
    apiFetch({
      path: "/wp/v2/posts",
      //   data: query,
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

  // Return your JSX element
  return (
    <div id="external-events">
      <div id="external-events-listing">
        <h4 className="unscheduled">
          Unscheduled {postType ? postType : "Posts"}{" "}
          <span className="spinner"></span>
        </h4>
        {postType !== "page" && (
          <select
            id="external-events-filter"
            multiple={true}
            style={{ width: "100%" }}
            onChange={handleSelectChange}
            value={allowCategories}
            defaultValue={["all"]}
          >
            <option value="all">All</option>
            {Object.keys(taxTerms).map(
              (
                taxLabel // Loop through your taxonomies using map method
              ) => (
                <optgroup label={taxLabel}>
                  {Object.keys(taxTerms[taxLabel]).map?.(
                    (
                      termLabel // Loop through your terms using map method
                    ) => {
                      const term = taxTerms[taxLabel][termLabel];
                      return (
                        <option
                          value={`${term.taxonomy}.${term.slug}`}
                          data-tax={term.taxonomy}
                        >
                          {term.name}
                        </option>
                      );
                    }
                  )}
                </optgroup>
              )
            )}
          </select>
        )}
        <div ref={draggableRef}>
          {posts.map(
            (
              post // Loop through your posts using map method
            ) => (
              <div className="fc-event">
                <div
                  className="wpscp-event-post"
                  data-postid={post.id}
                  data-post-type={post.type}
                  key={post.id}
                >
                  <div className="postlink ">
                    <span>
                      <span className="posttime">[{post.date}]</span>{" "}
                      {post.title.rendered} [{post.status}]
                    </span>
                  </div>
                </div>
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
