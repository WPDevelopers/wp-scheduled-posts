const { useSelect } = wp.data;

const useCurrentPostData = ({
    postId: propPostId,
    postStatus: propPostStatus,
    postTitleProp,
    postContentProp,
    postUrlProp,
}) => useSelect((select) => {
    const editor = select('core/editor');
    const core = select('core');

    // Gutenberg Path
    let id = propPostId || (editor ? editor.getCurrentPostId() : null);
    let status = propPostStatus || (editor ? editor.getEditedPostAttribute('status') : null);
    let title = postTitleProp || (editor ? editor.getEditedPostAttribute('title') : null);
    let content = postContentProp || (editor ? editor.getEditedPostAttribute('content') : null);
    let url = postUrlProp || (editor ? editor.getPermalink() : null);

    let mediaUrl = null;
    if (editor && core) {
        const featuredMediaId = editor.getEditedPostAttribute('featured_media');
        const media = featuredMediaId ? core.getMedia(featuredMediaId) : null;
        mediaUrl = media?.source_url || null;
    }

    // Fallback Path (Classic Editor / Page Builders)
    if (!id && typeof window.WPSchedulePostsFree !== 'undefined') {
        id = window.WPSchedulePostsFree.current_post_id;

        // Try to get live data from DOM/TinyMCE for Classic Editor
        const titleEl = document.getElementById('title');
        const contentEl = document.getElementById('content');
        const excerptEl = document.getElementById('excerpt');

        // Title
        if (titleEl && titleEl.value) {
            title = titleEl.value;
        } else {
            title = window.WPSchedulePostsFree.current_post_title;
        }

        // Content
        if (typeof window.tinymce !== 'undefined' && window.tinymce.get('content') && !window.tinymce.get('content').isHidden()) {
            content = window.tinymce.get('content').getContent();
        } else if (contentEl && contentEl.value) {
            content = contentEl.value;
        } else {
            content = window.WPSchedulePostsFree.current_post_content;
        }

        // Elementor Support
        if (typeof window.elementor !== 'undefined' && window.elementor.settings && window.elementor.settings.page) {
            const pageModel = window.elementor.settings.page.model;
            if (pageModel.get('post_title')) {
                title = pageModel.get('post_title');
            }
            const featImg = pageModel.get('featured_image');
            if (featImg && featImg.url) {
                mediaUrl = featImg.url;
            }
            // Note: Fetching live content from Elementor is complex as it's structured data.
            // We'll fallback to saved content or excerpt if needed, but title/image are live.
        }

        status = window.WPSchedulePostsFree.current_post_status;
        url = window.WPSchedulePostsFree.current_post_url;
        mediaUrl = window.WPSchedulePostsFree.current_post_featured_image;
    }

    return {
        postId: id,
        postStatus: status,
        postTitle: title,
        postContent: content,
        postUrl: url,
        featuredImageUrl: mediaUrl,
    };
}, [propPostId, propPostStatus, postTitleProp, postContentProp, postUrlProp]);

export default useCurrentPostData;
