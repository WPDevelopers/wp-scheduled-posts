import { EventApi } from '@fullcalendar/core';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import React from 'react';
import { SweetAlertDeleteMsgForPost } from '../../ToasterMsg';
import { PostCardProps, PostType } from './types';


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

export const deletePost = (id) => {
  return apiFetch({
    path: addQueryArgs('/wpscp/v1/post', { ID: id }),
    method: 'DELETE',
    // data: query,
  }).then((data: []) => {
    // Set your posts state with the fetched data
    console.log(data);
  });
};

export const eventDrop = (event: EventApi, eventType) => {
  const post: PostType = getPostFromEvent(event, true);

  // let date = post.end.toISOString().replace('T', ' ').replace(/\.\d{3}Z/, '');
  // let date = DateTime.fromISO(post.end).toFormat('yyyy-MM-dd HH:mm:ss');
  console.log(post.end);

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

const PostCard: React.FC<PostCardProps> = ({
  post,
  editAreaToggle,
  setEditAreaToggle,
  openModal,
}) => {
  const toggleEditArea = () => {
    setEditAreaToggle({
      [post.postId]: !editAreaToggle?.[post.postId] ?? true,
    });
  };

  const handlePostDelete = (item) => {
    alert('Hello World')
    SweetAlertDeleteMsgForPost( { item }, deleteFile );
  }
  
  const deleteFile = (item) => {
    toggleEditArea();
    deletePost(item.postId);
  };

  return (
    <div className="wpsp-event-card card">
      {editAreaToggle?.[post.postId] && (
        <ul className="edit-area">
          <li>
            <Button
              variant="link"
              target="_blank"
              href={decodeURIComponent(post.href)}
              onClick={(event) => {
                toggleEditArea();
              }}
            >
              View
            </Button>
          </li>
          <li>
            <Button
              variant="link"
              target="_blank"
              href={decodeURIComponent(post.edit)}
              onClick={(event) => {
                toggleEditArea();
              }}
            >
              Edit
            </Button>
          </li>
          <li>
            <Button
              variant="link"
              href="#"
              onClick={(event) => {
                event.preventDefault();
                toggleEditArea();
                openModal({ post, eventType: "addEvent" });
              }}
            >
              Quick Edit
            </Button>
          </li>
          <li>
            <Button
              variant="link"
              href="#"
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
      <i className="wpsp-icon wpsp-dots" onClick={toggleEditArea}></i>
      <span className={`set-time ` + ('Published' === post.status ? 'published' : 'scheduled')}>
        {/* "1:00 am" */}
        {/* @ts-ignore */}
        {/* {format(post.end, 'h:mm a')} */}
        {post.postTime}
      </span>
      <h3>{post.title}</h3>
      <span className="badge-wrapper">
        <span className="Unscheduled-badge">{post.postType}</span>
        <span className="status-badge">{post.status}</span>
      </span>
    </div>
  );
};

export default PostCard;
