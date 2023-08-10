import { __ } from '@wordpress/i18n';
import 'react-toastify/dist/ReactToastify.css';
import Swal from 'sweetalert2';

// Setup Sweetalert2 toaster
export const SweetAlertToaster = (args: any = {}) => {
  // @ts-ignore 
  const image_path = wpspSettingsGlobal.image_path + '/toaster-icon/';
    let toastIcon = '';
    if( args?.icon ?? (args?.type || "success") == "success" ) {
      toastIcon = image_path + 'Connected.gif';
    }else if( args?.type == 'error' ) {
      toastIcon = image_path + 'Error.gif';
    }
    return Swal.mixin({
        icon: args?.icon ?? (args?.type || "success"),
        title: args?.title ?? __('Changes Saved Successfully','wp-scheduled-posts'),
        toast: args?.toast ?? true,
        position: args?.position ?? 'top-end',
        showConfirmButton: args?.showConfirmButton ?? false,
        timer: args?.timer ?? 3000,
        timerProgressBar: args?.toast ?? true,
        showClass: {
          popup: 'animate__animated animate__fadeOutUp'
        },
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        },
        iconHtml: `<img src="${toastIcon}" class="wpsp-toaster-icon">`,
        customClass: {
          container     : 'wpsp-toast-container',
          popup         : 'wpsp-toast-popup',
          title         : 'wpsp-toast-title'
        }
    })
};

// Setup Sweetalert2 pro message popup
export const SweetAlertProMsg = (args: any = {}) => {
    // @ts-ignore
    const image_path = wpspSettingsGlobal.image_path;
    return Swal.fire({
        title: args?.title ?? __('Opps!','wp-scheduled-posts'),
        showCancelButton: args?.showCancelButton ?? true,
        cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
        showConfirmButton: args?.showConfirmButton ?? false,
        allowOutsideClick: false, // Prevent closing on outside click
        html: `
          <div>
            <h4>${ args?.message ?? __('You Need SchedulePress PRO','wp-scheduled-posts') }</h4>
            <img src="${ args?.imageUrl ?? image_path + '/upgrade-pro.gif' }" alt="${__(args?.imageAlt ?? __('Pro Alert'), 'wp-scheduled-posts')}">
            <a href="${ args?.buttonUrl ?? 'https://schedulepress.com/#pricing' }" target="${ args?.target ?? '_blank' }">
              ${ args?.buttonText ?? __('Check Pricing Plans', 'wp-scheduled-posts') }
            </a>
          </div>
        `
    });
};

// Setup Sweetalert2 pro message popup
export const SweetAlertDeleteMsg = ( args: any = {}, deleteFile?: (item) => void ) => {
    return Swal.fire({
        title: args?.title ?? __( 'Are you sure?','wp-scheduled-posts' ),
        text: args?.text ?? __( "You won't be able to revert this!",'wp-scheduled-posts' ),
        icon: args?.icon ?? __( 'error','wp-scheduled-posts' ),
        allowOutsideClick: false, // Prevent closing on outside click
        showCancelButton: args?.showCancelButton ?? true,
        confirmButtonColor: args?.confirmButtonColor ?? '#3085d6',
        cancelButtonColor: args?.cancelButtonColor ?? '#d33',
        cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
        confirmButtonText: args?.confirmButtonText ?? __('Yes, delete it!', 'wp-scheduled-posts'),
    }).then((result) => {
        if (result.isConfirmed) {
            deleteFile(args?.item)
        }
    })
};

// Setup Sweetalert2 pro message popup
export const SweetAlertDeleteMsgForPost = ( args: any = {}, deleteFile?: (item) => void ) => {
  return Swal.fire({
      title: args?.title ?? __( 'Are you sure?','wp-scheduled-posts' ),
      text: args?.text ?? __( "You won't be able to revert this!",'wp-scheduled-posts' ),
      icon: args?.icon ?? __( 'error','wp-scheduled-posts' ),
      allowOutsideClick: false, // Prevent closing on outside click
      showCancelButton: args?.showCancelButton ?? true,
      confirmButtonColor: args?.confirmButtonColor ?? '#3085d6',
      cancelButtonColor: args?.cancelButtonColor ?? '#d33',
      cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
      confirmButtonText: args?.confirmButtonText ?? __('Yes, Delete it!', 'wp-scheduled-posts'),
  }).then((result) => {
    if (result.isConfirmed) {
      deleteFile(args?.item)
      Swal.fire(
        __('Deleted!','wp-scheduled-posts'),
        __('Your post has been deleted.','wp-scheduled-posts'),
        'success'
      )
    }
  })
};
// Show poup for auto & manual scheduler status change
export const SweetAlertStatusChangingMsg = ( args: any = {}, handleStatusChange?: (status,values,manualSchedulerStatusIndex) => void ) => {
    return Swal.fire({
        title: args?.title ?? __( 'Are you sure?','wp-scheduled-posts' ),
        text: args?.text ?? __( "You won't be able to revert this!",'wp-scheduled-posts' ),
        icon: args?.icon ?? __( 'warning','wp-scheduled-posts' ),
        allowOutsideClick: false, // Prevent closing on outside click
        showCancelButton: args?.showCancelButton ?? true,
        confirmButtonColor: args?.confirmButtonColor ?? '#3085d6',
        cancelButtonColor: args?.cancelButtonColor ?? '#d33',
        cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
        confirmButtonText: args?.confirmButtonText ?? __('Yes, Save it!', 'wp-scheduled-posts'),
    }).then((result) => {
        if (result.isConfirmed) {
            handleStatusChange(args?.status , args?.values,args?.manualSchedulerStatusIndex);
        }
    })
};