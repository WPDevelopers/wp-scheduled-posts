
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