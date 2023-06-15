import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
const ApiCredentialsForm = ({ platform,requestHandler }) => {


    const [appID, SetAppID] = useState("793062612541839");
    const [appSecret, SetAppSecret] = useState("144f868aa0233914b1e94dabb4edf9f0");

    const redirectURIv2 = "https://api.schedulepress.com/v2/callback.php";
    const [redirectURI, SetRedirectURI] = useState(
        "https://api.schedulepress.com/callback.php"
    );
 
  return (
    <React.Fragment>
      <div className="modalbody">
        <div className="wpsp-social-account-insert-modal">
          {(platform == "facebook") && (
            <form>
                <div className="form-group">
                    <label htmlFor="">Redirect URI:</label>
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
                </div>
                <div className="form-group">
                    <label htmlFor="">App ID: </label>
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
                    <label htmlFor="">App Secret: </label>
                    <input
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
                onClick={(event) => {
                  if (redirectURI && appID && appSecret) {
                    requestHandler(redirectURI, appID, appSecret);
                    event.preventDefault();
                  }
                }}
                >Connect Your Account</button>
            </form>
          )}
        </div>
      </div>
    </React.Fragment>
  );
};
export default ApiCredentialsForm;
