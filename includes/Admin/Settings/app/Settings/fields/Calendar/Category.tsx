import React, { useEffect, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import { __ } from "@wordpress/i18n";
import ReactSelectWrapper, { Option, addAllOption, getOptionsFlatten } from "./ReactSelectWrapper";

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
