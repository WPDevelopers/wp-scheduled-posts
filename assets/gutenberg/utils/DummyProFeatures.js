import React from 'react';
import ProModal from './ProModal';
const {
	element: { createElement,Fragment,useState },
    components: { SelectControl, TextControl, ToggleControl }
} = wp;
const { __ } = wp.i18n;

const DummyProFeatures = () => {
    const [proModal,setProModal] = useState(false);
    const handleProModal = (event) => {
        // event.preventDefault();
        setProModal(true);
    }
  return (
    <div className="dummy-pro-features">
        <div className="auto-scheduler">
            <h3>{ __('Auto Schedule','wp-scheduled-posts') }</h3>
            <div className="auto-schedule">
            <input id="dummyProAutoScheduler" onClick={ handleProModal } type="checkbox" />
            <label htmlFor="dummyProAutoScheduler">Wednesday, June 14, 2023 at 9:15 AM</label>
            </div>
        </div>
        <div className="manual-scheduler">
            <h3>{ __('Manual Schedule','wp-scheduled-posts') }</h3>
            <div onClick={ handleProModal }>
                <SelectControl
                    options={ [
                        { label: 'Wednesday, June 14, 2023 at 2:50 PM', value: '' }
                    ] }
                />
            </div>
        </div>
        <div className="unpublish-republish">
            <h2>{ __('Scheduling Options','wp-scheduled-posts') }</h2>
            <div className="unpublish">
                <label htmlFor="unpublish_on">{ __('Unpublish On','wp-scheduled-posts') }</label>
                <div onClick={ handleProModal }>
                    <TextControl id="unpublish_on" placeholder="Y/M/D H:M:S" />
                </div>
            </div>
            <div className="republish" >
                <label htmlFor="republish_on">{ __( 'Republish On','wp-scheduled-posts' ) }</label>
                <div onClick={ handleProModal }>
                    <TextControl id="republish_on" placeholder="Y/M/D H:M:S" />
                </div>
            </div>
        </div>
        <div className="advanced-schedule" onClick={ handleProModal }>
            <ToggleControl
                label={ __( 'Advanced Schedule','wp-scheduled-posts' ) }
                checked={ false }
                className='wpsp-advance-schedule-control'
            />
        </div>
        <ProModal isOpenModal={ proModal } setProModal={setProModal} />
    </div>
  )
}

export default DummyProFeatures