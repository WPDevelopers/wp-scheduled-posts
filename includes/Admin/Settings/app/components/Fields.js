import React from 'react'
import { compareConditionValue } from './../utils/helper'
import Text from './type/Text'
import Textarea from './type/Textarea'
import Checkbox from './type/Checkbox'
import Radio from './type/Radio'
import Email from './type/Email'
import Error from './type/Error'
import Select from './type/Select'

const Fields = (props) => {
    // console.log(Object.is(props.values, props.condition))
    let renderComponent
    if (props.type === 'text') {
        renderComponent = <Text {...props} />
    } else if (props.type === 'email') {
        renderComponent = <Email {...props} />
    } else if (props.type === 'textarea') {
        renderComponent = <Textarea {...props} />
    } else if (props.type === 'checkbox') {
        renderComponent = <Checkbox {...props} />
    } else if (props.type === 'radio') {
        renderComponent = <Radio {...props} />
    } else if (props.type === 'select') {
        renderComponent = <Select {...props} />
    } else {
        renderComponent = <Error {...props} />
    }

    if (
        props.condition !== undefined &&
        compareConditionValue(props.condition, props.values)
    ) {
        return ''
    } else {
        return <div>{renderComponent}</div>
    }
}

export default Fields
