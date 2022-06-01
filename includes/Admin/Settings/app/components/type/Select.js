import React, { useState, useEffect } from 'react'
import { useField } from 'formik'
import Select2 from 'react-select'

const Select = ({
    id,
    title,
    subtitle,
    multiple,
    desc,
    options,
    setFieldValue,
}) => {
    const [isFetchData, setIsFetchData] = useState(false)
    const [optionsData, setOptionsData] = useState([])
    const [field] = useField(id)

    useEffect(() => {
        if (options) {
            if(options?.length){
                setOptionsData(options)
            }
            else{
                setOptionsData(
                    Object.entries(options).map(([key, value]) => ({
                        value: key,
                        label: value,
                    }))
                )
            }
        } else {
            setOptionsData([])
        }
    }, [])

    const onChange = (option) => {
        if (option == null) {
            return setFieldValue(field.name, '')
        }
        return setFieldValue(
            field.name,
            multiple ? option.map((item) => item.value) : option.value
        )
    }

    const fetchData = () => {
        if (!isFetchData) {
            setIsFetchData(true)
            var data = {
                action: 'wpsp_get_select2_field_data',
                _wpnonce: wpspSettingsGlobal.api_nonce,
                type: id,
            }
            jQuery.post(ajaxurl, data, function (response) {
                if (response.success === true) {
                    if(options?.length){
                        setOptionsData(response.data)
                    }
                    else{
                        setOptionsData(
                            Object.entries(response.data).map(([key, value]) => ({
                                value: key,
                                label: value,
                            }))
                        )
                    }
                }
            })
        }
    }

    const getValue = () => {
        if (field.value) {
            return Object.entries(field.value).map(([key, value]) => {
                var label = getLabel(value);
                return {
                    value: value,
                    label: label,
                }
            })
        }
    }

    const getLabel = (value) => {
        if(optionsData?.length){
            let _optionsData = optionsData;
            // checking if this is optgroup
            if(!optionsData[0]?.value){
                _optionsData = [].concat(...optionsData.map(optGroup => optGroup.options));
            }
            const option = _optionsData.find(option => option.value == value);
            return option?.label || value;
        }
        return value;
    }

    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <div className='select-field'>
                    <Select2
                        id={field.id}
                        name={field.name}
                        value={getValue()}
                        onMenuOpen={() => fetchData()}
                        onChange={onChange}
                        options={optionsData}
                        isMulti={multiple === true ? true : false}
                    />
                </div>
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Select
