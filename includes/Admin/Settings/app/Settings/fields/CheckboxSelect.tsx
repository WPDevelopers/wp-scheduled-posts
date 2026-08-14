import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActionMeta, default as ReactSelect } from "react-select";
import { findOptionLabelByValue } from "../helper/helper";
import { multiSelectStyles } from "../helper/styles";
import { CheckboxOption, SelectChip, SelectShell } from "../components/MultiSelect";
import { Option } from "./Calendar/types";


export const addAllOption = (options: Option[]) => {
  return [{ label: 'All', value: 'all' }, ...Object.values(options || [])];
};
export const getOptionsFlatten = (options: Option[]) => {
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

const CheckboxSelect = (props) => {
  let { name, multiple, onChange } = props;

  const allOption = useMemo(() => addAllOption(props.option), [props.option]);
  const allOptionFlatten = useMemo(
    () => getOptionsFlatten(allOption),
    [allOption]
  );
  const selectedValue = props.value?.includes('all') ? allOptionFlatten : props.value?.map((item) => {
    return findOptionLabelByValue(allOption, item);
  });

  const [optionSelected, setOptionSelected] = useState(selectedValue ?? []);

  // Add and remove
  const handleChange = (newValue: Option[], actionMeta: ActionMeta<any>) => {
    if (actionMeta.action === "select-option") {
      if (actionMeta.option.value === "all") {
        newValue = allOptionFlatten;
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
        if (
          newValue.length === allOptionFlatten.length - 1
        ) {
          newValue = allOptionFlatten;
        }
      }
    } else if (actionMeta.action === "deselect-option") {
      if (actionMeta.option.value === "all") {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
        // if (newValue.length === 0) {
        //   newValue = allOptionFlatten;
        // }
      }
    }
    setOptionSelected(newValue);
    // onChange(newValue);
  };
  const removeItem = (item) => {
    const updatedItems = optionSelected.filter((i) => i !== item);
    handleChange(updatedItems, {
      action: 'deselect-option',
      option: item,
    });
  };

  useEffect(() => {
    onChange({
      target: {
        type: "checkbox-select",
        name,
        value: optionSelected?.filter((item) => item)?.map((item) => item.value),
        multiple,
      },
    });
  }, [optionSelected]);

  const isTags = useCallback(
    (item) => {
      if (allOptionFlatten.length === optionSelected.length) {
        if (allOptionFlatten.length === 2) {
          return item.value !== "all";
        }
        return item.value === "all";
      }
      return true;
    },
    [allOptionFlatten, optionSelected]
  );

  // `isTags` collapses a full selection down to the single "All" chip.
  const chips = (optionSelected || []).filter(
    (item) => isTags(item) && item?.label
  );

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
        chips={chips.map((item, index) => (
          <SelectChip
            key={item.value ?? index}
            label={item.label}
            onRemove={() => removeItem(item)}
          />
        ))}
      >
        <ReactSelect
          options={allOption}
          styles={multiSelectStyles}
          isMulti
          closeMenuOnSelect={false}
          hideSelectedOptions={false}
          components={{ Option: CheckboxOption }}
          autoFocus={false}
          onChange={handleChange}
          value={optionSelected}
          /* Chips are rendered by the shell so the "All" collapse applies. */
          controlShouldRenderValue={false}
          placeholder={
            chips.length
              ? __("Add another…", "wp-scheduled-posts")
              : __("Select…", "wp-scheduled-posts")
          }
          className="checkbox-select"
          classNamePrefix="checkbox-select"
        />
      </SelectShell>
    </div>
  );
};

export default CheckboxSelect;
