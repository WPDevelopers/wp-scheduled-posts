import React, { useContext } from 'react'
import { AppContext } from '../../context/AppContext';
import SocialTemplates from './SocialTemplates';
import ProPopup from './ProPopup';

const Modals = () => {
    const { state, dispatch } = useContext(AppContext);
    const { isOpenCustomSocialMessageModal } = state;
    return (
        <div className={`wpsp-post-panel-inner-modal`}>
            { isOpenCustomSocialMessageModal && (
               <SocialTemplates />
            )}
            <ProPopup />
        </div>
    )
}

export default Modals
