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
            {url ? (
              <div style={{ marginBottom: 12, position: 'relative', display: 'inline-block' }}>
                <img
                  src={url}
                  alt={field.label}
                  style={{
                    maxWidth: 120,
                    maxHeight: 120,
                    borderRadius: 4,
                    border: '1px solid #ddd'
                  }}
                />
                <Button
                  isDestructive
                  isSmall
                  onClick={() => {
                    setScfValues(prev => ({ ...prev, [field.name]: '' }));
                    setImagePreviews(prev => {
                      const copy = { ...prev };
                      delete copy[field.name];
                      return copy;
                    });
                  }}
                  style={{
                    position: 'absolute',
                    top: -8,
                    right: -8,
                    minWidth: 24,
                    height: 24,
                    borderRadius: '50%',
                    padding: 0,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                  title={__('Remove Image', 'wp-scheduled-posts')}
                >
                  ×
                </Button>
              </div>
            ) : (
              <div
                style={{
                  border: '2px dashed #ddd',
                  borderRadius: 4,
                  padding: 20,
                  textAlign: 'center',
                  marginBottom: 12,
                  cursor: 'pointer',
                  transition: 'border-color 0.2s'
                }}
                onMouseEnter={(e) => (e.target as HTMLElement).style.borderColor = '#007cba'}
                onMouseLeave={(e) => (e.target as HTMLElement).style.borderColor = '#ddd'}
              >
                <MediaUpload
                  onSelect={media => {
                    setScfValues(prev => ({ ...prev, [field.name]: media.id }));
                    setImagePreviews(prev => ({ ...prev, [field.name]: media.url }));
                  }}
                  allowedTypes={['image']}
                  value={id}
                  render={({ open }) => (
                    <div onClick={open}>
                      <div style={{ fontSize: 24, marginBottom: 8, color: '#666' }}>📷</div>
                      <div style={{ color: '#666', fontSize: 14 }}>
                        {__('Click to select an image', 'wp-scheduled-posts')}
                      </div>
                    </div>
                  )}
                />
              </div>
            )}
            {url && (
              <MediaUpload
                onSelect={media => {
                  setScfValues(prev => ({ ...prev, [field.name]: media.id }));
                  setImagePreviews(prev => ({ ...prev, [field.name]: media.url }));
                }}
                allowedTypes={['image']}
                value={id}
                render={({ open }) => (
                  <Button onClick={open} isSecondary isSmall>
                    {__('Change Image', 'wp-scheduled-posts')}
                  </Button>
                )}
              />
            )}
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
          <div className="form-group" key={field.name}>
            <label>{field.label}</label>

            {urls.length > 0 ? (
              <div style={{ marginBottom: 12 }}>
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fill, minmax(80px, 1fr))',
                  gap: 8,
                  marginBottom: 12
                }}>
                  {urls.map((url: string, i: number) => (
                    <div key={i} style={{ position: 'relative', display: 'inline-block' }}>
                      <img
                        src={url}
                        alt={`${field.label} ${i + 1}`}
                        style={{
                          width: 80,
                          height: 80,
                          objectFit: 'cover',
                          borderRadius: 4,
                          border: '1px solid #ddd'
                        }}
                      />
                      <Button
                        isDestructive
                        isSmall
                        onClick={() => removeImage(i)}
                        style={{
                          position: 'absolute',
                          top: -8,
                          right: -8,
                          minWidth: 20,
                          height: 20,
                          borderRadius: '50%',
                          padding: 0,
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontSize: 12
                        }}
                        title={__('Remove Image', 'wp-scheduled-posts')}
                      >
                        ×
                      </Button>
                    </div>
                  ))}
                </div>
                <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                  <MediaUpload
                    onSelect={(mediaArr: any) => {
                      const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                      const newIds = arr.map((m: any) => m.id);
                      const newUrls = arr.map((m: any) => m.url);

                      // Convert to string format for consistency with SCF
                      const idsValue = newIds.join(',');
                      setScfValues(prev => ({ ...prev, [field.name]: idsValue }));
                      setGalleryPreviews(prev => ({ ...prev, [field.name]: newUrls }));
                    }}
                    allowedTypes={['image']}
                    multiple
                    gallery
                    value={ids}
                    render={({ open }) => (
                      <Button onClick={open} isSecondary isSmall>
                        {__('Replace All Images', 'wp-scheduled-posts')}
                      </Button>
                    )}
                  />
                  <MediaUpload
                    onSelect={(mediaArr: any) => {
                      const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                      const newIds = arr.map((m: any) => m.id);
                      const newUrls = arr.map((m: any) => m.url);

                      // Append to existing images
                      const combinedIds = [...ids, ...newIds];
                      const combinedUrls = [...urls, ...newUrls];

                      // Convert to string format for consistency with SCF
                      const idsValue = combinedIds.join(',');
                      setScfValues(prev => ({ ...prev, [field.name]: idsValue }));
                      setGalleryPreviews(prev => ({ ...prev, [field.name]: combinedUrls }));
                    }}
                    allowedTypes={['image']}
                    multiple
                    gallery
                    value={[]}
                    render={({ open }) => (
                      <Button onClick={open} isSecondary isSmall>
                        {__('Add More Images', 'wp-scheduled-posts')}
                      </Button>
                    )}
                  />
                  <Button
                    isDestructive
                    isSmall
                    onClick={clearAllImages}
                  >
                    {__('Clear All', 'wp-scheduled-posts')}
                  </Button>
                </div>
              </div>
            ) : (
              <div
                style={{
                  border: '2px dashed #ddd',
                  borderRadius: 4,
                  padding: 30,
                  textAlign: 'center',
                  marginBottom: 12,
                  cursor: 'pointer',
                  transition: 'border-color 0.2s'
                }}
                onMouseEnter={(e) => (e.target as HTMLElement).style.borderColor = '#007cba'}
                onMouseLeave={(e) => (e.target as HTMLElement).style.borderColor = '#ddd'}
              >
                <MediaUpload
                  onSelect={(mediaArr: any) => {
                    const arr = Array.isArray(mediaArr) ? mediaArr : [mediaArr];
                    const newIds = arr.map((m: any) => m.id);
                    const newUrls = arr.map((m: any) => m.url);

                    // Convert to string format for consistency with SCF
                    const idsValue = newIds.join(',');
                    setScfValues(prev => ({ ...prev, [field.name]: idsValue }));
                    setGalleryPreviews(prev => ({ ...prev, [field.name]: newUrls }));
                  }}
                  allowedTypes={['image']}
                  multiple
                  gallery
                  value={ids}
                  render={({ open }) => (
                    <div onClick={open}>
                      <div style={{ fontSize: 32, marginBottom: 12, color: '#666' }}>🖼️</div>
                      <div style={{ color: '#666', fontSize: 16, marginBottom: 4 }}>
                        {__('Click to select images', 'wp-scheduled-posts')}
                      </div>
                      <div style={{ color: '#999', fontSize: 12 }}>
                        {__('You can select multiple images at once', 'wp-scheduled-posts')}
                      </div>
                    </div>
                  )}
                />
              </div>
            )}
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
              {scfLoading && <div>Loading custom fields...</div>}
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
            <button type="submit">Save</button>
          </form>
        )}
      </div>
    </Modal>
  );
};
