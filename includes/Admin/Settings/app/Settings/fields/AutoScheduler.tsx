import React from 'react'
import classNames from 'classnames';

const AutoScheduler = (props) => {
    return (
        <div className={classNames('wprf-control', 'wprf-auto-scheduler', `wprf-${props.name}-auto-scheduler`, props?.classes)}>
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
                <div className="start-time set-timing">
                    <div className="time-title">
                        <h4>Start Time</h4>
                        <span>Default : 12:30 AM</span>
                    </div>
                    <div className="time">
                        <select name="" id="">
                            <option value=""> 12:30 AM</option>
                            <option value=""> 12:45 AM</option>
                        </select>
                    </div>
                </div>
                <div className="end-time set-timing">
                    <div className="time-title">
                        <h4>End Time</h4>
                        <span>Default : 04:00 PM</span>
                    </div>
                    <div className="time">
                        <select name="" id="">
                            <option value=""> 04:00 PM</option>
                            <option value=""> 04:00 PM</option>
                        </select>
                    </div>
                </div>
            </div>
            <div className="weeks">
                <div className="week">
                    <input type="number" value={0} />
                    <span>Number of posts</span>
                    <h6>Sunday</h6>
                </div>
                <div className="week">
                    <input type="number" value={6} />
                    <span>Number of posts</span>
                    <h6>Monday</h6>
                </div>
                <div className="week">
                    <input type="number" value={14} />
                    <span>Number of posts</span>
                    <h6>Tuesday</h6>
                </div>
                <div className="week">
                    <input type="number" value={0} />
                    <span>Number of posts</span>
                    <h6>Wednesday</h6>
                </div>
                <div className="week">
                    <input type="number" value={14} />
                    <span>Number of posts</span>
                    <h6>Thursday</h6>
                </div>
                <div className="week">
                    <input type="number" value={7} />
                    <span>Number of posts</span>
                    <h6>Friday</h6>
                </div>
                <div className="week">
                    <input type="number" value={0} />
                    <span>Number of posts</span>
                    <h6>Saturday</h6>
                </div>
            </div>
      </div>
    )
}

export default AutoScheduler;