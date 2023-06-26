import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'

export default function Pinterest({ platform, data, addProfileToggle }) {
   
    return (
        <>
          <div className="wpsp-modal-social-platform">
            <ul>
              {data.map((item, index) => (
                <li key={index}>
                  <div className="item-content">
                    <div className="entry-thumbnail">
                      <img src={item.thumbnail_url} alt="logo" />
                    </div>
                    <h4 className="entry-title">{item.name}</h4>
                    <div className="control pinterest-select">
                      
                    </div>
                    <div className="control pinterest-select">
                      
                    </div>
                    <div className="control">
                      
                      <div></div>
                    </div>
                    
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </>
    )
}
