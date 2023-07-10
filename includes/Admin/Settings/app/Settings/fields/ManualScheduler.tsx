import React from 'react'
import classNames from 'classnames';

const ManualScheduler = (props) => {
    return (
        <div className={classNames('wprf-control', 'wprf-manual-scheduler', `wprf-${props.name}-manual-scheduler`, props?.classes)}>
            <div className="header">
                <div className="title">
                    <h3>Manual Scheduler</h3>
                    <span> To configure the Auto Scheduler Settings, check out this <a href="#">Doc</a></span>
                </div>
                <div className="switcher">
                    <input type="checkbox" name="" id="" />
                </div>
            </div>
            <div className="content">
                <select name="days" id="days" className="select-days select-items">
                    <option value="">Select Days</option>
                    <option value="">Friday</option>
                    <option value="">Saturday</option>
                    <option value="">Sunday</option>
                </select>
                <select name="time" id="time" className="select-times select-items">
                    <option value="">Select Times</option>
                    <option value="">12:15 AM</option>
                    <option value="">12:15 AM</option>
                    <option value="">12:15 AM</option>
                </select>
                <button>Save Schedule</button>
            </div>
            <div className="weeks">
                <div className="week">
                   <h6>Sun</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Mon</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Tue</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Wed</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Thu</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Fri</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Sat</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
            </div>
      </div>
    )
}

export default ManualScheduler;