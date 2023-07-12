import React,{useState} from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";

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
                noSection(event)
                setBoardDefaultSection(event)
            } }
            options={sectionOptions}
        />
    )
}