import React, { useState } from "react";
import ReactSelect, { ActionMeta, MultiValue, components } from "react-select";
import { selectStyles } from "../../helper/styles";

type Option = {
  value: string;
  label: string;
};

type Props = {
  options: Option[];
  onChange: (selectedOption: Option | null) => void;
  value: Option | null;
  showTags: boolean;
};

const ReactSelectWrapper: React.FC<Props> = ({ options, showTags = false, ...rest }) => {
  const allOption = [
    { label: "All", value: "all" },
    ...Object.values(options || []),
  ];
  const [selectedPostType, setSelectedPostType] =
    useState<MultiValue<Option>>(allOption);

  const Option = (props) => {
    return (
      <div>
        <components.Option {...props}>
          <input
            type="checkbox"
            checked={props.isSelected}
            onChange={() => null}
          />{" "}
          <label>{props.label}</label>
        </components.Option>
      </div>
    );
  };

  // Add and remove
  const handleChange = (
    newValue: MultiValue<any>,
    actionMeta: ActionMeta<any>
  ) => {
    console.log(actionMeta, newValue);
    if (actionMeta.action === "select-option") {
      if (actionMeta.option.value === "all") {
        newValue = allOption;
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
        if (newValue.length === Object.values(options).length) {
          newValue = allOption;
        }
      }
    } else if (actionMeta.action === "deselect-option") {
      if (actionMeta.option.value === "all") {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== "all");
        if (newValue.length === 0) {
          newValue = allOption;
        }
      }
    }
    setSelectedPostType(newValue);
  };
  const removeItem = (item) => {
    const updatedItems = selectedPostType.filter((i) => i !== item);
    handleChange(updatedItems, {
      action: "deselect-option",
      option: item,
    });
  };

  return (
    <>
    <ReactSelect
      {...rest}
      options={options}
      value={selectedPostType}
      onChange={handleChange}
      components={{
        Option,
      }}
      styles={selectStyles}
      closeMenuOnSelect={false}
      hideSelectedOptions={false}
      autoFocus={false}
      controlShouldRenderValue={false}
      className="main-select"
      isMulti
    />
    {showTags && (
      <div className="selected-options">
        <ul>
          {selectedPostType?.map((item, index) => (
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

export default ReactSelectWrapper;
