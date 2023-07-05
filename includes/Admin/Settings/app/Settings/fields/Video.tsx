import classNames from 'classnames';
import React from 'react'

const List = (props) => {
  return (
      <div className={classNames('wprf-control', 'wprf-video', `wprf-${props.name}-video`, props?.classes)}>
          <h4>{props?.label}</h4>
          <iframe width={props?.width} height={props?.height} src={props?.url}></iframe>
      </div>
  )
}

export default List;