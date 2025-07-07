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
import { MediaUpload } from '@wordpress/media-utils';
import { Button } from '@wordpress/components';

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
  const [scfFields, setScfFields] = useState([]);
  const [scfValues, setScfValues] = useState({});
  const [scfLoading, setScfLoading] = useState(false);
  const [imagePreviews, setImagePreviews] = useState({}); // { [fieldName]: url }
  const [galleryPreviews, setGalleryPreviews] = useState({}); // { [fieldName]: [url, ...] }

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
        scf        : scfValues,
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

  // Refactored function to fetch SCF fields
  const fetchScfFields = async (postType: string, postId?: number, isEdit?: boolean) => {
    setScfLoading(true);
    const url = isEdit && postId
      ? `/wpscp/v1/scf-fields?post_type=${postType}&post_id=${postId}`
      : `/wpscp/v1/scf-fields?post_type=${postType}`;
    try {
      const fields = await wpFetch({ path: url });
      const scfFieldArray = (fields || []) as any[];
      setScfFields(scfFieldArray);
      const values = {};
      scfFieldArray.forEach(field => {
        values[field.name] = field.value ?? '';
      });
      setScfValues(values);
    } catch (e) {
      setScfFields([]);
    } finally {
      setScfLoading(false);
    }
  };

  useEffect(() => {
    if (!postData.post_type) return;
    fetchScfFields(postData.post_type, postData.ID, !!modalData?.post);
  }, [postData.post_type, postData.ID]);

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

  // Helper to fetch image URL from attachment ID if not provided
  const fetchImageUrl = async (id) => {
    if (!id) return '';
    try {
      const data: any = await wpFetch({ path: `/wp/v2/media/${id}` });
      return data?.source_url || '';
    } catch {
      return '';
    }
  };

  // Update image preview when scfValues changes for image fields
  useEffect(() => {
    scfFields.forEach(async (field) => {
      if (field.type === 'image') {
        const id = scfValues[field.name];
        if (id && !imagePreviews[field.name]) {
          let url = field.url;
          if (!url && id) url = await fetchImageUrl(id);
          setImagePreviews((prev) => ({ ...prev, [field.name]: url }));
        } else if (!id && imagePreviews[field.name]) {
          setImagePreviews((prev) => {
            const copy = { ...prev };
            delete copy[field.name];
            return copy;
          });
        }
      }
      if (field.type === 'gallery') {
        const ids = (scfValues[field.name] || '').split(',').map((id) => parseInt(id, 10)).filter(Boolean);
        if (ids.length && (!galleryPreviews[field.name] || galleryPreviews[field.name].length !== ids.length)) {
          Promise.all(ids.map(fetchImageUrl)).then((urls) => {
            setGalleryPreviews((prev) => ({ ...prev, [field.name]: urls }));
          });
        } else if (!ids.length && galleryPreviews[field.name]) {
          setGalleryPreviews((prev) => {
            const copy = { ...prev };
            delete copy[field.name];
            return copy;
          });
        }
      }
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [scfValues, scfFields]);

  const renderSCFField = (field) => {
    const commonProps = {
      key: field.name,
      id: field.name,
      label: field.label,
      value: scfValues[field.name] || '',
      onChange: (e) => {
        const value = e?.target ? e.target.value : e; // for selects
        setScfValues((prev) => ({ ...prev, [field.name]: value }));
      },
      required: field.required,
    };

    switch (field.type) {
      case 'text':
        return <div className="form-group">
            <Input type="text" {...commonProps} />
        </div>
      case 'textarea':
        return <div className="form-group">
            <Textarea type="text" {...commonProps} />
        </div>
      case 'select':
        return (
          <div className="form-group wpsp-scf-select" key={field.name}>
            <label htmlFor={field.name}>{field.label}</label>
            <select
              id={field.name}
              value={field.multiple ? (Array.isArray(scfValues[field.name]) ? scfValues[field.name] : (scfValues[field.name] ? [scfValues[field.name]] : [])) : (scfValues[field.name] || '')}
              onChange={e => {
                if (field.multiple) {
                  const selected = Array.from(e.target.selectedOptions).map(opt => opt.value);
                  setScfValues(prev => ({ ...prev, [field.name]: selected }));
                } else {
                  setScfValues(prev => ({ ...prev, [field.name]: e.target.value }));
                }
              }}
              required={field.required}
              multiple={!!field.multiple}
            >
              {!field.multiple && <option value="">Select...</option>}
              {field.options?.map(opt => (
                <option key={opt} value={opt}>{opt}</option>
              ))}
            </select>
          </div>
        );
      case 'checkbox':
        return (
          <div className="wpsp-scf-checkbox" key={field.name}>
            <label>
              <input
                type="checkbox"
                checked={!!scfValues[field.name]}
                onChange={e => setScfValues((prev) => ({ ...prev, [field.name]: e.target.checked }))}
              />
              {field.label}
            </label>
          </div>
        );
      case 'number':
        return (
          <div className="form-group" key={field.name}>
            <label htmlFor={field.name}>{field.label}</label>
            <input
              type="number"
              id={field.name}
              value={scfValues[field.name] || ''}
              onChange={e => setScfValues(prev => ({ ...prev, [field.name]: e.target.value }))}
              required={field.required}
            />
          </div>
        );
      case 'image': {
        const id = scfValues[field.name];
        const url = imagePreviews[field.name] || field.url;
        return (
          <div className="form-group" key={field.name}>
            <label>{field.label}</label>
            {url && (
              <div style={{ marginBottom: 8 }}>
                <img src={url} alt={field.label} style={{ maxWidth: 120, maxHeight: 120 }} />
              </div>
            )}
            <MediaUpload
              onSelect={media => {
                setScfValues(prev => ({ ...prev, [field.name]: media.id }));
                setImagePreviews(prev => ({ ...prev, [field.name]: media.url }));
              }}
              allowedTypes={['image']}
              value={id}
              render={({ open }) => (
                <div style={{ display: 'flex', gap: 8 }}>
                  <Button onClick={open} isSecondary>
                    {id ? __('Update Image', 'wp-scheduled-posts') : __('Select Image', 'wp-scheduled-posts')}
                  </Button>
                  {id && (
                    <Button
                      isDestructive
                      onClick={() => {
                        setScfValues(prev => ({ ...prev, [field.name]: '' }));
                        setImagePreviews(prev => {
                          const copy = { ...prev };
                          delete copy[field.name];
                          return copy;
                        });
                      }}
                    >
                      {__('Remove', 'wp-scheduled-posts')}
                    </Button>
                  )}
                </div>
              )}
            />
          </div>
        );
      }
      case 'gallery': {
        const rawVal = scfValues[field.name];
        const ids = Array.isArray(rawVal)
          ? rawVal
          : (typeof rawVal === 'string' && rawVal.length
              ? rawVal.split(',').map((id) => parseInt(id, 10)).filter(Boolean)
              : []);
        const urls = galleryPreviews[field.name] || field.urls || [];
        return (
          <div className="form-group" key={field.name}>
            <label>{field.label}</label>
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 8 }}>
              {urls.map((url, i) => (
                <img key={i} src={url} alt={field.label} style={{ maxWidth: 80, maxHeight: 80 }} />
              ))}
            </div>
            <MediaUpload
              onSelect={mediaArr => {
                const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                const ids = arr.map(m => m.id);
                const urls = arr.map(m => m.url);
                setScfValues(prev => ({ ...prev, [field.name]: ids }));
                setGalleryPreviews(prev => ({ ...prev, [field.name]: urls }));
              }}
              allowedTypes={['image']}
              multiple
              gallery
              value={ids}
              render={({ open }) => (
                <div style={{ display: 'flex', gap: 8 }}>
                  <Button onClick={open} isSecondary>
                    {ids.length ? __('Update Images', 'wp-scheduled-posts') : __('Select Images', 'wp-scheduled-posts')}
                  </Button>
                  {ids.length > 0 && (
                    <Button
                      isDestructive
                      onClick={() => {
                        setScfValues(prev => ({ ...prev, [field.name]: [] }));
                        setGalleryPreviews(prev => {
                          const copy = { ...prev };
                          delete copy[field.name];
                          return copy;
                        });
                      }}
                    >
                      {__('Remove', 'wp-scheduled-posts')}
                    </Button>
                  )}
                </div>
              )}
            />
          </div>
        );
      }
      // Add more field types as needed
      default:
        return null;
    }
  };

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
            <div className="modal-fields">
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
              {scfLoading && <div>Loading custom fields...</div>}
              {!scfLoading && scfFields.length > 0 && (
                <div className="scf-fields">
                  {scfFields.map(renderSCFField)}
                </div>
              )}

              <TimePicker
                currentTime={postData.post_date}
                onChange={(date) => setPostData((postData) => ({...postData, post_date: date}))}
                is12Hour
              />
            </div>
            <button type="submit">Save</button>
          </form>
        )}
      </div>
    </Modal>
  );
};
