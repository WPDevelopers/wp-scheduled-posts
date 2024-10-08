import classNames from "classnames";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActionMeta, default as ReactSelect, components } from "react-select";
import { findOptionLabelByValue } from "../helper/helper";
import { selectStyles } from "../helper/styles";
import { Option } from "./Calendar/types";

// Prepare options with checkbox
const Option = (props) => {
  return (
    <div className="checkbox-select-menu-list-item">
      <components.Option {...props}>
        <span>{props.label}</span>
      </components.Option>
    </div>
  );
};


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

  return (
    <>
      <div
        className={classNames(
          "wprf-control",
          "wprf-control-wrapper",
          "wprf-checkbox-select",
          `wprf-${props.name}-checkbox-select`,
          props.classes
        )}
      >
        <div className="wprf-control-label">
          <label htmlFor={`${props.id}`}>{props.label}</label>
          <div className="selected-options">
            <ul>
            {optionSelected &&
              optionSelected
                ?.filter((item) => isTags(item) && item?.label) // Filter items that have a truthy label
                .map((item, index) => (
                  <li key={index}>
                    {" "}
                    {item.label}{" "}
                    <button onClick={() => removeItem(item)}>
                      {" "}
                      <i className="wpsp-icon wpsp-close"></i>{" "}
                    </button>{" "}
                  </li>
              ))}
            </ul>
          </div>
        </div>
        <div className="wprf-checkbox-select-wrap wprf-checked wprf-label-position-right">
          <span
            className="d-inline-block"
            data-toggle="popover"
            data-trigger="focus"
            data-content="Please select account(s)"
          >
            <ReactSelect
              options={allOption}
              styles={selectStyles}
              isMulti
              closeMenuOnSelect={false}
              hideSelectedOptions={false}
              components={{
                Option,
              }}
              autoFocus={false}
              onChange={handleChange}
              value={optionSelected}
              controlShouldRenderValue={false}
              className="checkbox-select"
              classNamePrefix="checkbox-select"
            />
          </span>
        </div>
      </div>
    </>
  );
};

export default CheckboxSelect;
