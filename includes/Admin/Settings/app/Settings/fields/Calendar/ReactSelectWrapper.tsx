import React, { useCallback, useMemo } from 'react';
import ReactSelect, { ActionMeta, components } from 'react-select';
import { selectStyles } from '../../helper/styles';
import { Option, SelectWrapperProps } from './types';

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

export const addAllOption = (options: Option[]) => {
  return [{ label: 'All', value: 'all' }, ...Object.values(options || [])];
};

const ReactSelectWrapper: React.FC<SelectWrapperProps> = ({
  options,
  value,
  onChange,
  showTags = false,
  ...rest
}) => {
  const allOption = useMemo(() => addAllOption(options), [options]);
  const allOptionFlatten = useMemo(
    () => getOptionsFlatten(allOption),
    [allOption]
  );
  // const [selectedPostType, setSelectedPostType] =
  //   useState<MultiValue<Option>>(allOptionFlatten);

  const Option = (props) => {
    return (
      <div className="checkbox-select-menu-list-item">
        <components.Option {...props}>
          <span>{props.label}</span>
        </components.Option>
      </div>
    );
  };

  /**
   * Selecting everything stores the "All" option *and* every real one, so the
   * chips would otherwise read "All, Posts, Pages, …". Only "All" is worth
   * showing — unless it is the only real option there is.
   */
  const isTags = useCallback(
    (item) => {
      if (allOptionFlatten.length === value.length) {
        if (allOptionFlatten.length === 2) {
          return item.value !== 'all';
        }
        return item.value === 'all';
      }
      return true;
    },
    [allOptionFlatten, value]
  );

  /** Chips live in the control; this hides the ones `isTags` rules out. */
  const MultiValue = (props) =>
    isTags(props.data) ? <components.MultiValue {...props} /> : null;

  // Add and remove
  const handleChange = (newValue: Option[], actionMeta: ActionMeta<any>) => {
    if (actionMeta.action === 'select-option') {
      if (actionMeta.option.value === 'all') {
        newValue = allOptionFlatten;
      } else {
        newValue = newValue.filter((item) => item.value !== 'all');
        if (
          newValue.length === Object.values(getOptionsFlatten(options)).length
        ) {
          newValue = allOptionFlatten;
        }
      }
    } else if (
      actionMeta.action === 'deselect-option' ||
      /* A chip's × and backspace both remove a value without going through
         the menu, so they report their own actions. */
      actionMeta.action === 'remove-value' ||
      actionMeta.action === 'pop-value'
    ) {
      const removed = actionMeta.option || actionMeta.removedValue;

      if (removed?.value === 'all') {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== 'all');
      }
    }
    onChange(newValue);
  };

  return (
    <ReactSelect
      {...rest}
      options={allOption}
      value={value}
      onChange={handleChange}
      components={{
        Option,
        MultiValue,
      }}
      styles={selectStyles}
      closeMenuOnSelect={false}
      hideSelectedOptions={false}
      autoFocus={false}
      /* The selection belongs in the field, not in a list under it. */
      controlShouldRenderValue={showTags}
      className="checkbox-select"
      classNamePrefix="checkbox-select"
      isMulti
    />
  );
};

export default ReactSelectWrapper;
