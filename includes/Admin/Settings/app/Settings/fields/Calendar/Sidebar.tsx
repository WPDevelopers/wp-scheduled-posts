import { Draggable } from "@fullcalendar/interaction";
import apiFetch from "@wordpress/api-fetch";
import React, { MutableRefObject, forwardRef, useEffect, useRef, useState } from "react";
import CategorySelect from "./Category";
import { ModalContent } from "./EditPost";
import PostCard from "./EventRender";
import { getValues } from "./Helpers";
import { ModalProps, PostType, SidebarProps } from "./types";

const Sidebar = (
  { selectedPostType, draftEvents: posts, setDraftEvents: setPosts, calendar, getPostTypeColor, postType, schedule_time, onSubmit }: SidebarProps,
  draggableRef: MutableRefObject<HTMLDivElement>
) => {
  const [optionSelected, setOptionSelected] = useState([]);
  const [editAreaToggle, setEditAreaToggle] = useState([]);
  const [status, setStatus] = useState(null);
  const [modalData, openModal] = useState<ModalProps>({ post: null, eventType: null });
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const scrollTimeout = useRef<NodeJS.Timeout | null>(null);
  const [newPosts, setNewPosts] = useState<PostType[]>([]);
  const postsPerPage = 10;

  const fetchPosts = async (pageNum: number, force = false) => {
    if (loading || (!hasMore && !force)) return;
    
    setLoading(true);
    const query = {
      post_type: postType ? [postType] : getValues(selectedPostType) ?? ["post"],
      post_status: ["draft", "pending"],
      posts_per_page: postsPerPage,
      taxonomy: optionSelected,
      page: pageNum,
    };

    try {
      const data: PostType[] = await apiFetch({
        method: "POST",
        path: "/wpscp/v1/posts",
        data: query,
      });

      if (data.length < postsPerPage) {
        setHasMore(false);
      } else {
        setHasMore(true);
      }

      if (pageNum === 1 || force) {
        setPosts(data); // Reset posts on new filters
      } else {
        // @ts-ignore 
        setPosts((prevPosts) => [...prevPosts, ...data]);
      }
    } catch (error) {
      console.error("Error fetching posts:", error);
    } finally {
      setLoading(false);
    }
  };

  const handleScroll = () => {
    if (scrollTimeout.current) return; // Already scheduled
  
    scrollTimeout.current = setTimeout(() => {
      scrollTimeout.current = null; // Reset
  
      if (loading || !hasMore) return;
      const sidebarWrapper = document.getElementById("sidebar-post-wrapper");
      if (sidebarWrapper) {
        const { scrollTop, scrollHeight, clientHeight } = sidebarWrapper;
        if (scrollTop + clientHeight >= scrollHeight - 100) {
          setPage((prevPage) => prevPage + 1);
        }
      }
    }, 300); // 300ms debounce
  };

  useEffect(() => {
    new Draggable(draggableRef.current, {
      itemSelector: ".fc-event",
      eventData: (eventEl) => {
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
  }, []);

  useEffect(() => {
    fetchPosts(page);
  }, [page]);

  useEffect(() => {
    setPage(1);
    setHasMore(true);
    fetchPosts(1, true);
  }, [optionSelected]);

  const onSubmitHandler = (data: PostType, oldData?: PostType) => {
    // Only add to newPosts if it's a newly created post (not edited)
    if (!oldData) {
      setNewPosts((prev) => [...prev, data]);
    }
  };
  

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
            {newPosts.length > 0 && newPosts
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
        onSubmit={onSubmitHandler}
        selectedPostType={selectedPostType}
        schedule_time={schedule_time}
      />
    </div>
  );
};

export default forwardRef(Sidebar);
