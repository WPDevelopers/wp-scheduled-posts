import classNames from 'classnames';
import React from 'react'

const List = (props) => {
  
  return (
      <div className={classNames('wprf-control', 'wprf-list', `wprf-${props.name}-list`, props?.classes)}>
          <h4>{props?.label}</h4>
          <ul className='wprf-list-item'>
            {props?.content?.map( (item) => (
                <li key={Math.random()}>
                    <a href={item?.link}>{item?.text}</a>
                </li>
            ) )}
          </ul>
      </div>
  )
}

export default List;