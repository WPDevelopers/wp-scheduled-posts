
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
  }

  export type ModalProps = {
    post: any;
    eventType: string;
    post_date?: Date;
    openModal?: boolean;
  }


export type SidebarProps = {
    selectedPostType: Option[];
    draftEvents: Array<PostType>;
    setDraftEvents: (posts: Array<PostType>) => void;
  }


  export type SelectWrapperProps = {
    options: Option[];
    value: Option[];
    onChange?: (selectedOption: Option[] | null) => void;
    showTags?: boolean;
    placeholder?: string;
  };