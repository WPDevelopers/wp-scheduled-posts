import React, { useState, useEffect, useCallback } from "react";
import { __ } from "@wordpress/i18n";
import store from "./../../redux/store";
import { connect } from "react-redux";
import { close_redirect_popup } from "./../../redux/actions/social.actions";
import { bindActionCreators } from "redux";
import { useField, FieldArray } from "formik";
import { wpspSettingsGlobal, wpspGetPluginRootURI } from "./../../utils/helper";
import Modal from "react-modal";
import SocialTabHeader from "./../Social/SocialTabHeader";
import Facebook from "./../Facebook";
import Profile from "./../Social/Profile";
import ListItemProfile from "./../Social/ListItemProfile";
import CustomAppForm from "./../../components/CustomAppForm";
import Pinterest from "../Pinterest";
import LinkedIn from "../LinkedIn";
const noSection = { label: "No Section", value: "" };

const customStyles = {
  overlay: {
    background: "rgba(1, 17, 50, 0.7)",
    padding: "50px 20px",
    display: "flex",
    overflow: "auto",
  },
  content: {
    margin: "auto",
    maxWidth: "100%",
    width: "450px",
    position: "static",
    overflow: "hidden",
  },
};

const customStylesForPaintarist = {
  overlay: {
    background: "rgba(1, 17, 50, 0.7)",
    padding: "50px 20px",
    display: "flex",
    overflow: "auto",
  },
  content: {
    width: "600px",
    maxWidth: "100%",
    margin: "auto",
    position: "static",
    overflow: "visible!important",
  },
};

const SocialProfile = ({ id, app, setFieldValue, close_redirect_popup }) => {
  const [modalIsOpen, setModalIsOpen] = useState(false);
  const [
    modalMultiProfileErrorIsOpen,
    setModalMultiProfileErrorIsOpen,
  ] = useState(false);
  const [multiProfileErrorMessage, setMultiProfileErrrorMessage] = useState(
    false
  );
  const [customAppModalIsOpen, setCustomAppModalIsOpen] = useState(false);
  const [localSocial, setLocalSocial] = useState();
  const [requestSending, setRequestSending] = useState(false);
  const [fbPage, setFbPage] = useState([]);
  const [fbGroup, setFbGroup] = useState([]);
  const [pinterestBoards, setPinterestBoards] = useState([]);
  const [responseData, setResponseData] = useState([]);
  const [linkedInData, setLinkedInData] = useState({})
  const [socialPlatform, setSocialPlatform] = useState("");
  const [field] = useField(id);
  const [fieldStatus] = useField(field.name + "_status");
  const [fieldList] = useField(field.name + "_list");
  const [cashedSectionData, setCashedSectionData] = useState({});
  const [error, setError] = useState("");

  useEffect(() => {
    setLocalSocial(store.getState("social"));
    if (
      localSocial !== undefined &&
      localSocial.social.redirectFromOauth === true
    ) {
      setModalIsOpen(true);
      // remove unnecessary query string
      if (history.pushState) {
        history.pushState(null, null, window.location.href.split("&")[0]);
      }
    }
  }, [localSocial]);

  function afterOpenModal() {
    setRequestSending(true);
    setSocialPlatform(localSocial.social.queryString.get("type"));
    const error = localSocial.social.queryString.get("error_message");
    if (error) {
      setError(error);
      return;
    }
    /**
     * send ajax requrest for generate access token and fetch user, page info
     */
    var data = {
      action: "wpsp_social_profile_fetch_user_info_and_token",
      type: localSocial.social.queryString.get("type"),
      appId: localSocial.social.queryString.get("appId"),
      appSecret: localSocial.social.queryString.get("appSecret"),
      code: localSocial.social.queryString.get("code"),
      redirectURI: localSocial.social.queryString.get("redirectURI"),
      access_token: localSocial.social.queryString.get("access_token"),
      refresh_token: localSocial.social.queryString.get("refresh_token"),
      expires_in: localSocial.social.queryString.get("expires_in"),
      rt_expires_in: localSocial.social.queryString.get("rt_expires_in"),
      oauthVerifier: localSocial.social.queryString.get("oauth_verifier"),
      oauthToken: localSocial.social.queryString.get("oauth_token"),
    };
    jQuery.post(ajaxurl, data, function (response) {
      setRequestSending(false);
      if (response.success) {
        setFbPage(response.page);
        setFbGroup(response.group);
        setResponseData([response.data]);
        setLinkedInData(response.linkedin);
        setPinterestBoards(response.boards);
      } else {
      }
    });
  }

  const customAppProfileRequest = (redirectURI, appID, appSecret) => {
    var data = {
      action: "wpsp_social_add_social_profile",
      redirectURI: redirectURI,
      appId: appID,
      appSecret: appSecret,
      type: app.platform,
    };

    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
    jQuery.post(ajaxurl, data, function (response) {
      if (response.success) {
        open(response.data, "_self");
      } else {
        let message;
        try {
          let _message = JSON.parse(response.data);
          if (_message?.errors?.[0]?.message) {
            message = _message.errors[0].message;
          } else {
            message = response.data;
          }
        } catch (e) {
          message = response.data;
        }
        setMultiProfileErrrorMessage(message);
        setModalMultiProfileErrorIsOpen(true);
        setCustomAppModalIsOpen(false);
      }
    });
  };

  const fetchSectionData = (defaultBoard, profile, updateOptions) => {
    let options = [noSection];
    if (!cashedSectionData?.[defaultBoard]) {
      if (defaultBoard) {
        var data = {
          action: "wpsp_social_profile_fetch_pinterest_section",
          _wpnonce: wpspSettingsGlobal.api_nonce,
          defaultBoard: defaultBoard,
          profile: profile,
        };
        jQuery
          .post(ajaxurl, data, function (response) {
            if (response.success === true) {
              const sections = response.data?.map((section) => {
                return {
                  label: section.name,
                  value: section.id,
                };
              });
              options = [...options, ...sections];
              updateOptions(options);
              setCashedSectionData({
                ...cashedSectionData,
                [defaultBoard]: options,
              });
            }
          })
          .fail(function () {
            updateOptions(options);
          });
      }
    } else {
      updateOptions(cashedSectionData?.[defaultBoard]);
    }
  };

  const openCustomAppModal = () => {
    setCustomAppModalIsOpen(true);
  };
  const closeCustomAppModalIsOpen = () => {
    setCustomAppModalIsOpen(false);
  };

  const closeModalMultiProfileErrorIsOpen = () => {
    setModalMultiProfileErrorIsOpen(false);
  };

  function closeModal() {
    setModalIsOpen(false);
    close_redirect_popup();
  }

  return (
    <div className="form-group">
      <SocialTabHeader
        socialPlatform={app.platform}
        field={fieldStatus}
        setFieldValue={setFieldValue}
      />
      {fieldList.value !== undefined && Array.isArray(fieldList.value) && (
        <div className="wpscp-social-tab__item-list">
          {wpspSettingsGlobal.pro_version ? (
            <FieldArray
              name={fieldList.name}
              render={(arrayHelpers) =>
                fieldList.value.map((item, index) => (
                  <ListItemProfile
                    groupFieldStatus={fieldStatus}
                    fieldList={fieldList}
                    arrayHelpers={arrayHelpers}
                    fetchSectionData={fetchSectionData}
                    item={item}
                    key={item.id}
                    index={index}
                    noSection={noSection}
                  />
                ))
              }
            />
          ) : (
            <FieldArray
              name={fieldList.name}
              render={(arrayHelpers) =>
                fieldList.value
                  .slice(0, 1)
                  .map((item, index) => (
                    <ListItemProfile
                      groupFieldStatus={fieldStatus}
                      fieldList={fieldList}
                      arrayHelpers={arrayHelpers}
                      fetchSectionData={fetchSectionData}
                      item={item}
                      key={item.id}
                      index={index}
                    />
                  ))
              }
            />
          )}
        </div>
      )}

      <button
        type="button"
        className={
          "wpscp-social-tab__btn wpscp-social-tab__btn--" +
          app.platform +
          " wpscp-social-tab__btn--addnew-profile"
        }
        onClick={() => openCustomAppModal(app.platform)}
      >
        <img
          src={
            wpspGetPluginRootURI + "assets/images/icon-" + app.platform + ".png"
          }
          alt="icon"
        />
        {__("Add New Profile", "wp-scheduled-posts")}
      </button>
      <Modal
        isOpen={customAppModalIsOpen}
        onRequestClose={closeCustomAppModalIsOpen}
        style={customStyles}
        ariaHideApp={false}
      >
        <CustomAppForm
          platform={app.platform}
          requestHandler={customAppProfileRequest}
        />
      </Modal>
      <Modal
        isOpen={modalMultiProfileErrorIsOpen}
        onRequestClose={closeModalMultiProfileErrorIsOpen}
        style={customStyles}
        ariaHideApp={false}
      >
        <div className="wpsp-mulit-profile-error-message">
          <div>
            <img
              src={wpspGetPluginRootURI + "assets/images/soft-warning.png"}
              alt="warning"
            />
          </div>
          <h2
            dangerouslySetInnerHTML={{
              __html: multiProfileErrorMessage,
            }}
          ></h2>
        </div>
      </Modal>

      {/* after auth then it will fire */}
      <Modal
        isOpen={modalIsOpen}
        onAfterOpen={afterOpenModal}
        onRequestClose={closeModal}
        style={customStylesForPaintarist}
        ariaHideApp={false}
      >
        {requestSending ? (
          <div className="wpsp-modal-info">
            {error
              ? error
              : __(
                  "Generating Token & Fetching User Data",
                  "wp-scheduled-posts"
                )}
          </div>
        ) : (
          <React.Fragment>
            {
              {
                facebook: (
                  <Facebook
                    fieldName={fieldList.name}
                    field={fieldList}
                    page={fbPage}
                    group={fbGroup}
                  />
                ),
                twitter: (
                  <Profile
                    fieldName={fieldList.name}
                    field={fieldList}
                    platform={socialPlatform}
                    data={responseData}
                  />
                ),
                linkedin: (
                  <LinkedIn
                    fieldName={fieldList.name}
                    field={fieldList}
                    platform={socialPlatform}
                    data={linkedInData}
                  />
                ),
                pinterest: (
                  <Pinterest
                    fieldName={fieldList.name}
                    field={fieldList}
                    platform={socialPlatform}
                    data={responseData}
                    boards={pinterestBoards}
                    fetchSectionData={fetchSectionData}
                    noSection={noSection}
                  />
                ),
              }[socialPlatform]
            }
            <button
              className="wpsp-modal-save-close-button"
              type="submit"
              onClick={closeModal}
            >
              {__("Save", "wp-scheduled-posts")}
            </button>
          </React.Fragment>
        )}
      </Modal>
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return {
    close_redirect_popup: bindActionCreators(close_redirect_popup, dispatch),
  };
};

export default connect(null, mapDispatchToProps)(SocialProfile);
