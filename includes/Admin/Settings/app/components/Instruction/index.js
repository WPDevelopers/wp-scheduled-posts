import React from 'react'
const Instruction = ({ icon, title, desc, button }) => {
    return (
        <React.Fragment>
            <div className='instruction_item'>
                <div className='instruction_item_top'>
                    <div className='instruction_log'>
                        <img src={icon} alt='Documentation' />
                    </div>
                    <h3 className='instruction_label'>{title}</h3>
                </div>
                <p>{desc}</p>
                <a href={button.url} rel='nofollow' className='instructin_btn'>
                    {button.text}
                </a>
            </div>
        </React.Fragment>
    )
}
export default Instruction
