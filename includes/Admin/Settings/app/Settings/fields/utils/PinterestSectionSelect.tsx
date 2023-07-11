import React from 'react'
import { __ } from '@wordpress/i18n'
import { default as ReactSelect } from "react-select";

export default function PinterestSectionSelect( { defaultSection,fetchSectionData, board, item} ) {
    
  return (
    <>
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
    </>
  )
}
