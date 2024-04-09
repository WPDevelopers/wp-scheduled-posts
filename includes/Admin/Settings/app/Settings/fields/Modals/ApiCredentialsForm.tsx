import { __ } from "@wordpress/i18n";
import React, { useState } from "react";
import { CopyToClipboard } from "react-copy-to-clipboard";

const ApiCredentialsForm = ({ props, platform, requestHandler, appInfo = [] }) => {
  const [appID, SetAppID] = useState( appInfo['app_id'] ? appInfo['app_id'] : "" );
  const [appSecret, SetAppSecret] = useState(appInfo['app_secret'] ? appInfo['app_secret'] : "" );
  const [isManual, setIsManual] = useState(false);
  const [isMultiAccountError,setMultiAccountError] = useState(false);
  const [multiAccountErrorMessage,setMultiAccountErrorMessage] = useState();
  const [copied, setCopied] = useState(false);
  const [openIDConnect, setOpenIDConnect] = useState(false);

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
      requestHandler(redirectURI, appID, appSecret,platform, openIDConnect).then((res) => {
        if( res?.error ) {
          setMultiAccountError(true);
          setMultiAccountErrorMessage(res?.message);
        }
        return res;
      });
    }
  }
  const handleURICopy = () => {
    setCopied(true);
    setTimeout(() => {
      setCopied(false);
    }, 2000);
  };
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
            {(isManual || platform == 'instagram' || platform == "facebook" || platform == "twitter") && (
              <form onSubmit={onSubmitHandler}>
                  <div className="form-group">
                      <label htmlFor="">{ __('Redirect URI:','wp-scheduled-posts') }</label>
                      <span className="redirect_url_wrapper">
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
                            readOnly
                        />
                        <CopyToClipboard
                          text={redirectURI}
                          onCopy={() => handleURICopy()}
                        >
                          <span
                            className="copyButton"
                            onClick={() => handleURICopy()}
                          >
                            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAABmJLR0QA/wD/AP+gvaeTAAACuElEQVR4nO2bPWgUQRiGn9PAiUZBQQknghYW/jQWNqL4U5giWqsINpIgWqa10MpCEBQsJFiIIPYmhRAwFnaHiBCtLESD50/EQxQiBi0mK7Ozu7nMztx+u+s8MLAzszO88zJ/d/cdBP5vGg7tTgKngd3Aem+K/LIIdIAZ4A4w56PTLcAT4E/F0k9g1HXwg8DLEgzGJTmZcL0EA/AxE7ZFA7LZA5rAZ+LrvQvcBt5a9FMkTWAEGDbKrwJXbDs7QNLNo44Ci+Ixcd1Po4pVFp1sMvLfULtrFZg08kPRg40BbeC7ln+EcrMKLBj5gcTDCuigpvwY8B644a4rUBRjxPeAN1FFrxkwtJRW901aPr4C74Df/eh8ALgAvEb+zF4uzQO3UDfTXmTOAJN1JI+Msqc5YF9eA8xT4C5wvEdnZaOFOuY252msG3AYOOVDkQAt4HKehvomeM6oWwAuAVMkz1FJGsAe1MfbXVr5WWAch42xTXyd3MyvsRAOkdwPtme8u6I9YKPR6JUvpX1iNqXMvK73ZLmrcNmvuWn6rL/hsvksUEuCAdICpAkGSAuQJhggLUCaYIC0AGmCAdICpAkGSAuQJhggLUCaYIC0AGmCAdICpAkGSAuQJhggLUCaYIC0AGmCAdICCmKHke9GDzZRYlWkARwDLhrl/35YresMGAQeoIKppoENRv3D6KGuM2AcOJNRN40K+gDqOwNaGeUvMIypqwETxMN6u8A14CDwRX+xrkugDewE9qMG/Bz4lfZiXQ0A+EgySjyBvgQWjbqmVzn+WZNSZh0hphvQMepGbDsrmBMpZR9sO9GXwAwq9CxiGBU2O0n54gT3okLfdGaBTy4dt4AfyMf+5k3nXQYfYQYUViVN4TGkf5RqzYT7wNq8g80KLNy6ZMQR1NIo2x8m5lG3unvAM2EtgUrzFzG21zF8JcAYAAAAAElFTkSuQmCC" />
                            {copied && (
                              <span className="copyTooltip">{__('Copied','wp-scheduled-posts')}</span>
                            )}
                          </span>
                        </CopyToClipboard>
                      </span>
                      
                      <span className="redirect-note">{props?.modal?.redirect_url_desc}</span>
                  </div>
                  { platform == 'linkedin' && 
                    <div className="linkedin-openid">
                      <div className="toggler_wrapper">
                        <span className="text">{ __( 'OpenID Connect','wp-scheduled-posts' ) }</span>
                        <div className="status">
                            <div className="switcher">
                              <input
                                  id="linkedin_openid_status"
                                  type='checkbox'
                                  className="wprf-switcher-checkbox"
                                  checked={openIDConnect}
                                  onChange={(e) => {
                                    setOpenIDConnect(e.target.checked)
                                  }}
                              />
                              <label
                                  className="wprf-switcher-label"
                                  htmlFor="linkedin_openid_status"
                                  style={{ background: openIDConnect && '#02AC6E' }}
                              >
                                <span className={`wprf-switcher-button`} />
                              </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  }
                  <div className="form-group">
                      <label htmlFor="">{ __( 'App ID:','wp-scheduled-posts' ) } </label>
                      <input
                          type="text"
                          required
                          value={appID}
                          placeholder={
                              platform === "twitter"
                              ? __("API ID", "wp-scheduled-posts")
                              : __("App ID", "wp-scheduled-posts")
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
