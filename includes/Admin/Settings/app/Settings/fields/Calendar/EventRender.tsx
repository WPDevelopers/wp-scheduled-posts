import { EventApi } from '@fullcalendar/core';
import apiFetch from '@wordpress/api-fetch';
import { decodeEntities } from '@wordpress/html-entities';
import { Button } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import React, { useEffect, useState } from 'react';
import { SweetAlertDeleteMsgForPost } from '../../ToasterMsg';
import { PostCardProps, PostType, WP_Error } from './types';
import { __ } from '@wordpress/i18n';


export const getPostFromEvent = (event: EventApi, dateString = false) => {
  const { title, start, end, allDay } = event;
  const { postId, href, edit, status, postType, postTime } = event.extendedProps;

  const post: PostType = {
    postId: postId,
    postTime: postTime,
    postType: postType,
    status: status,
    title: title,
    href: href,
    edit: edit,
    start: start,
    end: end,
    allDay: allDay,
  };
  return post;
}
export const eventDrop = (event: EventApi, eventType) => {
  const post: PostType = getPostFromEvent(event, true);

  return apiFetch<PostType>({
    method: "POST",
    path: "/wpscp/v1/post",
    data: {
      type: eventType,
      ID: post.postId,
      post_type: post.postType,
      post_status: post.status,
      date: post.end,
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
    setStatus(post.status);

    setTimeout(() => {
      const editArea = document.querySelector(`.wpsp-event-post-${post.postId} .edit-area`);
      const scrollableContent = document.querySelector('.fc-daygrid-day-events'); // Adjust selector if necessary

      if (editArea && scrollableContent) {
        const editAreaRect = editArea.getBoundingClientRect();
        const scrollableContentRect = scrollableContent.getBoundingClientRect();
        console.log('editAreaRect.bottom',editAreaRect);
        console.log('scrollableContentRect.bottom',scrollableContentRect);
        
        // if (editAreaRect.bottom > scrollableContentRect.bottom) {
        //   scrollableContent.scrollTop = editArea.offsetTop;
        // }
      }
    }, 0);
  };

  const deletePost = (id, status) => {
    return apiFetch({
      path: addQueryArgs('/wpscp/v1/post', { ID: id, status }),
      method: 'DELETE',
    }).then((data: { id: string, message: string, status: string } | WP_Error) => {
      if ('id' in data) {
        setEvents((events) => {
          return events.filter((event) => {
            if (data.status == 'Adv. Scheduled') {
              if (event.postId == parseInt(data.id) && event.status == data.status) {
                return false;
              }
              return true;
            }
            return (event.postId !== parseInt(data.id));
          });
        });
      } else {
        // @todo show error message.
      }
    });
  };

  const handlePostDelete = (item) => {
    if (item.status == "Adv. Scheduled") {
      SweetAlertDeleteMsgForPost(
        {
          item,
          text: __('Deleting Advanced Scheduling will result in the loss of any changes made using this feature.', 'wp-scheduled-posts'),
          successTitle: __('Your scheduled data has been deleted!', 'wp-scheduled-posts'),
          buttonText: __('Delete Scheduled Data!', 'wp-scheduled-posts')
        }, deleteFile);
    } else {
      SweetAlertDeleteMsgForPost({ item }, deleteFile);
    }
  }

  const deleteFile = (item) => {
    toggleEditArea();
    return deletePost(item.postId, item.status);
  };

  const addEventListeners = () => {
    document.addEventListener("mousedown", handleClickOutside);
  };

  const removeEventListeners = () => {
    document.removeEventListener("mousedown", handleClickOutside);
  };

  const handleClickOutside = (event) => {
    if (!event.target?.closest(`.wpsp-event-card`)) {
      setEditAreaToggle({
        [post.postId]: false,
      });
      removeEventListeners();
    }
  };

  useEffect(() => {
    if (editAreaToggle?.[post.postId]) {
      addEventListeners();
    }
    else {
      removeEventListeners();
    }

    return () => {
      removeEventListeners();
    };
  }, [editAreaToggle?.[post.postId]]);

  return (
    <div className={`wpsp-event-card wpsp-event-post-${post.postId} card ${postColor}`}>
      {(editAreaToggle?.[post.postId] && post.status == status) && (
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
          {status != 'Adv. Scheduled' &&
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
          }
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
          {post.postTime}
        </span>
        <h3>{decodeEntities(post.title)}</h3>
        <span className="badge-wrapper">
          <span className="Unscheduled-badge">{post.postType}</span>
          <span className="status-badge">{post.status}</span>
        </span>
      </div>
    </div>
  );
};

export default PostCard;
