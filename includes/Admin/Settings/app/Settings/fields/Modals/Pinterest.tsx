import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { default as ReactSelect } from "react-select";
import { selectStyles } from '../../helper/styles';
import PinterestSectionSelect from '../utils/PinterestSectionSelect';

export default function Pinterest({ platform, data, boards,fetchSectionData,noSection,addProfileToggle,savedProfile,singlePinterestBoard,setProfileEditModal }) {
    let boardOptions = boards?.map((board) => {
      return {
        label: board.name || board.id,
        value: board.id,
      };
    });
    
    const [defaultSection, setDefaultSection] = useState(noSection ? [noSection] : []);
    const [editDefaultSection,setEditDefaultSection]  = useState( singlePinterestBoard?.defaultSection ?? [] );
    const [sectionOptions, setSectionOptions] = useState([noSection]);
    const [singleBoardOptions, setSingleBoardOptions] = useState([]);
    const [isSectionUpdate,setIsSectionUpdate] = useState(false);

    useEffect( () => {
      if( !setProfileEditModal ) {
        setIsSectionUpdate(false);
      }
    },[setProfileEditModal] )
    useEffect( () => {
      if( singlePinterestBoard ) {
        setSingleBoardOptions([singlePinterestBoard?.default_board_name]);
        setEditDefaultSection([singlePinterestBoard?.defaultSection]);
      }
    },[singlePinterestBoard] );
    let error_message = '';
    if( boardOptions.length <= 0 ) {
        error_message = __('It seems that there are no Pinterest Board associated to your Pinterest profile.','wp-scheduled-posts');
    }
    return (
        <>
          <div className="wpsp-modal-social-platform">
            { !singlePinterestBoard && data?.map ( ( item, index ) => (
              <div className='pinterest-model-wrapper'>
                <div className='author-details'>
                  <img src={item?.thumbnail_url} alt={item?.name} />
                  <h3>{item?.name}</h3>
                </div>
                <div className="profile-info">
                  { boardOptions.length > 0 &&
                    <ul>
                      {boardOptions.map((board, board_index) => {
                        return (<li key={board_index}>
                          <div className="item-content">
                            <h4 className="entry-title">{board?.label}</h4>
                            <div className="control pinterest-select">
                              <PinterestSectionSelect
                                noSection={noSection}
                                fetchSectionData={fetchSectionData}
                                board={board}
                                item={item}
                                setSectionOptions={setSectionOptions}
                                sectionOptions={sectionOptions}
                                setBoardDefaultSection={setDefaultSection}
                                prevDefaultSection={defaultSection}
                              />
                            </div>
                            <input
                                type='checkbox'
                                onChange={ (event) => {
                                  let selectedBoardSection = defaultSection.find((item) => item.board === board?.value)
                                  addProfileToggle(
                                    item,
                                    selectedBoardSection,
                                    event,
                                    board,
                                  )
                                  }
                                }
                            />
                          </div>
                        </li>);
                      })}
                  </ul>
                  }
                  { boardOptions.length <= 0 &&
                    <span className='profile-not-found-message'>{ error_message }</span>
                  }
                  <button
                      type="submit"
                      className="wpsp-modal-save-account"
                      onClick={(event) => {
                        savedProfile(event)
                      }}
                    >{ __( 'Save','wp-scheduled-posts' ) }</button>
                </div>
              </div>
            ) )}
            { singlePinterestBoard && (
              <div className="profile-info">
                <div className='author-details'>
                  <img src={singlePinterestBoard?.thumbnail_url} alt={singlePinterestBoard?.name} />
                  <h3>{singlePinterestBoard?.name}</h3>
                </div>
                { singleBoardOptions.length > 0 &&
                 <ul>
                  {singleBoardOptions?.map((board, board_index) => (
                    <li key={board_index}>
                      <div className="item-content">
                        <h4 className="entry-title">{board?.label}</h4>
                        <div className="control pinterest-select">
                          <ReactSelect
                            value={ editDefaultSection }
                            onMenuOpen={() =>
                              fetchSectionData(
                                board?.value,
                                singlePinterestBoard,
                                setSectionOptions
                              )
                            }
                            styles={selectStyles}
                            className='main-select'
                            onChange={ (event) => {
                                setEditDefaultSection(event)
                                setIsSectionUpdate(true);
                              }
                            }
                            options={sectionOptions}
                          />
                        </div>
                      </div>
                    </li>
                  ))}
                <button
                  type="submit"
                  className="wpsp-modal-save-account"
                  onClick={(event) => {
                      if( isSectionUpdate ) {
                        savedProfile(event)
                        addProfileToggle(
                          singlePinterestBoard,
                          editDefaultSection,
                          'save-edit',
                          singlePinterestBoard?.default_board_name,
                        )
                      }else{
                        setProfileEditModal(false);
                      }
                    }}
                  >{ __( 'Save','wp-scheduled-posts' ) }</button>
                  </ul>
                  }
              </div>
            ) }
          </div>
        </>
    )
}
