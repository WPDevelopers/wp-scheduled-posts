import classNames from 'classnames';
import React from 'react'

/** Embedded walkthrough video. */
const Video = (props) => {
  if (!props?.url) {
    return null;
  }

  return (
      <div className={classNames('wprf-control', 'wprf-video', `wprf-${props.name}-video`, props?.classes, 'tw-py-4')}>
          {props?.label && (
            <h4 className="tw-m-0 tw-mb-3 tw-text-base tw-font-semibold tw-text-ink">
              {props.label}
            </h4>
          )}

          {/* 16:9 via aspect-ratio rather than the fixed pixel size in the
              config, which overflowed narrow layouts. Percentage padding would
              resolve against the parent's width, not this box's. */}
          <div className="tw-aspect-video tw-w-full tw-max-w-[560px] tw-overflow-hidden tw-rounded-lg tw-bg-canvas-sunken">
            <iframe
              src={props.url}
              title={props?.label || 'Video'}
              allowFullScreen
              className="tw-h-full tw-w-full tw-border-0"
            />
          </div>
      </div>
  )
}

export default Video;
