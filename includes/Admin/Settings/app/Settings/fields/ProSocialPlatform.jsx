import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import React from 'react';
import { SweetAlertProMsg, SweetAlertToaster } from '../ToasterMsg';

/**
 * The locked stand-in for a network sold with SchedulePress Pro.
 *
 * Carries none of the platform's code — just its name, logo and a way to buy
 * it. Pro replaces this card wholesale by registering the real one under the
 * same field name, so this only ever renders when nothing is serving the
 * platform.
 */
const ProSocialPlatform = (props) => {
  // @ts-ignore
  const proVersion = wpspSettingsGlobal?.pro_version;
  // @ts-ignore
  const minProVersion = wpspSettingsGlobal?.min_pro_version;

  const openUpgrade = (event) => {
    event?.preventDefault();
    event?.stopPropagation();

    // Pro is running but did not claim this platform, which means it predates
    // the release that owns it. Selling them a licence they already have would
    // be the wrong answer.
    if (proVersion) {
      SweetAlertToaster({
        type: 'error',
        title: sprintf(
          /* translators: %s: minimum required SchedulePress Pro version */
          __(
            'Update SchedulePress Pro to %s or newer to use this platform.',
            'wp-scheduled-posts'
          ),
          minProVersion
        ),
      }).fire();
      return;
    }

    SweetAlertProMsg();
  };

  return (
    <div
      className={classNames(
        'wprf-control',
        'wprf-social-profile',
        'wpsp-pro-social-platform',
        `wprf-${props.name}-social-profile`,
        props?.classes
      )}>
      <div
        className="social-profile-card"
        role="button"
        tabIndex={0}
        onClick={openUpgrade}
        onKeyDown={(event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            openUpgrade(event);
          }
        }}>
        {!proVersion && props?.badge && (
          <img className="wpsppro-icon" src={props.badge} alt={__('Pro feature', 'wp-scheduled-posts')} />
        )}

        <div className="main-profile">
          <div>
            <div className="card-header">
              <div className="heading">
                <img width={'30px'} src={props?.logo} alt={props?.label} />
                <h5>{props?.label}</h5>
              </div>
              <div className="status">
                <div className="switcher">
                  <input
                    id={props?.id}
                    type="checkbox"
                    checked={false}
                    readOnly
                    className="wprf-switcher-checkbox"
                  />
                  <label className="wprf-switcher-label" htmlFor={props?.id}>
                    <span className="wprf-switcher-button" />
                  </label>
                </div>
              </div>
            </div>
            <div className="card-content">
              <p dangerouslySetInnerHTML={{ __html: props?.desc }} />
            </div>
            <div className="card-footer">
              <button type="button" onClick={openUpgrade}>
                {__('Add New', 'wp-scheduled-posts')}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProSocialPlatform;
