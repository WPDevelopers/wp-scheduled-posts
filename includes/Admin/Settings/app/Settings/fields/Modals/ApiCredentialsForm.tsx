import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
const ApiCredentialsForm = ({ props, platform, requestHandler }) => {
  const [appID, SetAppID] = useState("");
  const [appSecret, SetAppSecret] = useState("");
  const [isManual, setIsManual] = useState(false);
  const [isMultiAccountError,setMultiAccountError] = useState(false);
  const [multiAccountErrorMessage,setMultiAccountErrorMessage] = useState();

  const redirectURIv2 = "https://api.schedulepress.com/v2/callback.php";
  const [redirectURI, SetRedirectURI] = useState(
      "https://api.schedulepress.com/callback.php"
  );

  const hasAutomatic = platform == "linkedin" || platform == "pinterest";
  
  const handleProfileConnection = () => {
   requestHandler(redirectURIv2, '', '', platform).then((res) => {
      if( res?.error ) {
        setMultiAccountError(true);
        setMultiAccountErrorMessage(res?.message);
      }
   });
  }
  const onSubmitHandler = (event) => {
    event.preventDefault();
    if (redirectURI && appID && appSecret) {
      requestHandler(redirectURI, appID, appSecret,platform).then((res) => {
        if( res?.error ) {
          setMultiAccountError(true);
          setMultiAccountErrorMessage(res?.message);
        }
        return res;
      });
    }
  }
  return (
    <React.Fragment>
      <div className={`modalbody ${ platform ? platform + '_wrapper' : ""}`}>
        { !isMultiAccountError ? (
          <div className="wpsp-social-account-insert-modal">
            <div className="platform-info">
              <img width={'30px'} src={`${props?.modal?.logo}`} alt={`${props?.label}`} />
              <h4>{props?.label}</h4>
            </div>
            {hasAutomatic && (
                <div className="menual_connection_checker">
                  <label className="toggler_wrapper">
                    <div className="status">
                        <div className="switcher">
                            <input
                                id="app_credential_toggle"
                                type='checkbox'
                                className="wprf-switcher-checkbox"
                                checked={isManual}
                                onChange={(e) => {
                                  setIsManual(e.target.checked);
                                }}
                            />
                            <label
                                className="wprf-switcher-label"
                                htmlFor="app_credential_toggle"
                                style={{ background: isManual && '#02AC6E' }}
                            >
                              <span className={`wprf-switcher-button`} />
                            </label>
                        </div>
                    </div>
                    <span className="text">
                      { __('Connect with','wp-scheduled-posts') } {!isManual ? __("App credentials",'wp-scheduled-posts') : __("Account",'wp-scheduled-posts')}
                    </span>
                  </label>
                </div>
            )}
            <input type="hidden" name="tempmodaltype" value="twitter" />
            {hasAutomatic && !isManual && (
              <div 
                className="wpsp-modal-generate-token-button-wrapper"
              >
                <a
                  onClick={ handleProfileConnection }
                  className="wpsp-modal-generate-token-button"
                >
                  {__("Connect your account", "wp-scheduled-posts")}
                </a>
              </div>
            )}
            {(isManual || platform == "facebook" || platform == "twitter") && (
              <form onSubmit={onSubmitHandler}>
                  <div className="form-group">
                      <label htmlFor="">{ __('Redirect URI:','wp-scheduled-posts') }</label>
                      <input
                          type="text"
                          required
                          value={redirectURI}
                          placeholder={__(
                          "Redirect URI",
                          "wp-scheduled-posts"
                          )}
                          style={{ marginRight: 30 }}
                          onChange={(e) => SetRedirectURI(e.target.value)}
                      />
                      <span className="redirect-note">{props?.modal?.redirect_url_desc}</span>
                  </div>
                  <div className="form-group">
                      <label htmlFor="">{ __( 'App ID:','wp-scheduled-posts' ) } </label>
                      <input
                          type="text"
                          required
                          value={appID}
                          placeholder={
                              platform === "twitter"
                              ? __("API Secret Key", "wp-scheduled-posts")
                              : __("App Secret", "wp-scheduled-posts")
                          }
                          onChange={(e) => SetAppID(e.target.value)}
                      />
                  </div>
                  <div className="form-group">
                      <label htmlFor="">{ __( 'App Secret:','wp-scheduled-posts' ) } </label>
                      <input
                          className="test"
                          type="text"
                          required
                          value={appSecret}
                          placeholder={
                              platform === "twitter"
                              ? __("API Secret Key", "wp-scheduled-posts")
                              : __("App Secret", "wp-scheduled-posts")
                          }
                          onChange={(e) => SetAppSecret(e.target.value)}
                      />
                  </div>
                  <button
                  type="submit"
                  className="wpsp-modal-generate-token-button"
                  >{ __( 'Connect Your Account','wp-scheduled-posts' ) }</button>
              </form>
            )}
            <p dangerouslySetInnerHTML={{ __html: props?.modal?.desc }}></p>
          </div>
        ) : (
          <div className="wpsp-multi-account-error">
              <p dangerouslySetInnerHTML={ { __html: multiAccountErrorMessage } }></p>
          </div>
        ) }
        
      </div>
    </React.Fragment>
  );
};
export default ApiCredentialsForm;
