import { __ } from '@wordpress/i18n';
import React from 'react';


const Header = ({image_path}) => {
    // @ts-ignore 
   const free_version = wpspSettingsGlobal?.free_version;
    // @ts-ignore 
   const pro_version = wpspSettingsGlobal?.pro_version;

    return (
        <div className="wpsp-admin-header">
            {/* @ts-ignore */}
            <img src={`${image_path}mainLogo.png`} alt="mainLogo" />
            <div className="wpsp-admin-version">
                { free_version &&  <p><span className="wpsp-version-text">{ __('Core Version:','wp-scheduled-posts') }</span> <span className="wpsp-version-version">{ free_version  }</span></p> }
                { pro_version && <p><span className="wpsp-version-text">{ __('Pro Version:','wp-scheduled-posts') }</span> <span className="wpsp-version-version">{ pro_version }</span></p> }
            </div>
        </div>
    )
}

export default Header;