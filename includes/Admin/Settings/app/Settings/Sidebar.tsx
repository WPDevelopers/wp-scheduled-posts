import React from 'react';
const docIcon =  require("../assets/images/doc.png");
const upgradePro =  require("../assets/images/upgrade-pro.png");

const Sidebar = () => {
    return (
        <div className="nx-admin-sidebar">
            <div className='upgrade-pro'>
                <img className='icon-wrapper' src={upgradePro} alt='icon-1' />
                <h3>Documentation</h3>
                <p>Get started spending some time with the documentation to get familiar with SchedulePress.</p>
                <button>Documentation</button>
            </div>
            <div className='card'>
                <img className='icon-wrapper' src={docIcon} alt='icon-1' />
                <h3>Documentation</h3>
                <p>Get started spending some time with the documentation to get familiar with SchedulePress.</p>
                <button>Documentation</button>
            </div>
        </div>
    )
}

export default Sidebar;