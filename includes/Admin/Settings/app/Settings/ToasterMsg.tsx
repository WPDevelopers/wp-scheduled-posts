import React, { useState } from 'react'
import { toast } from 'react-toastify';
import { __ } from '@wordpress/i18n';
import 'react-toastify/dist/ReactToastify.css';
import Swal from 'sweetalert2';

// Setup Sweetalert2 toaster
export const SweetAlertToaster = (args: any = {}) => {
    return Swal.mixin({
        icon: args?.icon ?? (args?.type || "success"),
        title: args?.title ?? __('Changes saved successfully','wp-scheduled-posts'),
        toast: args?.toast ?? true,
        position: args?.position ?? 'bottom-end',
        showConfirmButton: args?.showConfirmButton ?? false,
        timer: args?.timer ?? 3000,
        timerProgressBar: args?.toast ?? true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })
};

// Setup Sweetalert2 pro message popup
export const SweetAlertProMsg = (args: any = {}) => {
    return Swal.fire({
        title: args?.title ?? __('Opps','wp-scheduled-posts'),
        showCancelButton: args?.showCancelButton ?? false,
        showConfirmButton: args?.showConfirmButton ?? false,
        html: `
          <div>
            <h4>${ args?.message ?? __('You need SchedulePress PRO','wp-scheduled-posts') }</h4>
            <img src="${ args?.imageUrl ?? wpspSettingsGlobal.admin_image_path + '/upgrade-pro-new.png' }" alt="${__(args?.imageAlt ?? __('Pro Alert'), 'wp-scheduled-posts')}">
            <a href="${ args?.buttonUrl ?? 'https://wpdeveloper.com/in/schedulepress-pro' }" target="${ args?.target ?? '_blank' }">
              <button>${ args?.buttonText ?? __('Check Pricing Plans', 'wp-scheduled-posts') }</button>
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
        icon: args?.icon ?? __( 'warning','wp-scheduled-posts' ),
        showCancelButton: args?.showCancelButton ?? true,
        confirmButtonColor: args?.confirmButtonColor ?? '#3085d6',
        cancelButtonColor: args?.cancelButtonColor ?? '#d33',
        confirmButtonText: args?.confirmButtonText ?? __('Yes, delete it!', 'wp-scheduled-posts'),
    }).then((result) => {
        if (result.isConfirmed) {
            deleteFile(args?.item)
        }
    })
};