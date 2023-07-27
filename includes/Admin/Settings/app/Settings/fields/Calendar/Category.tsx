import React, { useEffect, useState } from "react";
import ReactSelect, { components } from "react-select";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import { __ } from "@wordpress/i18n";
import { selectStyles } from "../../helper/styles";

const CategorySelect = ({ selectedPostType, Option, showTags = false }) => {
  const [categoryOptions, setCategoryOptions] = useState<any[]>([]);
  const [selectedCategories, setSelectedCategories] = useState([]);

  const removeItem = (item) => {
    const updatedItems = categoryOptions.filter((i) => i !== item);
    setCategoryOptions(updatedItems);
  };

  useEffect(() => {
    const taxUrl = addQueryArgs("/wpscp/v1/get_tax_terms", {
      post_type: selectedPostType.map((item) => item.value),
    });

    apiFetch({
      path: taxUrl,
    }).then((data: []) => {
      setCategoryOptions(data);
    });
  }, [selectedPostType]);



  return (
    <>
      
      <div className="wprf-checkbox-select-wrap wprf-checked wprf-label-position-right">
          <ReactSelect
            placeholder={__("Select Category", "wp-scheduled-posts")}
            options={categoryOptions}
            styles={selectStyles}
            value={selectedCategories}
            onChange={(value, actionMeta) => {
                setSelectedCategories([...value]);
            }}
            closeMenuOnSelect={false}
            hideSelectedOptions={false}
            autoFocus={false}
            isMulti
            components={{
              Option,
            }}
            controlShouldRenderValue={false}
            className="main-select"
            classNamePrefix="checkbox-select"

            // onMenuOpen={memoizedCategoryOptions}
          />
        </div>
      {showTags && (
        <div className="selected-options">
          <ul>
            {selectedCategories?.map((item, index) => (
              <li key={index}>
                {" "}
                {item?.label}{" "}
                <button onClick={() => removeItem(item)}>
                  {" "}
                  <i className="wpsp-icon wpsp-close"></i>{" "}
                </button>{" "}
              </li>
            ))}
          </ul>
        </div>
      )}
    </>
  );
};

export default CategorySelect;
