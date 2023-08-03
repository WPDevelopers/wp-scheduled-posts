import { Option } from "./types";
import { parse, format } from 'date-fns'

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

// "2023-08-10 11:41:00"
// create function that can perse this date format and create utc date object
export const getUTCDate = (date: string) => {
  const dateObject = new Date(date);
  const utcDate = new Date(dateObject.getTime() + dateObject.getTimezoneOffset() * 60000);
  return utcDate;
}

// use Year month day form startDate and hour minute second from endDate
export const getEndDate = (startDate: Date, endDate: string) => {
  // "2023-08-09 06:03:00"
  const date = parse(endDate, 'yyyy-MM-dd HH:mm:ss', startDate);

  const end = format(startDate, 'yyyy-MM-dd') + ' ' + format(date, 'HH:mm:ss');

  return end;
}
