import React, { useState,useEffect } from 'react'
import { __ } from '@wordpress/i18n'

export default function Pinterest({ platform, data, boards,fetchSectionData,noSection }) {
    const boardOptions = boards?.map((board) => {
      return {
        label: board.name || board.id,
        value: board.id,
      };
    });
    const [defaultBoard, setDefaultBoard] = useState();
    const [defaultSection, setDefaultSection] = useState(noSection);
    const [sectionOptions, setSectionOptions] = useState([noSection]);
  
    useEffect(() => {
      setDefaultSection(noSection);
  }, [defaultBoard]);
  
    useEffect(() => {
      setDefaultBoard(boardOptions?.[0]);
    }, [boards]);
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
