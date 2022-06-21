import React, { useCallback, useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { wpspGetPluginRootURI, wpspSettingsGlobal } from './../../utils/helper'
import Select2 from 'react-select'

export default function ListItemProfile({
    item,
    arrayHelpers,
    fetchSectionData,
    groupFieldStatus,
    fieldList,
    index,
    noSection,
}) {
    const [showItemControl, setItemControl] = useState(false);
    const [defaultBoard, setDefaultBoard] = useState(item.default_board_name);
    const [defaultSection, setDefaultSection] = useState(item.defaultSection || noSection);
  const [sectionOptions, setSectionOptions] = useState([noSection]);

  const options = item.boards?.map(board => {
    return {
        label: board.name || board.id,
        value: board.id,
    };
})
useEffect(() => {
    arrayHelpers.replace(
        index,
        {
            ...item,
            default_board_name: defaultBoard || null,
        }
    );
}, [defaultBoard]);
useEffect(() => {
    arrayHelpers.replace(
        index,
        {
            ...item,
            defaultSection: defaultSection || null,
        }
    );
}, [defaultSection]);



return (
    <React.Fragment>
      <div className="wpscp-social-tab__item-list__single_item" key={index}>
        <div className="entry-thumbnail">
          <img src={item.thumbnail_url} alt="icon" />
        </div>
        <div className="entry-content">
          <div
            style={{
              display: "flex",
              flexDirection: "column",
              justifyContent: "center",
            }}
          >
            <h4 className="entry-content__title">{item.name}</h4>
            <p className="entry-content__doc">
              <strong style={{ marginLeft: 0 }}>{item.added_by}</strong> on{" "}
              {item.added_date}
            </p>
          </div>
          {item.boards && (
            <div className="entry-content-wrap">
              <div className="entry-content__doc">
                <strong>Default Board:</strong>
                <Select2
                  value={defaultBoard}
                  // onMenuOpen={() => fetchData()}
                  onChange={setDefaultBoard}
                  options={options}
                />
              </div>
              <div className="entry-content__doc">
                <strong>Default Section:</strong>
                <Select2
                  value={defaultSection}
                  onMenuOpen={() =>
                    fetchSectionData(
                      defaultBoard?.value,
                      item,
                      setSectionOptions
                    )
                  }
                  onChange={setDefaultSection}
                  options={sectionOptions}
                />
              </div>
            </div>
          )}
        </div>
        <div className="entry-control">
          <div className="checkbox-toggle">
            <input
              type="checkbox"
              checked={groupFieldStatus.value === true ? item.status : false}
              name={`${fieldList.name}.${index}`}
              onChange={(e) => {
                if (groupFieldStatus.value === true) {
                  if (e.target.checked) {
                    const itemTrue = {
                      ...item,
                      status: true,
                    };
                    return arrayHelpers.replace(index, itemTrue);
                  } else {
                    const itemFalse = {
                      ...item,
                      status: false,
                    };
                    return arrayHelpers.replace(index, itemFalse);
                  }
                }
              }}
            />
          </div>
          <div className="entry-control__more-link">
            <button
              type="button"
              className="btn-more-link"
              onClick={() => setItemControl(!showItemControl)}
            >
              <img
                src={wpspGetPluginRootURI + "assets/images/icon-more.png"}
                alt="more item"
              />
            </button>
            {showItemControl && (
              <ul className="entry-control__more-link__group_absolute">
                <li>
                  <button
                    type="button"
                    className="btn btn-remove"
                    onClick={() => {
                      return arrayHelpers.remove(index);
                    }}
                  >
                    {__("Remove", "wp-scheduled-posts")}
                  </button>
                </li>
              </ul>
            )}
          </div>
        </div>
      </div>
    </React.Fragment>
  );
}
