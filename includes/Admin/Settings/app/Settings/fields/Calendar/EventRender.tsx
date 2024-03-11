import { EventApi } from '@fullcalendar/core';
import apiFetch from '@wordpress/api-fetch';
import { decodeEntities } from '@wordpress/html-entities';
import { Button } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import React, { useEffect, useState } from 'react';
import { SweetAlertDeleteMsgForPost } from '../../ToasterMsg';
import { PostCardProps, PostType, WP_Error } from './types';


export const getPostFromEvent = (event: EventApi, dateString = false) => {
  const { title, start, end, allDay } = event;
  const { postId, href, edit, status, postType, postTime } = event.extendedProps;

  const post: PostType = {
    postId  : postId,
    postTime: postTime,
    postType: postType,
    status  : status,
    title   : title,
    href    : href,
    edit    : edit,
    start   : start,
    end     : end,
    allDay  : allDay,
  };
  return post;
}
export const eventDrop = (event: EventApi, eventType) => {
  const post: PostType = getPostFromEvent(event, true);

  // let date = post.end.toISOString().replace('T', ' ').replace(/\.\d{3}Z/, '');
  // let date = DateTime.fromISO(post.end).toFormat('yyyy-MM-dd HH:mm:ss');

  return apiFetch<PostType>({
    method: "POST",
    path: "/wpscp/v1/post",
    data: {
      type       : eventType,
      ID         : post.postId,
      post_type  : post.postType,
      post_status: post.status,
      // postContent: post.post_content,
      // postTitle  : post.title,
      date       : post.end,
    },
  });
}

const PostCard = ({
  post,
  editAreaToggle,
  setEditAreaToggle,
  openModal,
  setEvents,
  getPostTypeColor,
  status = null,
  setStatus = null,
}) => {
  const postColor = getPostTypeColor(post.postType);
  
  const toggleEditArea = () => {
    setEditAreaToggle({
      [post.postId]: !editAreaToggle?.[post.postId] ?? true,
    });
    setStatus( post.status );
  };  

  const deletePost = (id) => {
    // @todo add confirm dialog.

    return apiFetch({
      path: addQueryArgs('/wpscp/v1/post', { ID: id }),
      method: 'DELETE',
      // data: query,
    }).then((data: {id: string, message: string} | WP_Error) => {
      if('id' in data) {
        setEvents((events) => {
          return events.filter((event) => {
            return event.postId !== parseInt(data.id);
          });
        });
        // @todo show success message.

      } else {
        // @todo show error message.
      }
    });
  };
  const handlePostDelete = (item) => {
    SweetAlertDeleteMsgForPost( { item }, deleteFile );
  }

  const deleteFile = (item) => {
    toggleEditArea();
    return deletePost(item.postId);
  };

  const addEventListeners = () => {
    document.addEventListener("mousedown", handleClickOutside);
  };
  const removeEventListeners = () => {
    document.removeEventListener("mousedown", handleClickOutside);
  };

  const handleClickOutside = (event) => {
    // check if event.target is descendant of the {id} class
    if (!event.target?.closest(`.wpsp-event-card`)) {
      setEditAreaToggle({
        [post.postId]: false,
      });
      removeEventListeners();
    }
  };

  useEffect(() => {
    if(editAreaToggle?.[post.postId]) {
      addEventListeners();
    }
    else{
      removeEventListeners();
    }

    return () => {
      removeEventListeners();
    };
  }, [editAreaToggle?.[post.postId]]);
  
  return (
    <div className={`wpsp-event-card card ${postColor}`} >
      {( editAreaToggle?.[post.postId] && post.status == status ) && (
        <ul className="edit-area">
          <li>
            <Button
              variant="secondary"
              onClick={(event) => {
                event.preventDefault();
                toggleEditArea();
                window.open(decodeURIComponent(post.href), '_blank');
              }}
            >
              View
            </Button>
          </li>
          <li>
            <Button
              variant="secondary"
              onClick={(event) => {
                event.preventDefault();
                toggleEditArea();
                window.open(decodeURIComponent(post.edit), '_blank');
              }}
            >
              Edit
            </Button>
          </li>
          <li>
            <Button
              variant="secondary"
              onClick={(event) => {
                event.preventDefault();
                toggleEditArea();
                openModal({ post, eventType: "editEvent" });
              }}
            >
              Quick Edit
            </Button>
          </li>
          <li>
            <Button
              variant="secondary"
              onClick={(event) => {
                event.preventDefault();
                handlePostDelete(post);
              }}
            >
              Delete
            </Button>
          </li>
        </ul>
      )}
      <div className="wpsp-event-card-content">
        <i className="wpsp-icon wpsp-dots" onClick={toggleEditArea}></i>
        <span className={`set-time ` + ('Published' === post.status ? 'published' : 'scheduled')}>
          {/* "1:00 am" */}
          {/* @ts-ignore */}
          {/* {format(post.end, 'h:mm a')} */}
          {post.postTime}
        </span>
        <h3>{ decodeEntities(  post.title ) }</h3>
        <span className="badge-wrapper">
          <span className="Unscheduled-badge">{post.postType}</span>
          <span className="status-badge">{post.status}</span>
        </span>
      </div>
    </div>
  );
};

export default PostCard;
