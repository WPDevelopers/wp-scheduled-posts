import React, { useCallback, useEffect, useMemo, useState } from 'react';
import ReactSelect, { ActionMeta, components } from 'react-select';
import { selectStyles } from '../../helper/styles';
import { Option, SelectWrapperProps } from './types';
import { fetchCategories } from '../../helper/helper'; // Assuming this fetches paginated data
import classNames from 'classnames';



// Prepare options with checkbox
const Option = (props) => {
  const isAllSelected = props.selectProps.value.some((selected) => selected.value === 'all');
  return (
      <div
      className={classNames(
          "checkbox-select-menu-list-item",
          { "blur-item": isAllSelected && props.data.value !== 'all' }
      )}
      >
      <components.Option {...props}>
          <span>{props.label}</span>
      </components.Option>
      </div>
  );
};

// Helper function to flatten the options structure
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

// Helper function to add the "All" option
export const addAllOption = (options: Option[], page) => {
    if( page == 1 ) {
        return [{ label: 'All', value: 'all' }, ...Object.values(options || [])];
    }
    return [...Object.values(options || [])];
};

const CategorySelectWrapper: React.FC<SelectWrapperProps> = ({
  options,
  value,
  onChange,
  showTags = false,
  ...rest
}) => {
  const [displayedOptions, setDisplayedOptions] = useState<Option[]>([]); // Track options for infinite scroll
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);

  // Memoize the allOption and flattened structure
  const allOption = useMemo(() => addAllOption(options, page), [options]);
  const allOptionFlatten = useMemo(() => getOptionsFlatten(allOption), [allOption]);

  // Load initial options on component mount
  useEffect(() => {
    fetchMoreOptions(1); // Load the first page
  }, []);

  // Helper to fetch paginated options
  const fetchMoreOptions = async (currentPage: number) => {
    setLoading(true);
    try {
      const response = await fetchCategories({ page: currentPage, limit: 10 }); // Assuming this fetches paginated data
      // @ts-ignore  
      const newOptions = addAllOption(response, currentPage);
      const flattenedOptions = getOptionsFlatten(newOptions);
      setDisplayedOptions((prevOptions) => [...prevOptions, ...flattenedOptions]); // Append new options
    } catch (error) {
      console.error('Failed to fetch more options:', error);
    } finally {
      setLoading(false);
    }
  };

  // Handle selection changes, including "all" option logic
  const handleChange = (newValue: Option[], actionMeta: ActionMeta<any>) => {
    if (actionMeta.action === 'select-option') {
      if (actionMeta.option.value === 'all') {
        newValue = allOptionFlatten;
      } else {
        newValue = newValue.filter((item) => item.value !== 'all');
        if (newValue.length === Object.values(getOptionsFlatten(options)).length) {
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

  /**
   * Chips live in the control. "All" is stored alongside every real category,
   * so only one of the two ever earns a chip.
   */
  const MultiValue = (props) => {
    const hasAll = !!value?.some((item) => item.value === 'all');
    const isAll = props.data.value === 'all';

    // With "All" selected only its own chip shows; otherwise only the rest do.
    return hasAll === isAll ? <components.MultiValue {...props} /> : null;
  };

  // Infinite scroll logic - load more options when user scrolls to the bottom
  const loadMoreOptions = () => {
    const isAllSelected = value.some((selected) => selected.value === 'all');
    if (!isAllSelected && !loading) {
      setPage((prevPage) => prevPage + 1); // Increment page number to load more options
    }
  };

  // Fetch more options when page changes
  useEffect(() => {
    if (page > 1) {
      fetchMoreOptions(page); // Fetch more options when page number changes
    }
  }, [page]);

  return (
    <ReactSelect
      {...rest}
      options={displayedOptions} // Use displayed options for infinite scroll
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
      onMenuScrollToBottom={loadMoreOptions} // Trigger load more on scroll
    />
  );
};

export default CategorySelectWrapper;
