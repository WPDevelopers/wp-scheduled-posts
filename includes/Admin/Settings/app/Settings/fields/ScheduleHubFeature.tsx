import classNames from 'classnames';
import React from 'react';

const ScheduleHubFeature = (props) => {
//   const { heading, button_text, button_link, options } = props?.content;
// @ts-ignore 
let is_pro = wpspSettingsGlobal?.pro_version ? true : false;
  return (
<<<<<<< Updated upstream
    <div
      className={classNames(
        'wprf-control',
        'wprf-schedule-hub-features',
        `wprf-${props.name}-schedule-hub-features`,
        props?.classes
      )}>
    { !is_pro && (
        <>
            <div className="wprf-heading">
                <h2>Upgrade to use these Pro Features</h2>
                <a href="#">Upgrade To Pro</a>
            </div>
            <div className="wprf-card-wrapper">
                <div className="single-card">
                    <i className="wpsp-icon wpsp-advance-pro"></i>
                    <h4>Manage Schedule</h4>
                </div>
                <div className="single-card">
                    <i className="wpsp-icon wpsp-manage-pro"></i>
                    <h4>Advanced Schedule</h4>
                </div>
                <div className="single-card">
                    <i className="wpsp-icon wpsp-missed-pro"></i>
                    <h4>Missed Schedule</h4>
                </div>
            </div>
        </>
    ) }
    </div>
=======
      <>
        { !is_pro && (
            <div
            className={classNames(
              'wprf-control',
              'wprf-scheudle-hub-features',
              `wprf-${props.name}-schedule-hub-features`,
              props?.classes
            )}>
              <div className="wprf-heading">
                  <h2>Upgrade to use these Pro Features</h2>
                  <a href="#">Upgrade To Pro</a>
              </div>
              <div className="wprf-card-wrapper">
                  <div className="single-card">
                      <i>Icon</i>
                      <h4>Manage Schedule</h4>
                  </div>
                  <div className="single-card">
                      <i>Icon</i>
                      <h4>Advanced Schedule</h4>
                  </div>
                  <div className="single-card">
                      <i>Icon</i>
                      <h4>Manage Schedule</h4>
                  </div>
              </div>
          </div>
        ) }
      </>
>>>>>>> Stashed changes
  );
};

export default ScheduleHubFeature;
