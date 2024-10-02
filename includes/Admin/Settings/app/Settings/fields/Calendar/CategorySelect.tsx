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
    } else if (actionMeta.action === 'deselect-option') {
      if (actionMeta.option.value === 'all') {
        newValue = [];
      } else {
        newValue = newValue.filter((item) => item.value !== 'all');
      }
    }
    onChange(newValue);
  };

  // Handle removing an item from selection
  const removeItem = (item) => {
    const updatedItems = value.filter((i) => i !== item);
    handleChange(updatedItems, {
      action: 'deselect-option',
      option: item,
    });
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
    <>
      <ReactSelect
        {...rest}
        options={displayedOptions} // Use displayed options for infinite scroll
        value={value}
        onChange={handleChange}
        components={{
          Option,
        }}
        styles={selectStyles}
        closeMenuOnSelect={false}
        hideSelectedOptions={false}
        autoFocus={false}
        controlShouldRenderValue={false}
        className="checkbox-select"
        classNamePrefix="checkbox-select"
        isMulti
        onMenuScrollToBottom={loadMoreOptions} // Trigger load more on scroll
      />
        {showTags && (
            <div className="selected-options">
                <ul>
                {value?.some((item) => item.value === 'all') ? (
                    <li>
                    All
                    <button onClick={() => !rest.isDisabled && removeItem({ value: 'all', label: 'All' })}>
                        <i className="wpsp-icon wpsp-close"></i>
                    </button>
                    </li>
                ) : (
                    value
                    ?.filter((item) => item.value !== 'all') // Ensure "All" is not shown in tags when others are selected
                    .map((item, index) => (
                        <li key={index}>
                        {item?.label}
                        <button onClick={() => !rest.isDisabled && removeItem(item)}>
                            <i className="wpsp-icon wpsp-close"></i>
                        </button>
                        </li>
                    ))
                )}
                </ul>
            </div>
        )}
    </>
  );
};

export default CategorySelectWrapper;
