import React, { useState,useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";
import PinterestSectionSelect from '../utils/PinterestSectionSelect';
import { selectStyles } from '../../helper/styles';

export default function Pinterest({ platform, data, boards,fetchSectionData,noSection,addProfileToggle,savedProfile,singlePinterestBoard }) {
    let boardOptions = boards?.map((board) => {
      return {
        label: board.name || board.id,
        value: board.id,
      };
    });
    const [defaultBoard, setDefaultBoard] = useState();
    const [defaultSection, setDefaultSection] = useState(noSection);
    const [sectionOptions, setSectionOptions] = useState([noSection]);
    const [singleBoardOptions, setSingleBoardOptions] = useState([]);

    useEffect(() => {
      if( defaultBoard ) {
        setDefaultSection(noSection);
      }
    }, [defaultBoard]);
    
    useEffect(() => {
      if( boards ) {
        setDefaultBoard(boardOptions?.[0]);
      }
    }, [boards]);

    useEffect( () => {
      if( singlePinterestBoard ) {
        setSingleBoardOptions([singlePinterestBoard?.default_board_name]);
        setDefaultSection([singlePinterestBoard?.defaultSection]);
      }
    },[singlePinterestBoard] );
    return (
        <>
          <div className="wpsp-modal-social-platform">
            { !singlePinterestBoard && data?.map ( ( item, index ) => (
              <div className="profile-info">
                <img width={'40px'} src={item?.thumbnail_url} alt={item?.name} />
                <h3>{item?.name}</h3>
                <ul>
                  {boardOptions.map((board, board_index) => {
                    console.log(board); 
                    return (<li key={board_index}>
                      <div className="item-content">
                        <h4 className="entry-title">{board?.label}</h4>
                        <div className="control pinterest-select">
                          {/* <ReactSelect
                            value={defaultSection}
                            onMenuOpen={() =>
                              fetchSectionData(
                                board?.value,
                                item,
                                setSectionOptions
                              )
                            }
                            onChange={ (event) => {
                              setDefaultSection(event)
                            } }
                            options={sectionOptions}
                          /> */}
                          <PinterestSectionSelect
                            noSection={noSection}
                            fetchSectionData={fetchSectionData}
                            board={board}
                            item={item}
                            setSectionOptions={setSectionOptions}
                            sectionOptions={sectionOptions}
                            setBoardDefaultSection={setDefaultSection}
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
                    </li>);
                  })}
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
            { singlePinterestBoard && (
              <div className="profile-info">
                <img width={'40px'} src={singlePinterestBoard?.thumbnail_url} alt={singlePinterestBoard?.name} />
                <h3>{singlePinterestBoard?.name}</h3>
                <ul>
                  {singleBoardOptions?.map((board, board_index) => (
                    <li key={board_index}>
                      <div className="item-content">
                        <h4 className="entry-title">{board?.label}</h4>
                        <div className="control pinterest-select">
                          <ReactSelect
                            value={defaultSection}
                            onMenuOpen={() =>
                              fetchSectionData(
                                board?.value,
                                singlePinterestBoard,
                                setSectionOptions
                              )
                            }
                            styles={selectStyles}
                            onChange={setDefaultSection}
                            options={sectionOptions}
                          />
                        </div>
                        <input
                            type='checkbox'
                            onChange={ (event) => {
                              addProfileToggle(
                                singlePinterestBoard,
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
            ) }
            
          </div>
        </>
    )
}
