import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActionMeta, default as ReactSelect } from "react-select";
import { fetchCategories, findOptionLabelByValue } from "../helper/helper";
import { multiSelectStyles } from "../helper/styles";
import { CheckboxOption, SelectChip, SelectShell } from "../components/MultiSelect";
import apiFetch from '@wordpress/api-fetch';

// Individual categories are dimmed while "All" is selected — picking them
// would have no effect.
const Option = (props) => {
    const isAllSelected = props.selectProps.value.some((selected) => selected.value === 'all');
    const isBlurred = isAllSelected && props.data.value !== 'all';

    return (
        <div className={classNames({ "tw-opacity-40 tw-pointer-events-none": isBlurred })}>
            <CheckboxOption {...props} />
        </div>
    );
};

// Helper functions
export const addAllOption = (options, page) => {
    if( page == 1 ) {
        return [{ label: 'All', value: 'all' }, ...Object.values(options || [])];
    }
    return [...Object.values(options || [])];
};

export const getOptionsFlatten = (options) => {
  const optionsArray = [];
  options.forEach((category) => {
    if (category.options) {
      optionsArray.push(...category.options);
    } else {
      optionsArray.push(category);
    }
  });
  return optionsArray;
};

const CheckboxSelectAsync = (props) => {
    const { name, multiple, onChange } = props;
    const [displayedOptions, setDisplayedOptions] = useState([]); // Paginated options
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const options = props?.value.map((item) => ({
        value: item,
        label: item == 'all' ? 'All' : item,
    }));

    const [optionSelected, setOptionSelected] = useState(options ?? []);

  // Function to handle selection and deselection
  const handleChange = (newValue, actionMeta: ActionMeta<any>) => {
    if (actionMeta.action === "select-option") {
      if (actionMeta.option.value === "all") {
        newValue = [{ label: 'All', value: 'all' }];
      } else {
        // Ensure 'All' is not part of the selection when other items are selected
        newValue = newValue.filter((item) => item.value !== "all");
      }
    } else if (actionMeta.action === "deselect-option") {
      if (actionMeta.option.value === "all") {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
      }
    }
    setOptionSelected(newValue);
  };

  // Remove an item from selection
  const removeItem = (item) => {
    const updatedItems = optionSelected.filter((i) => i !== item);
    handleChange(updatedItems, {
      action: "deselect-option",
      option: item,
    });
  };

  // Function to fetch categories from the server
  const fetchOptions = async (page) => {
    try {
      setLoading(true);
      const data = {
        page: page,
        limit : 10,
      }
      let response = await fetchCategories(data);
      response = addAllOption(response, page);
      response = getOptionsFlatten(response);
    //   @ts-ignore 
      setDisplayedOptions((prevOptions) => [...prevOptions, ...response]);
    } catch (error) {
      console.error("Failed to load options:", error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch the first set of categories on mount
  useEffect(() => {
    fetchOptions(1); // Initial load with page 1
  }, []);

  // Handle scroll event to load more categories when reaching the bottom
  const loadMoreOptions = () => {
    const isAllSelected = optionSelected.some((selected) => selected.value === 'all');
    if (isAllSelected || loading) {
      return;
    }
    setPage((prevPage) => prevPage + 1);
  };

  // Fetch new categories when the page state changes (pagination)
  useEffect(() => {
    if (page > 1) {
      fetchOptions(page);
    }
  }, [page]);

    useEffect(() => {
        onChange({
        target: {
            type: "checkbox-select",
            name,
            value: optionSelected
            ?.filter(item => item && item.value) // Ensure item is valid and has a 'value'
            .map((item) => item.value),
            multiple,
        },
        });
    }, [optionSelected]);
    
  const chips = (optionSelected || []).filter((item) => item && item.label);

  return (
    <div
      className={classNames(
        "wprf-control",
        "wprf-control-wrapper",
        "wprf-checkbox-select",
        `wprf-${props.name}-checkbox-select`,
        props.classes
      )}
    >
      {/* The label is supplied by the settings renderer, not here. */}
      <SelectShell
        className={loading ? 'wpsp-checkbox-async-loading' : ''}
        chips={chips.map((item, index) => (
          <SelectChip
            key={item.value ?? index}
            label={item.label}
            onRemove={() => removeItem(item)}
          />
        ))}
      >
        <ReactSelect
          options={displayedOptions}
          styles={multiSelectStyles}
          isMulti
          closeMenuOnSelect={false}
          hideSelectedOptions={false}
          components={{ Option }}
          onChange={handleChange}
          value={optionSelected}
          /* Chips are rendered by the shell. */
          controlShouldRenderValue={false}
          isLoading={loading}
          placeholder={
            chips.length
              ? __("Add another…", "wp-scheduled-posts")
              : __("Select…", "wp-scheduled-posts")
          }
          className="checkbox-select"
          classNamePrefix="checkbox-select"
          onMenuScrollToBottom={loadMoreOptions}
        />
      </SelectShell>
    </div>
  );
};

export default CheckboxSelectAsync;
