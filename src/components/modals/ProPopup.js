import React, { useContext } from 'react';
import { AppContext } from '../../context/AppContext';

const { __ } = wp.i18n;

const PRICING_URL = 'https://schedulepress.com/#pricing';

const ProPopup = () => {
    const { state, dispatch } = useContext(AppContext);
    const { isOpenProPopup } = state;

    if (!isOpenProPopup) return null;

    const close = () => dispatch({ type: 'SET_OPEN_PRO_POPUP', payload: false });

    const assetsURI = (window.WPSchedulePostsFree && window.WPSchedulePostsFree.assetsURI) || '';
    const gifSrc = `${assetsURI}images/upgrade-pro.gif`;

    return (
        <div className="wpsp-pro-popup-overlay" onClick={close}>
            <div className="wpsp-pro-popup" onClick={(e) => e.stopPropagation()} style={{ maxWidth: '550px', width: '100%' }}>
                <button type="button" className="wpsp-pro-popup__close" onClick={close} aria-label={__('Close', 'wp-scheduled-posts')}>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 5L15 15M15 5L5 15" stroke="#7C7E8C" strokeWidth="1.8" strokeLinecap="round"/>
                    </svg>
                </button>

                <h2 className="wpsp-pro-popup__title">{ __('Opps!', 'wp-scheduled-posts') }</h2>
                <h3 className="wpsp-pro-popup__subtitle">{ __('You Need SchedulePress PRO', 'wp-scheduled-posts') }</h3>

                <div className="wpsp-pro-popup__illustration">
                    <img
                        src={gifSrc}
                        alt={ __('Upgrade to Pro', 'wp-scheduled-posts') }
                        style={{ maxWidth: '400px', width: '100%', height: 'auto', borderRadius: '12px', display: 'block' }}
                    />
                </div>

                <a href={PRICING_URL} target="_blank" rel="noopener noreferrer" className="wpsp-pro-popup__cta">
                    { __('Check Pricing Plans', 'wp-scheduled-posts') }
                </a>
            </div>
        </div>
    );
};

export default ProPopup;
