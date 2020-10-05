import React, { useState, useEffect } from 'react'
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs'
import { Formik, Form } from 'formik'
import fetchWP from './../utils/fetchWP'
import Fields from './../components/Fields'

const Settings = ({ wpObject }) => {
    const [tabIndex, setTabIndex] = useState(1)
    const [formValue, setFormValue] = useState({})
    useEffect(() => {
        getSetting()
    }, [])
    const FETCHWP = new fetchWP({
        restURL: wpObject.api_url,
        restNonce: wpObject.api_nonce,
    })
    const processOkResponse = (json, action) => {
        if (json.success) {
            setFormValue(JSON.parse(json.value))
        } else {
            console.log(`Setting was not ${action}.`, json)
        }
    }

    const getSetting = () => {
        FETCHWP.get('wprs').then(
            (json) => setFormValue(JSON.parse(json.value)),
            (err) => console.log('error', err)
        )
    }
    return (
        <Formik
            enableReinitialize={true}
            initialValues={formValue}
            onSubmit={(values, actions) => {
                FETCHWP.post('wprs', {
                    wprsSetting: JSON.stringify(values, null, 2),
                }).then(
                    (json) => processOkResponse(json, 'saved'),
                    (err) => console.log('error', err)
                )
            }}
        >
            {(props) => {
                return (
                    <form onSubmit={props.handleSubmit}>
                        <Tabs
                            selectedIndex={tabIndex}
                            onSelect={(tabIndex) => setTabIndex(tabIndex)}
                        >
                            <TabList>
                                {wpObject.settings.map((item, index) => (
                                    <Tab key={index}>{item.title}</Tab>
                                ))}
                            </TabList>
                            {wpObject.settings.map((item, index) => (
                                <TabPanel key={index}>
                                    {Object.keys(props.values).length > 0 &&
                                        item.fields.map(
                                            (fieldItem, fieldIndex) => (
                                                <Fields
                                                    {...fieldItem}
                                                    setFieldValue={
                                                        props.setFieldValue
                                                    } // formik
                                                    key={fieldIndex}
                                                    values={props.values}
                                                />
                                            )
                                        )}
                                </TabPanel>
                            ))}
                        </Tabs>
                        <button type='submit' disabled={props.isSubmitting}>
                            Submit
                        </button>
                    </form>
                )
            }}
        </Formik>
    )
}

export default Settings
