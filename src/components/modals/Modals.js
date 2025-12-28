import React, { useContext } from 'react'
import { AppContext } from '../../context/AppContext';
import SocialTemplates from './SocialTemplates';

const Modals = () => {
    const { state, dispatch } = useContext(AppContext);    
    const { isOpenCustomSocialMessageModal } = state;
    return (
        <div className={`wpsp-post-panel-inner-modal`}>
            { isOpenCustomSocialMessageModal && (
               <SocialTemplates />
            )}
        </div>
    )
}

export default Modals
