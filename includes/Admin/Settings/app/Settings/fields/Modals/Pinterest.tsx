import React, { useState,useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";

export default function Pinterest({ platform, data, boards,fetchSectionData,noSection,addProfileToggle,savedProfile }) {
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
            {data.map( ( item, index ) => (
              <div className="profile-info">
                <img width={'40px'} src={item?.thumbnail_url} alt={item?.name} />
                <h3>{item?.name}</h3>
                <ul>
                  {boardOptions.map((board, board_index) => (
                    <li key={board_index}>
                      <div className="item-content">
                        <h4 className="entry-title">{board?.label}</h4>
                        <div className="control pinterest-select">
                          <ReactSelect
                            value={defaultSection}
                            onMenuOpen={() =>
                              fetchSectionData(
                                board?.value,
                                item,
                                setSectionOptions
                              )
                            }
                            onChange={setDefaultSection}
                            options={sectionOptions}
                          />
                        </div>
                        <input
                            type='checkbox'
                            onChange={ (event) => {
                              addProfileToggle(
                                item,
                                defaultBoard,
                                defaultSection,
                                event,
                                board,
                              )
                              }
                            }
                        />
                      </div>
                    </li>
                  ))}
                <button
                  type="submit"
                  className="wpsp-modal-save-account"
                  onClick={(event) => {
                    event.preventDefault();
                    savedProfile(event)
                  }}
                  >{ __( 'Save','wp-scheduled-posts' ) }</button>
                  </ul>
              </div>
            ) )}
            
          </div>
        </>
    )
}
