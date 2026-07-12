
export const fetchSocialProfileData = async (url,queryParams, customQuery = true) => {
    let queryString;
    if( customQuery ) {
        queryString = Object.keys(queryParams)
        .map(key => `${key}=${encodeURIComponent(queryParams[key])}`)
        .join('&');
    }else{
        queryString = queryParams;
    }
    
      return await wp.apiFetch({
        path: `${url}?${queryString}`,
      })
      .then((data) => {
        return data;
      })
      .catch((error) => {
        return error;
      });
};
export const fetchPinterestSection = async (body) => {
  return await wp.apiFetch( {
      path: 'wp-scheduled-posts/v1/fetch_pinterest_section',
      method: 'POST',
      data: body,
  } ).then( ( res ) => {
      return res;
  } );
};


/**
 * Update Pro Settings
 * 
 * @param {number} postId 
 * @param {object} data 
 * @returns {Promise}
 */
export const updateProSettings = ( postId, data ) => {
    return wp.apiFetch({
        path: `/wp-scheduled-posts/v1/update-settings/${postId}`,
        method: 'POST',
        data: {
            ...data,
            post_id: postId,
        }
    });
};