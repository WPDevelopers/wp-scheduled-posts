import React,{useState} from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";

export default function PinterestSectionSelect( { noSection,fetchSectionData, board, item, setSectionOptions,sectionOptions } ) {
    const [defaultSection, setDefaultSection] = useState(noSection);
    return (
        <ReactSelect
            value={noSection}
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
            } }
            options={sectionOptions}
        />
    )
}
