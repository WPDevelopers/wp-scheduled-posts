import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import React from 'react';
const docIcon =  require("../assets/images/doc.png");
// const upgradePro =  require("../assets/images/upgrade-pro.png");

const Sidebar = ({ props }) => {
    const builderContext = useBuilderContext();
    
    if(props.id !== 'tab-sidebar-layout' || builderContext.config.active === 'layout_calendar' ) {
        return;
    }
    {/* @ts-ignore */}
    const is_pro = wpspSettingsGlobal.pro_version;

    return (
        <div className="wpsp-admin-sidebar">
            {
                is_pro && (
                <div className='manage-license card'>
                    {/* @ts-ignore */}
                    <img src={`${wpspSettingsGlobal?.image_path}upgrade-pro-new.png`} alt={__('upgrade-pro-img','wp-scheduled-posts')} />
                    <div className="content">
                        <h3>{__('Manage License','wp-scheduled-posts')}</h3>
                        <p>{ __('Supercharge your content schedule and ave a peace in mind','wp-scheduled-posts') }</p>
                        <a href='#'>{__('Manage License','wp-scheduled-posts')}</a>
                    </div>
                </div>
                )
            }
            {
                !is_pro && (
                <div className='upgrade-pro card'>
                    {/* <img className='icon-wrapper' src={upgradePro} alt='icon-1' /> */}
                    {/* @ts-ignore */}
                    <img src={`${wpspSettingsGlobal?.image_path}upgrade-pro-new.png`} alt={__('upgrade-pro-img','wp-scheduled-posts')} />
                    <div className="content">
                        <h3>{__('Get Unlimited Features','wp-scheduled-posts')}</h3>
                        <p>{ __('Supercharge your content schedule and have a peace in mind','wp-scheduled-posts') }</p>
                        <a href='#'>{__('Upgrade To Pro','wp-scheduled-posts')}</a>
                    </div>
                </div>
                )
            }
            
            
            <div className='card'>
                <i className="wpsp-icon wpsp-file"></i>
                <h3>{__('Documentation','wp-scheduled-posts')}</h3>
                <p>{__('Get started spending some time with the documentation to get familiar with SchedulePress.','wp-scheduled-posts')}</p>
                <a href='#'>{__('Documentation','wp-scheduled-posts')}</a>
            </div>
            <div className='card'>
                <i className="wpsp-icon wpsp-puzzle"></i>
                <h3>{__('Contribute to SchedulePress','wp-scheduled-posts')}</h3>
                <p>{__('You can contribute to making SchedulePress better by reporting bugs','wp-scheduled-posts')}</p>
                <a href='#'>{__('Report A Bug','wp-scheduled-posts')}</a>
            </div>
            <div className='card'>
                <i className="wpsp-icon wpsp-comment"></i>
                <h3>{__('Need Help?','wp-scheduled-posts')}</h3>
                <p>{__('Stuck with something? Get help from the community WPDeveloper Forum or Facebook Community.','wp-scheduled-posts')}</p>
                <a href='#'>{__('Get Support','wp-scheduled-posts')}</a>
            </div>
            <div className='card'>
                <i className="wpsp-icon wpsp-chat-2"></i>
                <h3>{__('Show your Love','wp-scheduled-posts')}</h3>
                <p>{__('We love to have you in the SchedulePress family. We are making it more awesome every day.','wp-scheduled-posts')}</p>
                <a href='#'>{__('Show your Love','wp-scheduled-posts')}</a>
            </div>
        </div>
    );
  };

export default Sidebar;


