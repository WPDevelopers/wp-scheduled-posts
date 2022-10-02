import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
import { socialPopUpData } from "./../../utils/helper";
const CustomAppForm = ({ platform, requestHandler }) => {
  const [redirectURI, SetRedirectURI] = useState(
    "https://devapi.schedulepress.com/callback.php"
  );
  const [appID, SetAppID] = useState("");
  const [appSecret, SetAppSecret] = useState("");
  const [isManual, setIsManual] = useState(false);
  const { title, subtitle } = socialPopUpData[platform];

  const hasAutomatic = platform == "linkedin" || platform == "pinterest";

  return (
    <React.Fragment>
      <div className="modalbody">
        <div className="wpsp-social-account-insert-modal">
          <div
            className={`wpsp-social-modal-header ${
              platform == "linkedin" || platform == "pinterest" ? "flex" : ""
            }`}
          >
            <h3>{title}</h3>
            {!hasAutomatic && (
              <p
                dangerouslySetInnerHTML={{
                  __html: subtitle,
                }}
              ></p>
            )}
            {hasAutomatic && (
              <div className="menual_connection_checker">
                <label className="toggler_wrapper">
                  <input
                    type="checkbox"
                    value={isManual}
                    onChange={(e) => {
                      setIsManual(e.target.checked);
                    }}
                  />
                  <span className="text">
                    Connect with {!isManual ? "App credentials" : "Account"}
                  </span>
                  <span
                    className={`toggler ${isManual ? "checked" : ""}`}
                  ></span>
                </label>
              </div>
            )}
          </div>
          <input type="hidden" name="tempmodaltype" value="twitter" />
          {hasAutomatic && !isManual && (
            <div
              style={{
                display: "flex",
                justifyContent: "center",
                marginTop: 5,
                marginBottom: 15,
              }}
            >
              <a
                onClick={() =>
                  requestHandler(
                    "https://devapi.schedulepress.com/v2/callback.php",
                    null,
                    null
                  )
                }
                className="wpsp-modal-generate-token-button"
              >
                {__("Connect your account", "wp-scheduled-posts")}
              </a>
            </div>
          )}
          {(isManual || platform == "facebook" || platform == "twitter") && (
            <form>
            <table className="form-table">
              <tbody>
                <tr>
                  <td align="left">
                    <div className="form-group redirect-group">
                      <div className="form-label">
                        <label>
                          {__("Redirect URI:", "wp-scheduled-posts")}{" "}
                        </label>
                      </div>
                      <div className="form-input">
                        <input
                          type="text"
                          required
                          value={redirectURI}
                          placeholder={__("Redirect URI", "wp-scheduled-posts")}
                          onChange={(e) => SetRedirectURI(e.target.value)}
                        />
                        <div className="doc">
                        {platform == "linkedin" &&
                        __(
                          "Add this URL in the Authorized redirect URLs field of your LinkedIn app.",
                          "wp-scheduled-posts"
                        )}
                        {platform == "pinterest" &&
                        __(
                          "Add this URL in the Redirect URLs field of your Pinterest app.",
                          "wp-scheduled-posts"
                        )}
                        {platform !== "linkedin" && platform !== "pinterest" && (
                          <>
                          {__(
                            "Copy this and paste it in your",
                            "wp-scheduled-posts"
                          )}{" "}
                          {platform}{" "}
                          {__("app Callback url field.", "wp-scheduled-posts")}
                          </>
                        )}
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td align="left">
                    <div className="form-group">
                      <div className="form-label">
                        <label>
                          {platform === "twitter"
                            ? __("API key:", "wp-scheduled-posts")
                            : __("App ID:", "wp-scheduled-posts")}
                        </label>
                      </div>
                      <div className="form-input">
                        <input
                          type="text"
                          required
                          value={appID}
                          placeholder={
                            platform === "twitter"
                              ? __("API key", "wp-scheduled-posts")
                              : __("App ID", "wp-scheduled-posts")
                          }
                          onChange={(e) => SetAppID(e.target.value)}
                        />
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td align="left">
                    <div className="form-group">
                      <div className="form-label">
                        <label>
                          {platform === "twitter"
                            ? __("API Secret Key:", "wp-scheduled-posts")
                            : __("App Secret:", "wp-scheduled-posts")}
                        </label>
                      </div>
                      <div className="form-input">
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
                    </div>
                  </td>
                </tr>
                <tr>
                  <td align="left">
                    <div
                      className="form-group"
                      style={{
                        display: "flex",
                        justifyContent: hasAutomatic ? "center" : "flex-start",
                      }}
                    >
                      <button
                      type="submit"
                      className="wpsp-modal-generate-token-button"
                      onClick={(event) => {
                        if(redirectURI && appID && appSecret){
                          requestHandler(redirectURI, appID, appSecret);
                          event.preventDefault();
                        }
                      }}>
                         {__("Connect your account", "wp-scheduled-posts")}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            </form>
          )}
          {hasAutomatic && (
            <p
              className="wpsp-social-modal-description"
              dangerouslySetInnerHTML={{
                __html: subtitle,
              }}
            ></p>
          )}
        </div>
      </div>
    </React.Fragment>
  );
};
export default CustomAppForm;
