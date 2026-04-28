import React from 'react';
import Settings from '../Content/Settings';
import SocialShare from '../Content/SocialShare';
const Content = () => {
    return (
        <div className="wpsp-post-panel-modal-content-wrap">
            <div className="wpsp-post-panel-modal-content">
                <Settings />
                <SocialShare />
            </div>
        </div>
    );
};

export default Content;
