import React from 'react';
const Settings = () => {
    return (
        <div className="wpsp-post-panel-modal-settings">
            <h2>Scheduling Settings</h2>
            <div className="wpsp-post-panel-modal-settings-schedule">
                <h3>Scheduling Options</h3>
                <div className="wpsp-post-panel-modal-settings-schedule-unpublish-republish">
                    <div className="wpsp-post-panel-modal-settings-schedule-unpublish">
                        <label htmlFor="">
                            <span>Unpublish On</span>
                            <input type="date" />
                        </label>
                    </div>
                    <div className="wpsp-post-panel-modal-settings-schedule-republish">
                        <label htmlFor="">
                            <span>Republish On</span>
                            <input type="date" />
                        </label>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Settings;
