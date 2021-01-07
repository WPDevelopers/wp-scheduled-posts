import React from 'react'
const Support = ({ link, title, icon }) => {
    return (
        <React.Fragment>
            <div className='wpsp_support_items'>
                <a href={link} target='__blank'>
                    <img src={icon} alt='icon' />
                    <h4>{title}</h4>
                </a>
            </div>
        </React.Fragment>
    )
}
export default Support
