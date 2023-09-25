import React from 'react';
const {
	element: { createElement,Fragment },
    components: { SelectControl, TextControl, Modal, Button }
} = wp;
const { __ } = wp.i18n;

const ProModal = ( { isOpenModal,setProModal } ) => {
  return (
    <Fragment>
        { isOpenModal && 
            <Modal className="wpsp-pro-modal" onRequestClose={ () => setProModal(false) }>
                <h2>Opps!</h2>
                <h4>You Need SchedulePress PRO</h4>
                <img src={ WPSchedulePostsFree.assetsURI + '/images/upgrade-pro.gif' } alt="pro-alert" />
                <a target='_blank' href="https://schedulepress.com/#pricing">Check Pricing Plans</a>
            </Modal>
        }
    </Fragment>
  )
}

export default ProModal