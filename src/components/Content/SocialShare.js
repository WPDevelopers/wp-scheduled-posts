import React, { useContext } from 'react';
import { AppContext } from '../../context/AppContext';
const SocialShare = () => {
  const { state, dispatch } = useContext(AppContext);

    const handleCustomSocialMessage = () => {
        dispatch({ type: 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL', payload: !state.isOpenCustomSocialMessageModal });
    };

    return (
        <div className="wpsp-modal-social-share">
            <h2>Social Share Settings</h2>
            <div className="wpsp-post--card">
                <div className="wpsp-disabled-social-share-checkbox">
                    <div className="wpsp-share-checkbox">
                        <input type="checkbox" id="socialShareDisable" name="socialShareDisable" />
                        <label htmlFor="socialShareDisable">Disable Social Share</label>
                    </div>
                </div>
                <div className="wpsp-upload-social-banner">
                    <div className="wpsp-upload-social-banner-btn">
                        <button className="wpsp-upload-social-share-btn">Upload Social Banner</button>
                        <p>*If you don't upload, featured image will be selected as banner</p>
                    </div>
                </div>
                <div className="wpsp-add-social-message-wrapper">
                    <p>Add message for target platforms</p>
                    <button className="wpsp-add-social-message-btn" onClick={handleCustomSocialMessage}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path d="M7.5 0L7.743 0.000749946L7.9815 0.00375009L8.4435 0.0165001L8.66775 0.0262501L9.102 0.05175L9.51675 0.08625C13.1055 0.43425 14.5657 1.8945 14.9137 5.48325L14.9482 5.898L14.9737 6.33225C14.9775 6.40575 14.9812 6.48075 14.9835 6.5565L14.9962 7.0185L15 7.5L14.9962 7.9815L14.9835 8.4435L14.9737 8.66775L14.9482 9.102L14.9137 9.51675C14.5657 13.1055 13.1055 14.5657 9.51675 14.9137L9.102 14.9482L8.66775 14.9737C8.59425 14.9775 8.51925 14.9812 8.4435 14.9835L7.9815 14.9962L7.5 15L7.0185 14.9962L6.5565 14.9835L6.33225 14.9737L5.898 14.9482L5.48325 14.9137C1.8945 14.5657 0.43425 13.1055 0.08625 9.51675L0.05175 9.102L0.0262501 8.66775C0.0226014 8.59302 0.0193514 8.51827 0.0165001 8.4435L0.00375009 7.9815C0.00150009 7.824 0 7.6635 0 7.5L0.000749946 7.257L0.00375009 7.0185L0.0165001 6.5565L0.0262501 6.33225L0.05175 5.898L0.08625 5.48325C0.43425 1.8945 1.8945 0.43425 5.48325 0.08625L5.898 0.05175L6.33225 0.0262501C6.40575 0.0225001 6.48075 0.0187501 6.5565 0.0165001L7.0185 0.00375009C7.176 0.00150009 7.3365 0 7.5 0ZM7.5 4.5C7.30109 4.5 7.11032 4.57902 6.96967 4.71967C6.82902 4.86032 6.75 5.05109 6.75 5.25V6.75H5.25L5.16225 6.75525C4.97243 6.77783 4.79839 6.87204 4.6757 7.01863C4.55301 7.16522 4.49092 7.35312 4.50212 7.54395C4.51332 7.73478 4.59697 7.91414 4.73597 8.04536C4.87498 8.17659 5.05884 8.24979 5.25 8.25H6.75V9.75L6.75525 9.83775C6.77783 10.0276 6.87204 10.2016 7.01863 10.3243C7.16522 10.447 7.35312 10.5091 7.54395 10.4979C7.73478 10.4867 7.91414 10.403 8.04536 10.264C8.17659 10.125 8.24979 9.94116 8.25 9.75V8.25H9.75L9.83775 8.24475C10.0276 8.22217 10.2016 8.12796 10.3243 7.98137C10.447 7.83478 10.5091 7.64688 10.4979 7.45605C10.4867 7.26521 10.403 7.08586 10.264 6.95464C10.125 6.82341 9.94116 6.75021 9.75 6.75H8.25V5.25L8.24475 5.16225C8.22326 4.97981 8.13556 4.81161 7.99828 4.68954C7.861 4.56747 7.6837 4.50002 7.5 4.5Z" fill="white" />
                        </svg> Add Social Message</button>
                </div>
            </div>
        </div>
    );
};

export default SocialShare;
