import React, { useState } from "react";
import Modal from "react-modal";

interface AddNewPostModalProps {
  post: any;
  isOpen: boolean;
  onClose: () => void;
}

const AddNewPostModal: React.FC<AddNewPostModalProps> = ({
  post,
  isOpen,
  onClose,
}) => {
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");

  const handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) =>
    setTitle(event.target.value);
  const handleContentChange = (event: React.ChangeEvent<HTMLTextAreaElement>) =>
    setContent(event.target.value);
  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    // handle form submission logic here
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onRequestClose={onClose}
      ariaHideApp={false}
      className="modal_wrapper"
    >
      <div className="modalhead">
        <button className="close-button" onClick={onClose}>
          <i className="wpsp-icon wpsp-close"></i>
        </button>
        <div className="platform-info">
          <h4>Add New Post</h4>
        </div>
      </div>
      <div className="modalbody">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="title">Title:</label>
            <input
              type="text"
              id="title"
              value={title}
              onChange={handleTitleChange}
            />
          </div>
          <div className="form-group">
            <label htmlFor="content">Content:</label>
            <textarea
              id="content"
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

export default AddNewPostModal;
