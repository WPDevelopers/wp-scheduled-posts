import React, { useState, useEffect } from 'react'
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs'
import { Formik, Form } from 'formik'
import { ToastContainer, toast } from 'react-toastify'
import fetchWP from './../utils/fetchWP'
import Fields from './../components/Fields'

const Settings = ({ wpspObject }) => {
    const [tabIndex, setTabIndex] = useState(0)
    const [formValue, setFormValue] = useState({})
    useEffect(() => {
        getSetting()
    }, [])
    const FETCHWP = new fetchWP({
        restURL: wpspObject.api_url,
        restNonce: wpspObject.api_nonce,
    })
    const notify = (status) => {
        return toast.success('Settings Saved!', {
            position: 'top-right',
            autoClose: 5000,
            hideProgressBar: true,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            progress: undefined,
        })
    }
    const processOkResponse = (json, action) => {
        if (json.success) {
            setFormValue(JSON.parse(json.value))
        } else {
            console.log(`Setting was not ${action}.`, json)
        }
        notify(json.success)
    }

    const getSetting = () => {
        FETCHWP.get('settings').then(
            (json) => setFormValue(JSON.parse(json.value)),
            (err) => console.log('error', err)
        )
    }
    return (
        <Formik
            enableReinitialize={true}
            initialValues={formValue}
            onSubmit={(values, actions) => {
                FETCHWP.post('settings', {
                    wpspSetting: JSON.stringify(values, null, 2),
                }).then(
                    (json) => processOkResponse(json, 'saved'),
                    (err) => console.log('error', err)
                )
            }}
        >
            {(props) => {
                if (props.dirty === true) {
                    window.onbeforeunload = function () {
                        return 'Do you really want to close?'
                    }
                } else {
                    window.onbeforeunload = null
                }
                console.log(wpspObject.settings)
                return (
                    <form onSubmit={props.handleSubmit}>
                        <Tabs
                            selectedIndex={tabIndex}
                            onSelect={(tabIndex) => setTabIndex(tabIndex)}
                        >
                            <TabList>
                                {wpspObject.settings.map((item, index) => (
                                    <Tab key={index}>{item.title}</Tab>
                                ))}
                            </TabList>
                            {wpspObject.settings.map((item, index) => (
                                <TabPanel key={index}>
                                    {Object.keys(props.values).length > 0 && (
                                        <div>
                                            {
                                                // sub tabs
                                                item.sub_tabs !== undefined && (
                                                    <Tabs>
                                                        {/* sub tabs menu item */}
                                                        <TabList>
                                                            {Object.entries(
                                                                item.sub_tabs
                                                            ).map(
                                                                ([
                                                                    subIndex,
                                                                    subItem,
                                                                ]) => (
                                                                    <Tab
                                                                        key={
                                                                            subIndex
                                                                        }
                                                                    >
                                                                        {
                                                                            subItem.title
                                                                        }
                                                                    </Tab>
                                                                )
                                                            )}
                                                        </TabList>
                                                        {/* sub tabs body */}

                                                        {Object.entries(
                                                            item.sub_tabs
                                                        ).map(
                                                            ([
                                                                subIndex,
                                                                subItem,
                                                            ]) => (
                                                                <TabPanel>
                                                                    {item
                                                                        .sub_tabs[
                                                                        subIndex
                                                                    ].fields !==
                                                                        undefined &&
                                                                        item.sub_tabs[
                                                                            subIndex
                                                                        ].fields.map(
                                                                            (
                                                                                subTabFieldItem,
                                                                                subTabFieldIndex
                                                                            ) => (
                                                                                <Fields
                                                                                    {...subTabFieldItem}
                                                                                    setFieldValue={
                                                                                        props.setFieldValue
                                                                                    } // formik
                                                                                    key={
                                                                                        subTabFieldIndex
                                                                                    }
                                                                                    values={
                                                                                        props.values
                                                                                    }
                                                                                />
                                                                            )
                                                                        )}
                                                                </TabPanel>
                                                            )
                                                        )}
                                                    </Tabs>
                                                )
                                            }
                                            {
                                                // main tabs fields
                                                item.fields !== undefined &&
                                                    item.fields.length > 0 &&
                                                    item.fields.map(
                                                        (
                                                            fieldItem,
                                                            fieldIndex
                                                        ) => (
                                                            <Fields
                                                                {...fieldItem}
                                                                setFieldValue={
                                                                    props.setFieldValue
                                                                } // formik
                                                                key={fieldIndex}
                                                                values={
                                                                    props.values
                                                                }
                                                            />
                                                        )
                                                    )
                                            }
                                        </div>
                                    )}
                                </TabPanel>
                            ))}
                        </Tabs>
                        <button
                            className={
                                props.dirty === false
                                    ? 'btn-submit'
                                    : 'btn-submit btn-submit--changed'
                            }
                            type='submit'
                        >
                            Submit
                        </button>
                        <ToastContainer />
                    </form>
                )
            }}
        </Formik>
    )
}

export default Settings
