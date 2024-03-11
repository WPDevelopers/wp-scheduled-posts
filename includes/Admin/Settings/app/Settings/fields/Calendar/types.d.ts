import FullCalendar from "@fullcalendar/react";
// create type for rest response of WP_Error from php
export type WP_Error = {
    code: string;
    message: string;
    data?: any;
  };

export type Option = {
    value: string;
    label: string;
    options?: Option[];
  };



export type PostType = {
    postId  : number;
    title   : string;
    href    : string;
    edit    : string;
    postType: string;
    status  : string;
    postTime: string;
    start   : string | Date;
    end     : string | Date;
    allDay  : boolean;
  }

  export type PostCardProps = {
    post: PostType;
    editAreaToggle: { [key: number]: boolean };
    setEditAreaToggle: React.Dispatch<
      React.SetStateAction<{ [key: number]: boolean }>
    >;
    openModal: (modalData: { post: any; eventType: string }) => void;
    setEvents: React.Dispatch<React.SetStateAction<PostType[]>>;
    getPostTypeColor: (postType: string) => string;
    status: string;
    setStatus: React.Dispatch<
      React.SetStateAction<string>
    >;
  }

  export type ModalProps = {
    post: any;
    eventType: string;
    post_date?: Date | string;
    openModal?: boolean;
  }


export type SidebarProps = {
    selectedPostType: Option[];
    draftEvents: Array<PostType>;
    calendar: React.MutableRefObject<FullCalendar>;
    setDraftEvents: (posts: Array<PostType>) => void;
    getPostTypeColor: (postType: string) => string;
    postType: string;
    schedule_time: string;
  }


  export type SelectWrapperProps = {
    isDisabled?: boolean;
    options: Option[];
    value: Option[];
    onChange?: (selectedOption: Option[] | null) => void;
    showTags?: boolean;
    placeholder?: string;
  };