import React, { useCallback, useState } from "react";
import { EventContentArg, sliceEvents, createPlugin } from "@fullcalendar/core";
import { Button } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import useEditPost from "./EditPost";

// a custom render function

const renderEventContent = (
  editAreaToggle,
  setEditAreaToggle,
  handleOpenModal
) => {
  return (eventInfo: EventContentArg) => {
    const { title, start, end, allDay } = eventInfo.event;
    const { postId, href, edit, status, postType, postTime } =
      eventInfo.event.extendedProps;
    return (
      <div className="wpscp-event-post">
        <div className="postlink">
          <span>
            <span className="posttime">[{postTime}]</span> {title} [{status}]
          </span>
        </div>

        <div className="postactions">
          <div>
            <i
              className="wpsp-icon wpsp-dots event-rendered-edit-icon"
              onClick={() => {
                setEditAreaToggle(() => {
                  let checkExistingIndex = editAreaToggle.findIndex(
                    (item) => item.post === postId
                  );
                  if (checkExistingIndex !== -1) {
                    return [
                      {
                        post: postId,
                        value: editAreaToggle[checkExistingIndex].value
                          ? false
                          : true,
                      },
                    ];
                  } else {
                    return [
                      {
                        post: postId,
                        value: true,
                      },
                    ];
                  }
                });
              }}
            ></i>
            {editAreaToggle.find((item) => item.post === postId)?.value && (
              <ul className="edit-area">
                <li>
                  <Button
                    variant="link"
                    target="_blank"
                    href={decodeURIComponent(href)}
                  >
                    View
                  </Button>
                </li>
                <li>
                  <Button
                    variant="link"
                    target="_blank"
                    href={decodeURIComponent(edit)}
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
                      handleOpenModal(eventInfo.event.extendedProps);
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
                    }}
                  >
                    Delete
                  </Button>
                </li>
              </ul>
            )}
          </div>
        </div>
      </div>
    );
  };
};

// export default renderEventContent;

const deletePost = (id) => {
  apiFetch({
    path: addQueryArgs("/wpscp/v1/post", { ID: id }),
    method: "DELETE",
    // data: query,
  }).then((data: []) => {
    // Set your posts state with the fetched data
    console.log(data);
  });
};

export interface PostCardProps {
  post: {
    postId: number;
    postTime: string;
    postType: string;
    status: string;
    title: string;
    href: string;
    edit: string;
  };
  editAreaToggle: { [key: number]: boolean };
  setEditAreaToggle: React.Dispatch<
    React.SetStateAction<{ [key: number]: boolean }>
  >;
  openModal: (post: any, eventType: string) => void;
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

  return (
    <div className="wpsp-event-card card">
      <i className="wpsp-icon wpsp-dots" onClick={toggleEditArea}></i>
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
                openModal(post, "addEvent");
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
                toggleEditArea();
                deletePost(post.postId);
              }}
            >
              Delete
            </Button>
          </li>
        </ul>
      )}
      <span className="set-time">{post.postTime}</span>
      <h3>{post.title}</h3>
      <span className="Unscheduled-badge">{post.postType}</span>
    </div>
  );
};

export default PostCard;
