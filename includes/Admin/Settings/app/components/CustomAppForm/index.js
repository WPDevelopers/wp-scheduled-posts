import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
import { socialPopUpData } from "./../../utils/helper";
import { CopyToClipboard } from "react-copy-to-clipboard";
import { toast } from "react-toastify";
const CustomAppForm = ({ platform, requestHandler }) => {
  const redirectURIv2 = "https://api.schedulepress.com/v2/callback.php";
  const [redirectURI, SetRedirectURI] = useState(
    "https://api.schedulepress.com/callback.php"
  );
  const [appID, SetAppID] = useState("");
  const [appSecret, SetAppSecret] = useState("");
  const [isManual, setIsManual] = useState(false);
  const [coppied, setCoppied] = useState(false);
  const { title, subtitle } = socialPopUpData[platform];

  const hasAutomatic = platform == "linkedin" || platform == "pinterest";

  const handleURICopy = () => {
    setCoppied(true);
    setTimeout(() => {
      setCoppied(false);
    }, 2000);
  };

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
                onClick={() => requestHandler(redirectURIv2, null, null)}
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
                        <div
                          className="form-input"
                          style={{ display: "flex", flexDirection: "column" }}
                        >
                          <span style={{ position: "relative" }}>
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
                            <CopyToClipboard
                              text={redirectURI}
                              onCopy={() => handleURICopy()}
                            >
                              <span
                                className="copyButton"
                                onClick={() => handleURICopy(redirectURI)}
                              >
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAABmJLR0QA/wD/AP+gvaeTAAACuElEQVR4nO2bPWgUQRiGn9PAiUZBQQknghYW/jQWNqL4U5giWqsINpIgWqa10MpCEBQsJFiIIPYmhRAwFnaHiBCtLESD50/EQxQiBi0mK7Ozu7nMztx+u+s8MLAzszO88zJ/d/cdBP5vGg7tTgKngd3Aem+K/LIIdIAZ4A4w56PTLcAT4E/F0k9g1HXwg8DLEgzGJTmZcL0EA/AxE7ZFA7LZA5rAZ+LrvQvcBt5a9FMkTWAEGDbKrwJXbDs7QNLNo44Ci+Ixcd1Po4pVFp1sMvLfULtrFZg08kPRg40BbeC7ln+EcrMKLBj5gcTDCuigpvwY8B644a4rUBRjxPeAN1FFrxkwtJRW901aPr4C74Df/eh8ALgAvEb+zF4uzQO3UDfTXmTOAJN1JI+Msqc5YF9eA8xT4C5wvEdnZaOFOuY252msG3AYOOVDkQAt4HKehvomeM6oWwAuAVMkz1FJGsAe1MfbXVr5WWAch42xTXyd3MyvsRAOkdwPtme8u6I9YKPR6JUvpX1iNqXMvK73ZLmrcNmvuWn6rL/hsvksUEuCAdICpAkGSAuQJhggLUCaYIC0AGmCAdICpAkGSAuQJhggLUCaYIC0AGmCAdICpAkGSAuQJhggLUCaYIC0AGmCAdICCmKHke9GDzZRYlWkARwDLhrl/35YresMGAQeoIKppoENRv3D6KGuM2AcOJNRN40K+gDqOwNaGeUvMIypqwETxMN6u8A14CDwRX+xrkugDewE9qMG/Bz4lfZiXQ0A+EgySjyBvgQWjbqmVzn+WZNSZh0hphvQMepGbDsrmBMpZR9sO9GXwAwq9CxiGBU2O0n54gT3okLfdGaBTy4dt4AfyMf+5k3nXQYfYQYUViVN4TGkf5RqzYT7wNq8g80KLNy6ZMQR1NIo2x8m5lG3unvAM2EtgUrzFzG21zF8JcAYAAAAAElFTkSuQmCC" />
                                {coppied && (
                                  <span className="copyTooltip">Coppied</span>
                                )}
                              </span>
                            </CopyToClipboard>
                          </span>
                          <div
                            className="doc"
                            style={{ fontSize: 12, marginTop: 5 }}
                          >
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
                            {platform !== "linkedin" &&
                              platform !== "pinterest" && (
                                <>
                                  {__(
                                    "Copy this and paste it in your",
                                    "wp-scheduled-posts"
                                  )}{" "}
                                  {platform}{" "}
                                  {__(
                                    "app Callback url field.",
                                    "wp-scheduled-posts"
                                  )}
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
                          justifyContent: "center",
                        }}
                      >
                        <button
                          type="submit"
                          className="wpsp-modal-generate-token-button"
                          onClick={(event) => {
                            if (redirectURI && appID && appSecret) {
                              requestHandler(redirectURI, appID, appSecret);
                              event.preventDefault();
                            }
                          }}
                        >
                          {__("Connect your account", "wp-scheduled-posts")}
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </form>
          )}
          <p
            className="wpsp-social-modal-description"
            dangerouslySetInnerHTML={{
              __html: subtitle,
            }}
          ></p>
        </div>
      </div>
    </React.Fragment>
  );
};
export default CustomAppForm;
