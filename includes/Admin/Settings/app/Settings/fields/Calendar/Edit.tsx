import React, { useEffect, useState } from "react";
import Modal from "react-modal";
import { Input, Textarea } from "quickbuilder";
import { EventData } from "../../types/Calendar";
import wpFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import {TimePicker} from '@wordpress/components';

interface EditPostProps {
  post: {
    postId  ?: number;
    postType?: string;
    status  ?: string;
  };
  isOpen    : boolean;
  closeModal: () => void;
}

type Post = {
  ID                   : number;
  comment_count        : string;
  comment_status       : string;
  filter               : string;
  guid                 : string;
  menu_order           : number;
  ping_status          : string;
  pinged               : string;
  post_author          : string;
  post_content         : string;
  post_content_filtered: string;
  post_date            : string;
  post_date_gmt        : string;
  post_excerpt         : string;
  post_mime_type       : string;
  post_modified        : string;
  post_modified_gmt    : string;
  post_name            : string;
  post_parent          : number;
  post_password        : string;
  post_status          : string;
  post_title           : string;
  post_type            : string;
  to_ping              : string;
}

const EditPost: React.FC<EditPostProps> = ({ post, isOpen, closeModal }) => {
  const [title, setTitle]     = useState("");
  const [content, setContent] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [postData, setPostData] = useState<Post>();

  const handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) =>
    setTitle(event.target.value);
  const handleContentChange = (event: React.ChangeEvent<HTMLTextAreaElement>) =>
    setContent(event.target.value);
  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    // handle form submission logic here
    wpFetch({
      method: "POST",
      path: '/wpscp/v1/post',
      data: {
        type       : 'addEvent',
        ID         : postData.ID,
        post_type  : postData.post_type,
        post_status: postData.post_status,
        postTitle  : title,
        postContent: content,
        date       : postData.post_date,
        // time       : null,
      },
    });
    closeModal();
    console.log(postData);
  };

  const openModal = (...args) => {
    console.log("args", ...args);
    console.log("post", post);
    if (post?.postId) {
      setIsLoading(true);
      wpFetch({
        path: addQueryArgs(`/wpscp/v1/post`, {
          postId     : post.postId,
          postType   : post.postType,
          post_status: post.status,
        }),
          // data: query,
      })
        .then((posts: Array<Post>) => {
          if (posts.length > 0) {
            const post = posts[0];
            setTitle(post.post_title);
            setContent(post.post_content);
            setPostData(post);
            console.log(post);

          }
          setIsLoading(false);
        })
        .catch((error) => {
          console.error(error);
          setIsLoading(false);
        });
    } else {
      setTitle("");
      setContent("");
    }
  };

  useEffect(() => {}, [post]);

  return (
    <Modal
      isOpen={isOpen}
      onRequestClose={closeModal}
      onAfterOpen={openModal}
      ariaHideApp={false}
      className="modal_wrapper"
    >
      <div className="modalhead">
        <button className="close-button" onClick={closeModal}>
          <i className="wpsp-icon wpsp-close"></i>
        </button>
        <div className="platform-info">
          <h4>Add New Post</h4>
        </div>
      </div>
      <div className="modalbody">
        {isLoading && <div>Loading...</div>}
        {!isLoading && (
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <Input
              type="text"
              id="title"
              label="Title"
              placeholder="Title"
              value={title}
              onChange={handleTitleChange}
            />
          </div>
          <div className="form-group">
            <Textarea
              id="content"
              label="Content"
              placeholder="Content"
              value={content}
              onChange={handleContentChange}
            />
          </div>

          <TimePicker
            currentTime={postData?.post_date}
            onChange={ ( newTime ) => setPostData({...postData, post_date: newTime })}
            is12Hour
          />

          <button type="submit">Save</button>
        </form>
        )}
      </div>
    </Modal>
  );
};

export default EditPost;
