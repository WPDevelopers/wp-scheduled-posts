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

// Define your component
export default function Sidebar({props,handleOpenModal}) {
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
    {label : "Option 2",value : "options-2"},
  ]

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
        <ReactSelect
          options={options}
          styles={selectStyles}
          closeMenuOnSelect={false}
          hideSelectedOptions={false}
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
        <div ref={draggableRef}>
          {posts.map(
            (
              post // Loop through your posts using map method
            ) => (
              <div className="fc-event" data-event={JSON.stringify(post)}>
                <div className="card">
                  <i 
                    className="wpsp-icon wpsp-dots"
                    onClick={ () => {
                      setEditAreaToggle(() => {
                        let checkExistingIndex = editAreaToggle.findIndex((item) => item.post === post.postId)
                        if( checkExistingIndex !== -1 ) {
                          return [
                            {
                              post : post.postId,
                              value : editAreaToggle[checkExistingIndex].value ? false : true,
                            }
                          ];
                        }else{
                          return [
                            {
                              post: post.postId,
                              value: true,
                            },
                          ];
                        }
                      });
                    } }
                  >  
                  </i>
                  { editAreaToggle.find(item => item.post === post.postId)?.value && (
                    <ul className="edit-area">
                    <li><Button variant="link" target="_blank" href={decodeURIComponent(post.href)}>View</Button></li>
                    <li><Button variant="link" target="_blank" href={decodeURIComponent(post.edit)}>Edit</Button></li>
                    <li><Button variant="link" href="#" onClick={(event) => {
                      event.preventDefault();
                      handleOpenModal(post);
                    }}>Quick Edit</Button></li>
                    <li><Button variant="link" href="#" onClick={(event) => {
                      event.preventDefault();
                      deletePost(post.postId);
                    }}>Delete</Button></li>
                  </ul>
                  ) }
                  <span className="set-time">{post.postTime}</span>
                  <h3>{post.title}</h3>
                  <span className="Unscheduled-badge">page</span>
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
