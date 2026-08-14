import classNames from 'classnames';
import React from 'react'

/** A titled list of documentation links. */
const List = (props) => {
  const items = props?.content || [];

  if (!items.length) {
    return null;
  }

  return (
      <div className={classNames('wprf-control', 'wprf-list', `wprf-${props.name}-list`, props?.classes, 'tw-py-4')}>
          {props?.label && (
            <h4 className="tw-m-0 tw-mb-2 tw-text-base tw-font-semibold tw-text-ink">
              {props.label}
            </h4>
          )}

          <ul className='wprf-list-item tw-m-0 tw-flex tw-list-none tw-flex-col tw-gap-1.5 tw-p-0'>
            {items.map((item, index) => (
                <li key={item?.link || index} className="tw-m-0">
                    <a
                      target={item?.target}
                      href={item?.link}
                      rel="noopener noreferrer"
                      className="tw-inline-flex tw-items-center tw-gap-1.5 tw-text-base tw-text-brand-500 tw-no-underline hover:tw-text-brand-700 hover:tw-underline"
                    >
                      <svg className="tw-h-3.5 tw-w-3.5 tw-shrink-0 tw-opacity-70" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M6 3.5 10.5 8 6 12.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                      </svg>
                      {item?.text}
                    </a>
                </li>
            ))}
          </ul>
      </div>
  )
}

export default List;
