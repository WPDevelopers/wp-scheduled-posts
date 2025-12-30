import React, { useContext } from 'react';
import { AppContext } from '../../../context/AppContext';

const Header = () => {
    const { state, dispatch } = useContext(AppContext);
  return (
    <div className="wpsp-components-modal__header">
      <div className="wpsp-components-modal__header-heading-container">
        <h1
          id="wpsp-components-modal-header-1"
          className="wpsp-components-modal__header-heading"
        >
          <span
            className="wpsp-add-social-message-text"
            style={{ display: 'flex', alignItems: 'normal', gap: 8 }}
          >
            Add Social Message

            <a
              className="wpsp-custom-social-doc-link"
              href="https://wpdeveloper.com/docs/use-custom-social-templates/"
              target="_blank"
              rel="noopener noreferrer"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width={28}
                height={28}
                viewBox="0 0 24 24"
                fill="none"
              >
                <rect width={24} height={24} rx={12} fill="#F3F2FF" />
                <path
                  d="M13.166 6.75V9.08333C13.166 9.23804 13.2275 9.38642 13.3369 9.49581C13.4463 9.60521 13.5946 9.66667 13.7493 9.66667H16.0827M13.166 6.75H9.08268C8.77326 6.75 8.47652 6.87292 8.25772 7.09171C8.03893 7.3105 7.91602 7.60725 7.91602 7.91667V16.0833C7.91602 16.3928 8.03893 16.6895 8.25772 16.9083C8.47652 17.1271 8.77326 17.25 9.08268 17.25H14.916C15.2254 17.25 15.5222 17.1271 15.741 16.9083C15.9598 16.6895 16.0827 16.3928 16.0827 16.0833V9.66667M13.166 6.75L16.0827 9.66667M10.2493 14.9167H13.7493M10.2493 12.5833H13.7493"
                  stroke="#6C62FF"
                  strokeWidth="1.1"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </svg>
            </a>

            <span className="wpsp-tooltip-wrapper">
              <div className="wpsp-tooltip-text">
                How to Use Custom Social Templates in SchedulePress.
              </div>
            </span>
          </span>
        </h1>
      </div>

      <div
        data-wp-c16t="true"
        data-wp-component="Spacer"
        className="wpsp-components-spacer -dae--b-aeadc-1t4gtoh e19lxcc00"
      />

      <button
        type="button"
        className="wpsp-components-button is-compact has-icon"
        onClick={() => dispatch({ type: 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL', payload: false })}
        aria-label="Close"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          width={24}
          height={24}
          aria-hidden="true"
          focusable="false"
        >
          <path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z" />
        </svg>
      </button>
    </div>
  );
};

export default Header;
