import React, { useState, useEffect } from 'react'
import CreatableSelect2 from 'react-select/creatable'
import { useField } from 'formik'

const CreatableSelect = ({
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
        setOptionsData(
            Object.entries(options).map(([key, value]) => ({
                value: key,
                label: value,
            }))
        )
    }, [])

    const onChange = (option, actionMeta) => {
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
                    setOptionsData(
                        Object.entries(response.data).map(([key, value]) => ({
                            value: key,
                            label: value,
                        }))
                    )
                }
            })
        }
    }
    const getValue = () => {
        return Object.entries(field.value).map(([key, value]) => ({
            value: value,
            label: value,
        }))
    }
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <div className='select-field'>
                    <CreatableSelect2
                        isClearable
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

export default CreatableSelect
