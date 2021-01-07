import React from 'react'
import { socialTabHeaderData, wpspGetPluginRootURI } from './../../utils/helper'

const SocialTabHeader = ({ socialPlatform, setFieldValue, field }) => {
    const { icon, title, subtitle } = socialTabHeaderData[socialPlatform]
    return (
        <React.Fragment>
            <div
                className={
                    'wpscp-social-tab__item-header wpscp-social-tab__item-header--' +
                    socialPlatform
                }
            >
                <div className='entry-icon'>
                    <img
                        width='74'
                        height='74'
                        src={wpspGetPluginRootURI + 'assets/images/' + icon}
                        alt='icon'
                    />
                </div>
                <div className='entry-content'>
                    <h3>{title}</h3>
                    <p dangerouslySetInnerHTML={{ __html: subtitle }}></p>
                </div>
                <div className='entry-control'>
                    <div className='checkbox_wrap'>
                        <div className='wpsp_switch'>
                            <input
                                type='checkbox'
                                checked={field.value}
                                name={field.name}
                                onChange={() =>
                                    setFieldValue(field.name, !field.value)
                                }
                            />
                            <span className='wpsp_switch_slider'></span>
                        </div>
                    </div>
                </div>
            </div>
        </React.Fragment>
    )
}
export default SocialTabHeader
