import React from 'react'

const SocialTabHeader = ({ socialPlatform, field }) => {
    const socialData = {
        facebook: {
            icon: 'Facebook',
            title: 'Facebook',
            subtitle:
                'You can enable/disable facebook social share. For details on facebook configuration, check out this Doc',
        },
        twitter: {
            icon: 'twitter',
            title: 'Twitter',
            subtitle:
                'You can enable/disable facebook social share. For details on facebook configuration, check out this Doc',
        },
        linkedin: {
            icon: 'linkedin',
            title: 'Linkedin',
            subtitle:
                'You can enable/disable facebook social share. For details on facebook configuration, check out this Doc',
        },
        pinterest: {
            icon: 'pinterest',
            title: 'Pinterest',
            subtitle:
                'You can enable/disable facebook social share. For details on facebook configuration, check out this Doc',
        },
    }
    return (
        <React.Fragment>
            <div className='wpscp-social-tab__item-header wpscp-social-tab__item-header--facebook'>
                <div className='entry-icon'>
                    <img
                        src='https://itushar.me/dev/wp-content/plugins/wp-scheduled-posts/admin/partials/./../assets/images/icon-facebook.png'
                        alt='icon'
                    />
                </div>
                <div className='entry-content'>
                    <h3>{socialData[socialPlatform].title}</h3>
                    <p>{socialData[socialPlatform].subtitle}</p>
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
