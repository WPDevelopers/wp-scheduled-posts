import React, { useState,useEffect } from "react";
import { default as ReactSelect } from "react-select";
import { components } from "react-select";
import classNames from 'classnames';
import { selectStyles } from '../helper/styles';

// Prepare options with checkbox
const Option = (props) => {
  return (
    <div>
      <components.Option {...props}>
        <input
          type="checkbox"
          checked={props.isSelected}
          onChange={() => null}
          id="wpsp-checkbox-select"
        />{" "}
        {props.label}
      </components.Option>
    </div>
  );
};

const CheckboxSelect = (props) => {
  let { name, multiple, onChange } = props;
  let options = [];
  if ( props.option ) {
    options = Object.entries(props?.option)?.map(([key, value]) => ({
      //@ts-ignore
      value: value?.value, 
      //@ts-ignore
      label: value?.label,
      key: key.toString(),
    }));
  }
  
  const selectedValue = props?.value?.map((item) => ({
    value: item,
    label: options.find((option) => option.value == item)?.label,
  }));
  const [optionSelected, setOptionSelected] = useState(selectedValue);

  // Add and remove
  const handleChange = (selected) => {
    setOptionSelected(selected);
  };
  const removeItem = (item) => {
    const updatedItems = optionSelected.filter((i) => i !== item);
    setOptionSelected(updatedItems);
  };

  useEffect(() => {
		onChange({
			target: {
				type: "checkbox-select",
				name,
				value: optionSelected?.map((item) => item.value),
				multiple,
			},
		});
	}, [optionSelected]);

  return (
    <>
        <div className={classNames('wprf-control','wprf-control-wrapper', 'wprf-checkbox-select', `wprf-${props.name}-checkbox-select`, props?.classes)}>
            <div className="wprf-control-label">
                <label htmlFor={`${props?.id}`}>{props?.label}</label>
                <div className="selected-options">
                    <ul>
                      { optionSelected?.map( (item, index) => (
                        <li key={index}> { item?.label } <button onClick={() => removeItem(item)}> <i className={props?.icon_classes}></i> </button> </li>
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
                    options={options}
                    styles={selectStyles}
                    isMulti
                    closeMenuOnSelect={false}
                    hideSelectedOptions={false}
                    components={{
                      Option
                    }}
                    autoFocus={false}
                    onChange={handleChange}
                    value={optionSelected}
                    controlShouldRenderValue={false}
                    className="checkbox-select"
                  />
                </span>
            </div>
        </div>        
    </>
  );
};

export default CheckboxSelect;