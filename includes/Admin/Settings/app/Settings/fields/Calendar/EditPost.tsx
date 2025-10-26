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
  const [isSubmitting, setIsSubmitting] = useState(false);
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
    setIsSubmitting(true);
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
      setIsSubmitting(false);
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
        const rawValue = scfValues[field.name];
        const ids = Array.isArray(rawValue)
          ? rawValue.map((id: any) => parseInt(id, 10)).filter(Boolean)
          : (typeof rawValue === 'string' && rawValue.length)
            ? rawValue.split(',').map((id: string) => parseInt(id, 10)).filter(Boolean)
            : [];
        const currentPreviews = galleryPreviews[field.name] || [];

        // Only fetch URLs if we don't have previews or if the count doesn't match
        if (ids.length && (!currentPreviews.length || currentPreviews.length !== ids.length)) {
          // Check if we need to fetch any URLs (only fetch for IDs that don't have URLs)
          const needsFetch = ids.some((_: number, index: number) => !currentPreviews[index]);

          if (needsFetch) {
            Promise.all(ids.map(fetchImageUrl)).then((urls) => {
              // Only update if the URLs are different to prevent unnecessary re-renders
              setGalleryPreviews((prev) => {
                const current = prev[field.name] || [];
                const urlsChanged = urls.some((url: string, index: number) => url !== current[index]);
                if (urlsChanged) {
                  return { ...prev, [field.name]: urls };
                }
                return prev;
              });
            });
          }
        } else if (!ids.length && currentPreviews.length) {
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

  const renderSCFField = (field: any) => {

    switch (field.type) {
      case 'text':
        return (
          <div className="wpsp-field-group wpsp-text-field" key={field.name}>
            <label className="wpsp-field-label" htmlFor={field.name}>
              {field.label}
              {field.required && <span className="wpsp-required">*</span>}
            </label>
            <div className="wpsp-field-wrapper">
              <input
                type="text"
                id={field.name}
                className="wpsp-text-input"
                value={scfValues[field.name] || ''}
                onChange={(e) => setScfValues((prev) => ({ ...prev, [field.name]: e.target.value }))}
                placeholder={field.placeholder || `Enter ${field.label.toLowerCase()}`}
                required={field.required}
              />
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
          </div>
        );
      case 'textarea':
        return (
          <div className="wpsp-field-group wpsp-textarea-field" key={field.name}>
            <label className="wpsp-field-label" htmlFor={field.name}>
              {field.label}
              {field.required && <span className="wpsp-required">*</span>}
            </label>
            <div className="wpsp-field-wrapper">
              <textarea
                id={field.name}
                className="wpsp-textarea-input"
                value={scfValues[field.name] || ''}
                onChange={(e) => setScfValues((prev) => ({ ...prev, [field.name]: e.target.value }))}
                placeholder={field.placeholder || `Enter ${field.label.toLowerCase()}`}
                required={field.required}
                rows={field.rows || 4}
              />
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
          </div>
        );
      case 'select':
        return (
          <div className="wpsp-field-group wpsp-select-field" key={field.name}>
            <label className="wpsp-field-label" htmlFor={field.name}>
              {field.label}
              {field.required && <span className="wpsp-required">*</span>}
            </label>
            <div className="wpsp-field-wrapper">
              <div className="wpsp-select-wrapper">
                <select
                  id={field.name}
                  className="wpsp-select-input"
                  value={field.multiple ? (Array.isArray(scfValues[field.name]) ? scfValues[field.name] : (scfValues[field.name] ? [scfValues[field.name]] : [])) : (scfValues[field.name] || '')}
                  onChange={e => {
                    if (field.multiple) {
                      const selected = Array.from(e.target.selectedOptions).map((opt: HTMLOptionElement) => opt.value);
                      setScfValues(prev => ({ ...prev, [field.name]: selected }));
                    } else {
                      setScfValues(prev => ({ ...prev, [field.name]: e.target.value }));
                    }
                  }}
                  required={field.required}
                  multiple={!!field.multiple}
                >
                  {!field.multiple && <option value="">{`Select ${field.label.toLowerCase()}...`}</option>}
                  {field.options?.map((opt: string) => (
                    <option key={opt} value={opt}>{opt}</option>
                  ))}
                </select>
                <div className="wpsp-select-arrow">
                  <svg width="12" height="8" viewBox="0 0 12 8" fill="none">
                    <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
              </div>
              {field.multiple && (
                <div className="wpsp-field-hint">Hold Ctrl/Cmd to select multiple options</div>
              )}
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
          </div>
        );
      case 'checkbox':
        return (
          <div className="wpsp-field-group wpsp-checkbox-field" key={field.name}>
            <div className="wpsp-checkbox-wrapper">
              <label className="wpsp-checkbox-label" htmlFor={field.name}>
                <input
                  type="checkbox"
                  id={field.name}
                  className="wpsp-checkbox-input"
                  checked={!!scfValues[field.name]}
                  onChange={e => setScfValues((prev) => ({ ...prev, [field.name]: e.target.checked }))}
                />
                <span className="wpsp-checkbox-custom">
                  <svg className="wpsp-checkbox-icon" width="12" height="9" viewBox="0 0 12 9" fill="none">
                    <path d="M1 4.5L4.5 8L11 1.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </span>
                <span className="wpsp-checkbox-text">
                  {field.label}
                  {field.required && <span className="wpsp-required">*</span>}
                </span>
              </label>
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
          </div>
        );
      case 'number':
        return (
          <div className="wpsp-field-group wpsp-number-field" key={field.name}>
            <label className="wpsp-field-label" htmlFor={field.name}>
              {field.label}
              {field.required && <span className="wpsp-required">*</span>}
            </label>
            <div className="wpsp-field-wrapper">
              <input
                type="number"
                id={field.name}
                className="wpsp-number-input"
                value={scfValues[field.name] || ''}
                onChange={e => setScfValues(prev => ({ ...prev, [field.name]: e.target.value }))}
                placeholder={field.placeholder || `Enter ${field.label.toLowerCase()}`}
                min={field.min}
                max={field.max}
                step={field.step || 1}
                required={field.required}
              />
              {(field.min !== undefined || field.max !== undefined) && (
                <div className="wpsp-field-hint">
                  {field.min !== undefined && field.max !== undefined
                    ? `Range: ${field.min} - ${field.max}`
                    : field.min !== undefined
                    ? `Minimum: ${field.min}`
                    : `Maximum: ${field.max}`
                  }
                </div>
              )}
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
          </div>
        );
      case 'image': {
        const id = scfValues[field.name];
        const url = imagePreviews[field.name] || field.url;
        return (
          <div className="wpsp-field-group wpsp-image-field" key={field.name}>
            <label className="wpsp-field-label">
              {field.label}
              {field.required && <span className="wpsp-required">*</span>}
            </label>
            <div className="wpsp-field-wrapper">
              {url ? (
                <div className="wpsp-image-preview-container">
                  <div className="wpsp-image-preview">
                    <img
                      src={url}
                      alt={field.label}
                      className="wpsp-image-preview-img"
                    />
                    <button
                      type="button"
                      className="wpsp-image-remove-btn"
                      onClick={() => {
                        setScfValues(prev => ({ ...prev, [field.name]: '' }));
                        setImagePreviews(prev => {
                          const copy = { ...prev };
                          delete copy[field.name];
                          return copy;
                        });
                      }}
                      title={__('Remove Image', 'wp-scheduled-posts')}
                    >
                      ×
                    </button>
                  </div>
                  <div className="wpsp-image-actions">
                    <MediaUpload
                      onSelect={(media: any) => {
                        setScfValues(prev => ({ ...prev, [field.name]: media.id }));
                        setImagePreviews(prev => ({ ...prev, [field.name]: media.url }));
                      }}
                      allowedTypes={['image']}
                      value={id}
                      render={({ open }) => (
                        <button type="button" className="wpsp-btn wpsp-btn-secondary" onClick={open}>
                          {__('Change Image', 'wp-scheduled-posts')}
                        </button>
                      )}
                    />
                  </div>
                </div>
              ) : (
                <div className="wpsp-image-upload-area">
                  <MediaUpload
                    onSelect={(media: any) => {
                      setScfValues(prev => ({ ...prev, [field.name]: media.id }));
                      setImagePreviews(prev => ({ ...prev, [field.name]: media.url }));
                    }}
                    allowedTypes={['image']}
                    value={id}
                    render={({ open }) => (
                      <div className="wpsp-upload-placeholder" onClick={open}>
                        <div className="wpsp-upload-icon">
                          <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M7 10L12 5L17 10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M12 5V15" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                          </svg>
                        </div>
                        <div className="wpsp-upload-text">
                          <div className="wpsp-upload-primary">{__('Click to upload an image', 'wp-scheduled-posts')}</div>
                          <div className="wpsp-upload-secondary">{__('or drag and drop', 'wp-scheduled-posts')}</div>
                        </div>
                      </div>
                    )}
                  />
                </div>
              )}
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
          </div>
        );
      }
      case 'gallery': {
        const rawVal = scfValues[field.name];
        const ids = Array.isArray(rawVal)
          ? rawVal
          : (typeof rawVal === 'string' && rawVal.length
              ? rawVal.split(',').map((id: string) => parseInt(id, 10)).filter(Boolean)
              : []);
        const urls = galleryPreviews[field.name] || field.urls || [];

        const removeImage = (indexToRemove: number) => {
          const newIds = ids.filter((_: any, index: number) => index !== indexToRemove);
          const newUrls = urls.filter((_: any, index: number) => index !== indexToRemove);

          // Convert back to string format for consistency with SCF
          const idsValue = newIds.length > 0 ? newIds.join(',') : '';
          setScfValues(prev => ({ ...prev, [field.name]: idsValue }));

          if (newUrls.length > 0) {
            setGalleryPreviews(prev => ({ ...prev, [field.name]: newUrls }));
          } else {
            setGalleryPreviews(prev => {
              const copy = { ...prev };
              delete copy[field.name];
              return copy;
            });
          }
        };

        const clearAllImages = () => {
          setScfValues(prev => ({ ...prev, [field.name]: '' }));
          setGalleryPreviews(prev => {
            const copy = { ...prev };
            delete copy[field.name];
            return copy;
          });
        };

        return (
          <div className="wpsp-field-group wpsp-gallery-field" key={field.name}>
            <label className="wpsp-field-label">
              {field.label}
              {field.required && <span className="wpsp-required">*</span>}
            </label>
            <div className="wpsp-field-wrapper">
              {urls.length > 0 ? (
                <div className="wpsp-gallery-preview-container">
                  <div className="wpsp-gallery-grid">
                    {urls.map((url: string, i: number) => (
                      <div key={i} className="wpsp-gallery-item">
                        <img
                          src={url}
                          alt={`${field.label} ${i + 1}`}
                          className="wpsp-gallery-image"
                        />
                        <button
                          type="button"
                          className="wpsp-gallery-remove-btn"
                          onClick={() => removeImage(i)}
                          title={__('Remove Image', 'wp-scheduled-posts')}
                        >
                          ×
                        </button>
                      </div>
                    ))}
                  </div>
                  <div className="wpsp-gallery-actions">
                    <MediaUpload
                      onSelect={(mediaArr: any) => {
                        const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                        const newIds = arr.map((m: any) => m.id);
                        const newUrls = arr.map((m: any) => m.url);

                        // Convert to string format for consistency with SCF
                        const idsValue = newIds.join(',');

                        // Update both states together to prevent race conditions
                        setScfValues(prev => ({ ...prev, [field.name]: idsValue }));
                        setGalleryPreviews(prev => ({ ...prev, [field.name]: newUrls }));
                      }}
                      allowedTypes={['image']}
                      multiple
                      gallery
                      value={ids}
                      render={({ open }) => (
                        <button type="button" className="wpsp-btn wpsp-btn-secondary" onClick={open}>
                          {__('Replace All', 'wp-scheduled-posts')}
                        </button>
                      )}
                    />
                    <MediaUpload
                      onSelect={(mediaArr: any) => {
                        const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                        const newIds = arr.map((m: any) => m.id);
                        const newUrls = arr.map((m: any) => m.url);

                        // Combine with existing IDs, filtering out duplicates
                        const currentIds = ids; // Use the current ids from the component state
                        const uniqueNewIds = newIds.filter((id: number) => !currentIds.includes(id));

                        if (uniqueNewIds.length === 0) {
                          // No new unique images selected
                          return;
                        }

                        const combinedIds = [...currentIds, ...uniqueNewIds];
                        const uniqueNewUrls = newUrls.filter((_: string, index: number) => uniqueNewIds.includes(newIds[index]));
                        const combinedUrls = [...urls, ...uniqueNewUrls];

                        // Update both states with the combined data
                        const idsValue = combinedIds.join(',');
                        setScfValues(prev => ({ ...prev, [field.name]: idsValue }));
                        setGalleryPreviews(prev => ({ ...prev, [field.name]: combinedUrls }));
                      }}
                      allowedTypes={['image']}
                      multiple
                      gallery
                      value={[]}
                      render={({ open }) => (
                        <button type="button" className="wpsp-btn wpsp-btn-secondary" onClick={open}>
                          {__('Add More', 'wp-scheduled-posts')}
                        </button>
                      )}
                    />
                    <button
                      type="button"
                      className="wpsp-btn wpsp-btn-destructive"
                      onClick={clearAllImages}
                    >
                      {__('Clear All', 'wp-scheduled-posts')}
                    </button>
                  </div>
                </div>
              ) : (
                <div className="wpsp-gallery-upload-area">
                  <MediaUpload
                    onSelect={(mediaArr: any) => {
                      const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                      const newIds = arr.map((m: any) => m.id);
                      const newUrls = arr.map((m: any) => m.url);

                      // Convert to string format for consistency with SCF
                      const idsValue = newIds.join(',');

                      // Set both states together
                      setScfValues(prev => ({ ...prev, [field.name]: idsValue }));
                      setGalleryPreviews(prev => ({ ...prev, [field.name]: newUrls }));
                    }}
                    allowedTypes={['image']}
                    multiple
                    gallery
                    value={ids}
                    render={({ open }) => (
                      <div className="wpsp-upload-placeholder" onClick={open}>
                        <div className="wpsp-upload-icon">
                          <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                            <path d="M14.2 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V7.8L14.2 2Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M14 2V8H20" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M16 13H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M16 17H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            <path d="M10 9H9H8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                          </svg>
                        </div>
                        <div className="wpsp-upload-text">
                          <div className="wpsp-upload-primary">{__('Click to select images', 'wp-scheduled-posts')}</div>
                          <div className="wpsp-upload-secondary">{__('You can select multiple images at once', 'wp-scheduled-posts')}</div>
                        </div>
                      </div>
                    )}
                  />
                </div>
              )}
              {field.description && (
                <div className="wpsp-field-description">{field.description}</div>
              )}
            </div>
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
        {isLoading && (
          <div className="wpsp-modal-loading-container">
            <div className="wpsp-modal-loading-content">
              <div className="wpsp-modal-loading-spinner">
                <div className="wpsp-spinner-circle"></div>
              </div>
              <div className="wpsp-modal-loading-text">
                {__('Loading post data...', 'wp-scheduled-posts')}
              </div>
              <div className="wpsp-modal-loading-subtext">
                {__('Please wait while we fetch the post information', 'wp-scheduled-posts')}
              </div>
            </div>
          </div>
        )}
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
                  onChange={(event: React.ChangeEvent<HTMLInputElement>) => setPostData((postData) => ({...postData, post_title: event.target.value}))}
                />
              </div>
              <div className="form-group">
                <Textarea
                  id="content"
                  label="Content"
                  placeholder="Content"
                  required
                  value={postData.post_content}
                  onChange={(event: React.ChangeEvent<HTMLTextAreaElement>) => setPostData((postData) => ({...postData, post_content: event.target.value}))}
                />
              </div>
              {scfLoading && (
                <div className="wpsp-scf-loading-container">
                  <div className="wpsp-scf-loading-content">
                    <div className="wpsp-scf-loading-spinner">
                      <div className="wpsp-spinner-ring"></div>
                      <div className="wpsp-spinner-ring"></div>
                      <div className="wpsp-spinner-ring"></div>
                    </div>
                    <div className="wpsp-scf-loading-text">
                      {__('Loading custom fields...', 'wp-scheduled-posts')}
                    </div>
                    <div className="wpsp-scf-loading-subtext">
                      {__('Please wait while we fetch your custom field configuration', 'wp-scheduled-posts')}
                    </div>
                  </div>

                  {/* Skeleton Loading for Fields */}
                  <div className="wpsp-scf-skeleton-fields">
                    {[1, 2, 3].map((index) => (
                      <div key={index} className="wpsp-skeleton-field">
                        <div className="wpsp-skeleton-label"></div>
                        <div className="wpsp-skeleton-input"></div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              {!scfLoading && scfFields.length > 0 && (
                <div className="scf-fields">
                  {scfFields.map(renderSCFField)}
                </div>
              )}

              <TimePicker
                currentTime={postData.post_date}
                onChange={(date: string) => setPostData((postData) => ({...postData, post_date: date}))}
                is12Hour
              />
            </div>
            <div className="wpsp-modal-footer">
              <button
                type="submit"
                className={`wpsp-submit-button ${isSubmitting ? 'wpsp-submitting' : ''}`}
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <>
                    <span className="wpsp-submit-spinner"></span>
                    {__('Saving...', 'wp-scheduled-posts')}
                  </>
                ) : (
                  __('Save', 'wp-scheduled-posts')
                )}
              </button>
            </div>
          </form>
        )}
      </div>
    </Modal>
  );
};
