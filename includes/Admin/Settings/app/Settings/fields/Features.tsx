import classNames from 'classnames';
import React from 'react';
import { ButtonLink } from '../components/ui';

const Features = (props) => {
  const { heading, button_text, button_link, options } = props?.content;

  return (
    <div
      className={classNames(
        'wprf-control',
        'wprf-features',
        `wprf-${props.name}-features`,
        props?.classes
      )}>
      <div className="header tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-mb-5">
        <h3 className="tw-flex tw-items-center tw-gap-3 tw-text-xl tw-font-medium tw-text-ink tw-m-0">
          <i className="wpsp-icon wpsp-pro tw-inline-flex tw-h-8 tw-w-8 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-full tw-bg-[#f6e6d7] tw-text-xs tw-text-warning-500" />
          {heading}
        </h3>

        <ButtonLink
          variant="link"
          target="_blank"
          rel="noopener noreferrer"
          href={button_link}
          /* The arrow flips on hover, as it did in the previous design. */
          className="tw-group"
          rightIcon={
            <i className="wpsp-icon wpsp-arrow tw-transition-transform tw-duration-300 group-hover:tw-translate-x-1" />
          }
        >
          {button_text}
        </ButtonLink>
      </div>

      <div className="content tw-grid tw-gap-4 tw-grid-cols-2 xl:tw-grid-cols-4">
        {options?.map((item) => (
          <a
            href={item?.link}
            target="_blank"
            rel="noopener noreferrer"
            key={item?.title}
            className="tw-flex tw-w-full tw-items-center tw-justify-center tw-gap-4 tw-rounded-lg tw-bg-white tw-p-5 tw-no-underline tw-transition-shadow tw-duration-150 hover:tw-shadow-card focus:tw-shadow-none"
          >
            <i className={`wpsp-icon ${item?.icon} tw-text-[32px] tw-text-brand-500`} />
            <h5 className="tw-text-lg tw-font-medium tw-text-ink tw-m-0">
              {item?.title}
            </h5>
          </a>
        ))}
      </div>
    </div>
  );
};

export default Features;
