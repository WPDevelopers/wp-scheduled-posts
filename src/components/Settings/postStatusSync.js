const assertStatus = (status) => {
    if (typeof status !== 'string' || !status.trim()) {
        throw new Error('Cannot synchronize an empty post status.');
    }
};

/**
 * Update the state sources used outside Gutenberg.
 *
 * The localized objects are the source used by the shared React panel in the
 * Classic Editor and page builders. The DOM/model updates also prevent those
 * editors from submitting the pre-request status on their next save.
 */
export const syncLocalizedPostStatus = (
    status,
    globalObject = typeof window !== 'undefined' ? window : undefined,
    documentObject = typeof document !== 'undefined' ? document : undefined
) => {
    assertStatus(status);

    if (!globalObject && !documentObject) return false;

    let synchronized = false;
    ['WPSchedulePostsFree', 'WPSchedulePosts'].forEach((key) => {
        const localizedState = globalObject?.[key];
        if (localizedState && typeof localizedState === 'object') {
            localizedState.current_post_status = status;
            synchronized = true;
        }
    });

    const classicStatus = documentObject?.getElementById?.('post_status');
    const classicHiddenStatus = documentObject?.getElementById?.('hidden_post_status');
    [classicStatus, classicHiddenStatus].forEach((field) => {
        if (!field) return;
        field.value = status;
        synchronized = true;
    });

    const classicStatusDisplay = documentObject?.getElementById?.('post-status-display');
    const selectedStatusOption = classicStatus?.options
        ? Array.from(classicStatus.options).find((option) => option.value === status)
        : null;
    if (classicStatusDisplay && selectedStatusOption) {
        classicStatusDisplay.textContent = selectedStatusOption.text;
    }

    const elementorPageModel = globalObject?.elementor?.settings?.page?.model;
    if (typeof elementorPageModel?.set === 'function') {
        elementorPageModel.set('post_status', status);
        synchronized = true;
    }

    return synchronized;
};

/**
 * Replace Gutenberg's persisted entity record after an out-of-band REST
 * mutation. This avoids creating a dirty edit with core/editor.editPost().
 */
export const syncGutenbergPostStatus = (
    status,
    dataRegistry = typeof wp !== 'undefined' ? wp.data : undefined
) => {
    assertStatus(status);

    if (!dataRegistry?.select || !dataRegistry?.dispatch) return false;

    let editor;
    try {
        editor = dataRegistry.select('core/editor');
    } catch (error) {
        // The Classic Editor and some builders load wp.data without registering
        // core/editor. That is an expected non-Gutenberg path.
        if (/store.*core\/editor.*(?:not found|not registered)/i.test(error?.message || '')) return false;
        throw error;
    }
    const postType = editor?.getCurrentPostType?.();
    const postId = editor?.getCurrentPostId?.();
    if (!postType || !postId) return false;

    const record = dataRegistry
        .select('core')
        ?.getEntityRecord?.('postType', postType, postId);
    const receiveEntityRecords = dataRegistry
        .dispatch('core')
        ?.receiveEntityRecords;

    if (!record || typeof receiveEntityRecords !== 'function') {
        throw new Error('The refreshed post could not be written to the Gutenberg data store.');
    }

    receiveEntityRecords(
        'postType',
        postType,
        { ...record, status },
        undefined,
        false
    );

    const synchronizedStatus = editor.getEditedPostAttribute?.('status');
    if (synchronizedStatus && synchronizedStatus !== status) {
        throw new Error('Gutenberg retained a stale post status after the schedule was restored.');
    }

    return true;
};

export const syncCurrentPostStatus = (status, options = {}) => {
    const result = {
        localized: syncLocalizedPostStatus(
            status,
            options.globalObject,
            options.documentObject
        ),
        gutenberg: syncGutenbergPostStatus(status, options.dataRegistry),
    };

    if (!result.localized && !result.gutenberg) {
        throw new Error('No active editor state accepted the refreshed post status.');
    }

    return result;
};
