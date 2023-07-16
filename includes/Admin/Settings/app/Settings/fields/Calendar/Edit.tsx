import React, { useEffect, useState } from "react";
import Modal from "react-modal";
import { Input, Textarea } from "quickbuilder";
import { EventData } from "../../types/Calendar";
import wpFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";

interface EditPostProps {
  post: {
    postId  ?: number;
    postType?: string;
    status  ?: string;
  };
  isOpen    : boolean;
  closeModal: () => void;
}

interface Post {
  ID: number;
  post_title: string;
  post_content: string;
  post_status: string;
  post_type: string;
}

const EditPost: React.FC<EditPostProps> = ({ post, isOpen, closeModal }) => {
  const [title, setTitle]     = useState("");
  const [content, setContent] = useState("");

  const handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) =>
    setTitle(event.target.value);
  const handleContentChange = (event: React.ChangeEvent<HTMLTextAreaElement>) =>
    setContent(event.target.value);
  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
      // handle form submission logic here
    closeModal();
    console.log({ title, content });
  };

  const openModal = (...args) => {
    console.log("args", ...args);
    console.log("post", post);
    if (post?.postId) {
      wpFetch({
        path: addQueryArgs(`/wpscp/v1/quick_edit_get_post`, {
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
          }
        })
        .catch((error) => {
          console.error(error);
        });
    } else {
      setTitle("");
      setContent("");
    }
  };

  useEffect(() => {}, [post]);

  if (isOpen) {
    console.log(post);
  }

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
          <button type="submit">Save</button>
        </form>
      </div>
    </Modal>
  );
};

export default EditPost;
