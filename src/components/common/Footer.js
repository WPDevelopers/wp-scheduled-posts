import React from 'react';
const Footer = () => {
    return (
        <div className="wpsp-post-panel-footer">
            <div className="wpsp-modal--footer">
                <button className="btn secondary-btn" id="wpsp-save-settings">Save Settings</button>
                <button className="btn primary-btn">Share Now
                    <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1.5 11L6.5 6L1.5 1" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
            </div>
        </div>
    );
};

export default Footer;
