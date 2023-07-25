import React,{useState} from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";
import { selectStyles } from '../../helper/styles';

export default function PinterestSectionSelect( { noSection,fetchSectionData, board, item, setSectionOptions,sectionOptions,setBoardDefaultSection , prevDefaultSection } ) {
    const [defaultSection, setDefaultSection] = useState(noSection);
    return (
        <ReactSelect
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
                if( prevDefaultSection.length > 0 ) {
                    const existingIndex = prevDefaultSection?.findIndex((item) => item.board === board?.value);
                    if (existingIndex !== -1) {
                        prevDefaultSection.splice(existingIndex, 1);
                    }
                    prevDefaultSection.push({...event,board:board?.value});
                    setBoardDefaultSection(prevDefaultSection)
                }else{
                    prevDefaultSection.push({...event,board:board?.value});
                    setBoardDefaultSection(prevDefaultSection)
                }
                
            } }
            styles={selectStyles}
            options={sectionOptions}
            className='main-select'
        />
    )
}