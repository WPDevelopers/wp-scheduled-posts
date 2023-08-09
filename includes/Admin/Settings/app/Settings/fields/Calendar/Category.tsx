import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";
import React, { useEffect, useState } from "react";
import ReactSelectWrapper, { addAllOption, getOptionsFlatten } from "./ReactSelectWrapper";
import { Option } from "./types";

const CategorySelect = ({ selectedPostType, onChange, showTags = false }) => {
  const [categoryOptions, setCategoryOptions] = useState<Option[]>([]);
  const [selectedCategories, setSelectedCategories] = useState<Option[]>([]);

  useEffect(() => {
    const taxUrl = addQueryArgs("/wpscp/v1/get_tax_terms", {
      post_type: selectedPostType.map((item) => item.value),
    });

    apiFetch({
      path: taxUrl,
    }).then((data: []) => {
      setCategoryOptions(data);
      setSelectedCategories(addAllOption(getOptionsFlatten(data)));
    });
  }, [selectedPostType]);
  console.log('category-options-from-calender',categoryOptions);
  
  return (
    <>
      <ReactSelectWrapper
        options={categoryOptions}
        value={selectedCategories}
        onChange={(value) => {
            setSelectedCategories([...value]);
            onChange([...value]);
        }}
        placeholder={__("Select Category", "wp-scheduled-posts")}
        showTags={showTags}
      />
    </>
  );
};

export default CategorySelect;
