import classNames from 'classnames';
import React from 'react';

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
      <div className="header">
        <h3>{heading}</h3>
        <a target='_blank' href={button_link}>{button_text}</a>
      </div>
      <div className="content">
        {options?.map((item) => (
          <a
            href={item?.link}
            target="_blank"
            key={Math.random()}>
            <div className="single-content">
              <i className={`wpsp-icon ${item?.icon}`}></i>
              <h5>{item?.title}</h5>
            </div>
          </a>
        ))}
      </div>
    </div>
  );
};

export default Features;
