import { Option } from "./types";

export const getValues = (options: Option[]) => {
  const values    = options ?? [];
  const allOption = values.find((option) => option.value === "all");
  if(allOption) {
    return [];
  }
  else{
    return values.map(option => option.value)
  }
};

export const getPostType = (selectedPostType?: Option[]) => {
  const postTypes = getValues(selectedPostType);
  if (postTypes.length && postTypes[0] !== "all") {
    return postTypes[0];
  }
  else{
    // add new code here
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if(page && page.startsWith('schedulepress-')){
      let post_type = page.replace('schedulepress-', '');
      if(post_type === 'calendar'){
        post_type = 'post';
      }
      return post_type;
    }
  }
  return "post";
}