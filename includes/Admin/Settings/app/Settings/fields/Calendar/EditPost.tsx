import wpFetch from "@wordpress/api-fetch";
import { TimePicker } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";
import { Input, Textarea } from "quickbuilder";
import React, { useEffect, useState } from "react";
import Modal from "react-modal";
import { SweetAlertToaster } from "../../ToasterMsg";
import { getPostType } from "./Helpers";
import { PostType, WP_Error } from "./types";

interface Post {
  ID               ?: number;
  post_content     ?: string;
  post_date        ?: string;
  post_date_gmt    ?: string;
  post_modified    ?: string;
  post_modified_gmt?: string;
  post_status      ?: string;
  post_title       ?: string;
  post_type        ?: string;
}


export const ModalContent = ({
  modalData,
  setModalData,
  onSubmit,
  selectedPostType,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [postData, setPostData] = useState<Post>({
    post_title: '',
    post_content: '',
    post_date: '',
  });

  // console.log(getSettings());

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    // let offset = parseFloat(getSettings().timezone.offset) + new Date().getTimezoneOffset() / 60;
    // let postDate = date("Y-m-d\\TH:i:s\\Z", postData.post_date, -offset);

    return wpFetch({
      method: "POST",
      path: "/wpscp/v1/post",
      data: {
        type       : modalData?.eventType,
        ID         : postData.ID,
        post_type  : postData.post_type,
        // post_status: postData.post_status,
        postTitle  : postData.post_title,
        postContent: postData.post_content,
        date       : postData.post_date,
      },
    }).then((data: PostType | WP_Error) => {
      onSubmit(data, modalData?.post);
      // @todo show success message
    }).finally(() => {
      closeModal();
      let message;
      switch ( modalData?.eventType ) {
        case 'addEvent':
          message = __('New Post has been successfully Created','wp-scheduled-posts');
          break;
        case 'editEvent':
          message = __('Post has been successfully Edited','wp-scheduled-posts');
          break;
        case 'newDraft':
          message = __('New Draft Post has been successfully Created','wp-scheduled-posts');
          break;
        case 'editDraft':
          message = __('Draft Post has been successfully Edited','wp-scheduled-posts');
          break;
        default:
          break;
      }
      SweetAlertToaster({ title : message }).fire();
    });
  };

  useEffect(() => {
    if (modalData?.post) {
      setIsOpen(true);
      const post = modalData.post;
      setIsLoading(true);
      wpFetch({
        path: addQueryArgs(`/wpscp/v1/post`, {
          postId     : post.postId,
          postType   : post.postType,
          post_status: post.status,
        }),
      })
      .then((posts: Array<Post>) => {
        if (posts.length > 0) {
          const post = posts[0];
          setPostData(post);
        }
        setIsLoading(false);
      })
      .catch((error) => {
        setPostData({});
        setIsLoading(false);
        // @todo show error message
      });
    } else if(modalData) {
      let data: Post = {
        post_type: getPostType(selectedPostType),
      };
      if(modalData?.post_date){
        data.post_date = modalData?.post_date;
      }
      if(modalData?.post_date || modalData?.openModal){
        setIsOpen(true);
      }
      setPostData(data);
    }
  }, [modalData]);

  const closeModal = () => {
    setModalData(false);
    setPostData({});
    setIsOpen(false);
  };

  useEffect(() => {
    return () => {
      closeModal();
    };
  }, []);


  return (
    <Modal
      isOpen={isOpen}
      onRequestClose={closeModal}
      ariaHideApp={false}
      className="modal_wrapper"
    >
      <div className="modalhead">
        <button className="close-button" onClick={closeModal}>
          <i className="wpsp-icon wpsp-close"></i>
        </button>
        <div className="platform-info">
          <h4>Add New {postData.post_type}</h4>
        </div>
      </div>
      <div className="modalbody">
        {isLoading && <div>Loading...</div>}
        {!isLoading && (
          <form onSubmit={(event) => {
            handleSubmit(event).then();
          }}>
            <div className="form-group">
              <Input
                type="text"
                id="title"
                label="Title"
                placeholder="Title"
                value={postData.post_title}
                onChange={(event) => setPostData((postData) => ({...postData, post_title: event.target.value}))}
              />
            </div>
            <div className="form-group">
              <Textarea
                id="content"
                label="Content"
                placeholder="Content"
                value={postData.post_content}
                onChange={(event) => setPostData((postData) => ({...postData, post_content: event.target.value}))}
              />
            </div>

            <TimePicker
              currentTime={postData.post_date}
              onChange={(date) => setPostData((postData) => ({...postData, post_date: date}))}
              is12Hour
            />

            <button type="submit">Save</button>
          </form>
        )}
      </div>
    </Modal>
  );
};
