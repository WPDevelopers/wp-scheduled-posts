import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
import { addQueryArgs } from "@wordpress/url";
import React, { useEffect, useRef, useState } from "react";
import { useBuilderContext } from "quickbuilder";
import ReactSelectWrapper, { addAllOption, getOptionsFlatten } from "./ReactSelectWrapper";
import { Option } from "./types";
import CategorySelectWrapper from "./CategorySelect";

const CategorySelect = ({ selectedPostType, onChange, showTags = false }) => {
  const [categoryOptions, setCategoryOptions] = useState<Option[]>([]);
  const [selectedCategories, setSelectedCategories] = useState<Option[]>([]);
  const builderContext = useBuilderContext();
  const presetApplied = useRef(false);

  useEffect(() => {
    const taxUrl = addQueryArgs("/wpscp/v1/get_tax_terms", {
      post_type: selectedPostType.map((item) => item.value),
    });

    apiFetch({
      path: taxUrl,
    }).then((data: []) => {
      setCategoryOptions(data);
      // Start on the categories picked in Settings → "Show Categories" (66496).
      // @ts-ignore The post-type calendars (Posts → Calendar) get it from the page instead.
      const allowed: string[] = builderContext?.values?.allow_categories ?? window.wpspSettingsGlobal?.allow_categories ?? [];
      const preset = allowed.includes("all") ? [] : getOptionsFlatten(data).filter((option) => allowed.includes(option.value));
      if (preset.length) {
        presetApplied.current = true;
        setSelectedCategories(preset);
        onChange(preset);
        return;
      }
      setSelectedCategories(addAllOption(data));
      // Don't keep filtering by a preset this post type doesn't have.
      if (presetApplied.current) {
        presetApplied.current = false;
        onChange(addAllOption(data));
      }
    });
  }, [selectedPostType]);  
  
  return (
    <>
      <CategorySelectWrapper
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
