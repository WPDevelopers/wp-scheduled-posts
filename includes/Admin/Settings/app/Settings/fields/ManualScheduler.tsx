import React from 'react'
import classNames from 'classnames';

const ManualScheduler = (props) => {
    return (
        <div className={classNames('wprf-control', 'wprf-manual-scheduler', `wprf-${props.name}-manual-scheduler`, props?.classes)}>
            <div className="header">
                <div className="title">
                    <h3>Auto Scheduler</h3>
                    <span> To configure the Auto Scheduler Settings, check out this <a href="#">Doc</a></span>
                </div>
                <div className="switcher">
                    <input type="checkbox" name="" id="" />
                </div>
            </div>
            <div className="content">
                <div className="select-days">
                    <select name="" id="">
                        <option value="">Select Days</option>
                    </select>
                </div>
                <div className="select-times">
                    <select name="" id="">
                        <option value="">Select Times</option>
                    </select>
                </div>
                <button>Save Schedule</button>
            </div>
            <div className="weeks">
                <div className="week">
                   <h6>Sun</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
                <div className="week">
                    <h6>Mon</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
                <div className="week">
                    <h6>Tue</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
                <div className="week">
                    <h6>Wed</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
                <div className="week">
                    <h6>Thu</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
                <div className="week">
                    <h6>Fri</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
                <div className="week">
                    <h6>Sat</h6>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                   <span>12:15 AM <button>X</button></span>
                </div>
            </div>
      </div>
    )
}

export default ManualScheduler;