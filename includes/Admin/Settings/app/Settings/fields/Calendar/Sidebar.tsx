import { Draggable } from "@fullcalendar/interaction";
import apiFetch from "@wordpress/api-fetch";
import React, { MutableRefObject, forwardRef, useEffect, useState } from "react";
import CategorySelect from "./Category";
import { ModalContent } from "./EditPost";
import PostCard from "./EventRender";
import { getValues } from "./Helpers";
import { ModalProps, PostType, SidebarProps } from "./types";

const Sidebar = (
  { selectedPostType, draftEvents: posts, setDraftEvents: setPosts, calendar, getPostTypeColor, postType, schedule_time }: SidebarProps,
  draggableRef: MutableRefObject<HTMLDivElement>
) => {
  const [optionSelected, setOptionSelected] = useState([]);
  const [editAreaToggle, setEditAreaToggle] = useState([]);
  const [status, setStatus] = useState(null);
  const [modalData, openModal] = useState<ModalProps>({ post: null, eventType: null });
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false); // Request in progress
  const [hasMore, setHasMore] = useState(true); // If there are more posts to load
  const postsPerPage = 10;

  const onSubmit = (data: any, oldData) => {
    const newEvents = posts.filter((event) => event.postId !== oldData?.postId);
    setPosts([...newEvents, data]);
  };

  const throttle = (func: Function, limit: number) => {
    let lastFunc: NodeJS.Timeout | null;
    let lastRan: number | null;

    return function (...args: any[]) {
      const context = this;
      if (!lastRan) {
        func.apply(context, args);
        lastRan = Date.now();
      } else {
        if (lastFunc) clearTimeout(lastFunc);
        lastFunc = setTimeout(() => {
          if (Date.now() - lastRan! >= limit) {
            func.apply(context, args);
            lastRan = Date.now();
          }
        }, limit - (Date.now() - lastRan!));
      }
    };
  };

  const handleScroll = throttle(() => {
    if (loading || !hasMore) return; // Prevent scrolling if still loading
    const sidebarWrapper = document.getElementById("sidebar-post-wrapper");
    if (sidebarWrapper) {
      const { scrollTop, scrollHeight, clientHeight } = sidebarWrapper;
      if (scrollTop + clientHeight >= scrollHeight - 100) {
        setPage((prevPage) => prevPage + 1);
      }
    }
  }, 200);

  const fetchPosts = async (page: number, force = false) => {
    if (loading || !hasMore) return; // Ensure only one request is made at a time
    setLoading(true); // Set loading before starting the request
    const query = {
      post_type: postType ? [postType] : getValues(selectedPostType) ?? ["post"],
      post_status: ["draft", "pending"],
      posts_per_page: postsPerPage,
      taxonomy: optionSelected,
      page: page,
    };

    try {
      const data: PostType[] = await apiFetch({
        method: "POST",
        path: "/wpscp/v1/posts",
        data: query,
      });

      if (data.length < postsPerPage) {
        setHasMore(false); // No more posts to load
      }
      if( optionSelected.length > 0 ) {
        setHasMore(true);
        if( page == 1 ) {
          setPosts([]);
        }
        // @ts-ignore 
        setPosts((prevPosts) => [...prevPosts, ...data]);
      }else{
        // @ts-ignore 
        if(force) {
          // @ts-ignore 
          setHasMore(true);
          setPosts([ ...data]);
        }else{
          // @ts-ignore 
          setPosts((prevPosts) => [...prevPosts, ...data]);
        }
      }
    } catch (error) {
      console.error("Error fetching posts:", error);
    } finally {
      setLoading(false); // Reset loading after request completion
    }
  };

  useEffect(() => {
    new Draggable(draggableRef.current, {
      itemSelector: ".fc-event",
      eventData: function (eventEl) {
        const post = JSON.parse(eventEl.getAttribute("data-event"));
        post._end = post.end;
        return post;
      },
    });
    
    const sidebarWrapper = document.getElementById("sidebar-post-wrapper");
    if (sidebarWrapper) {
      sidebarWrapper.addEventListener("scroll", handleScroll);
    }

    return () => {
      if (sidebarWrapper) {
        sidebarWrapper.removeEventListener("scroll", handleScroll);
      }
    };
  }, [selectedPostType, optionSelected]); // Trigger when filters change


  useEffect(() => {
    fetchPosts(page); // Trigger when page changes
  }, [page]);

  useEffect(() => {
    setPage(1);
    fetchPosts(page, true);
  }, [optionSelected])
  

  return (
    <div id="wpsp-sidebar" className="sidebar" ref={draggableRef}>
      <div id="external-events">
        <div id="external-events-listing">
          <h4 className="unscheduled">
            Unscheduled Posts <span className="spinner"></span>
          </h4>
          <CategorySelect 
            selectedPostType={selectedPostType} 
            onChange={(value) => {
              setOptionSelected([...value]);
          }}
            showTags 
          />
          <div className="event-wrapper" id="sidebar-post-wrapper">
            {posts
              .sort((a, b) => new Date(b.end).getTime() - new Date(a.end).getTime())
              .map((post: PostType) => (
                <div key={post.postId} className="fc-event" data-event={JSON.stringify(post)}>
                  <PostCard
                    post={post}
                    editAreaToggle={editAreaToggle}
                    setEditAreaToggle={setEditAreaToggle}
                    openModal={(modalData) => openModal({ ...modalData, eventType: "editDraft" })}
                    setEvents={setPosts}
                    getPostTypeColor={getPostTypeColor}
                    status={status}
                    setStatus={setStatus}
                  />
                </div>
              ))}
          </div>
        </div>
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
};

export default forwardRef(Sidebar);
