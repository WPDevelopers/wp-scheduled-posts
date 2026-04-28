import React, { useContext } from 'react';
import { AppContext } from '../context/AppContext';

const overlayStyle = {
    position: 'absolute',
    inset: 0,
    cursor: 'pointer',
    background: 'transparent',
    zIndex: 2,
};

const itemStyle = { position: 'relative' };

export default function useProOverlay() {
    const { dispatch } = useContext(AppContext);
    const isPro = !!(window.WPSchedulePostsFree && window.WPSchedulePostsFree.is_pro);

    const openProPopup = (e) => {
        if (isPro) return;
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        dispatch({ type: 'SET_OPEN_PRO_POPUP', payload: true });
    };

    const proOverlay = !isPro ? (
        <div
            className="wpsp-pro-option__overlay"
            role="button"
            tabIndex={0}
            onClick={openProPopup}
            onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') openProPopup(e); }}
            style={overlayStyle}
        />
    ) : null;

    return { isPro, openProPopup, proOverlay, itemStyle };
}
