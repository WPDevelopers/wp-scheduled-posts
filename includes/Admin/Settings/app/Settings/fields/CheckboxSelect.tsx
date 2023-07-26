import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import { default as ReactSelect, components } from 'react-select';
import { selectStyles } from '../helper/styles';

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

const CheckboxSelect = (props) => {
  let { name, multiple, onChange } = props;
  let options = [];
  if (props.option) {
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
        type: 'checkbox-select',
        name,
        value: optionSelected?.map((item) => item.value),
        multiple,
      },
    });
  }, [optionSelected]);

  return (
    <>
      <div
        className={classNames(
          'wprf-control',
          'wprf-control-wrapper',
          'wprf-checkbox-select',
          `wprf-${props.name}-checkbox-select`,
          props?.classes
        )}>
        <div className="wprf-control-label">
          <label htmlFor={`${props?.id}`}>{props?.label}</label>
          <ul className="selected-options">
            {optionSelected?.map((item, index) => (
              <li key={index}>
                {' '}
                {item?.label}{' '}
                <button onClick={() => removeItem(item)}>
                  {' '}
                  <i className={props?.icon_classes}></i>{' '}
                </button>{' '}
              </li>
            ))}
          </ul>
        </div>
        <div className="wprf-checkbox-select-wrap wprf-checked wprf-label-position-right">
          <span
            className="d-inline-block"
            data-toggle="popover"
            data-trigger="focus"
            data-content="Please select account(s)">
            <ReactSelect
              options={options}
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
