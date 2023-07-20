import React,{useState} from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";
import { selectStyles } from '../../helper/styles';

export default function PinterestSectionSelect( { noSection,fetchSectionData, board, item, setSectionOptions,sectionOptions,setBoardDefaultSection } ) {
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
                setBoardDefaultSection(event)
                
            } }
            styles={selectStyles}
            options={sectionOptions}
            className='main-select'
        />
    )
}