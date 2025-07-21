import wpFetch from "@wordpress/api-fetch";
import { TimePicker } from "@wordpress/components";
import { date, format } from "@wordpress/date";
import { __ } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";
import { useBuilderContext } from "quickbuilder";
import Input from "quickbuilder/dist/fields/Input";
import Textarea from "quickbuilder/dist/fields/Textarea";
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
  schedule_time,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [postData, setPostData] = useState<Post>({
    post_title: '',
    post_content: '',
    post_date: '',
  });
  const builderContext = useBuilderContext();

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
      // console.log(data);
      onSubmit(data, modalData?.post);
      // @todo show success message
    }).then(() => {
      let message;
      switch ( modalData?.eventType ) {
        case 'addEvent':
          message = __('New Post has been Successfully Created','wp-scheduled-posts');
          break;
        case 'editEvent':
          message = __('Post has been Successfully Edited','wp-scheduled-posts');
          break;
        case 'newDraft':
          message = __('New Draft Post has been Successfully Created','wp-scheduled-posts');
          break;
        case 'editDraft':
          message = __('Draft Post has been Successfully Edited','wp-scheduled-posts');
          break;
        default:
          break;
      }
      SweetAlertToaster({ title : message }).fire();
    }).catch((error) => {
      // @todo show error message
      let message = error?.message || __('Something went wrong','wp-scheduled-posts');
      SweetAlertToaster({ type: 'error', title : message }).fire();
    }).finally(() => {
      closeModal();
    });
  };

  useEffect(() => {
    // edit existing post.
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
    } else if(modalData?.post_date || modalData?.openModal) {
      // add new post.
      let data: Post = {
        post_type: getPostType(selectedPostType),
      };
      const time = builderContext?.values?.calendar_schedule_time || schedule_time || '12:00:00';
      if (modalData?.post_date) {
        data.post_date = format('Y-m-d', modalData.post_date) + ' ' + time;
      } else {
        data.post_date = date('Y-m-d', undefined, undefined) + ' ' + time;
      }
      setIsOpen(true);
      setPostData(data);
    }
    else{
      setPostData({});
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
          { (modalData?.eventType == 'editEvent' || modalData?.eventType == 'editDraft') ? <h4>Edit {postData.post_type}</h4> : <h4>Add New {postData.post_type}</h4> }
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
                required
                onChange={(event) => setPostData((postData) => ({...postData, post_title: event.target.value}))}
              />
            </div>
            <div className="form-group">
              <Textarea
                id="content"
                label="Content"
                placeholder="Content"
                required
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
