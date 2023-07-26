import classNames from 'classnames';
import React from 'react';

const ScheduleHubFeature = (props) => {
//   const { heading, button_text, button_link, options } = props?.content;
  return (
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
  );
};

export default ScheduleHubFeature;
