import React from 'react';
import ProModal from './ProModal';
const {
	element: { createElement,Fragment,useState },
    components: { SelectControl, TextControl }
} = wp;
const { __ } = wp.i18n;

const DummyProFeatures = () => {
    const [proModal,setProModal] = useState(false);
    const handleProModal = (event) => {
        event.preventDefault();
        setProModal(true);
    }
  return (
    <div className="dummy-pro-features">
        <div className="auto-scheduler">
            <h3>{ __('Auto Scheduler','wp-scheduled-posts') }</h3>
            <div className="auto-schedule">
            <input onClick={ handleProModal } type="checkbox" />
            <label htmlFor="">Wednesday, June 14, 2023 at 9:15 AM</label>
            </div>
        </div>
        <div className="manual-scheduler">
            <h3>{ __('Manual Schedule','wp-scheduled-posts') }</h3>
            <SelectControl
                options={ [
                    { label: 'Wednesday, June 14, 2023 at 2:50 PM', value: '' }
                ] }
                onClick={ handleProModal }
            />
        </div>
        <div className="unpublish-republish">
            <h2>{ __('Scheduling Options','wp-scheduled-posts') }</h2>
            <div className="unpublish">
                <label htmlFor="unpublish_on">{ __('Unpublish On','wp-scheduled-posts') }</label>
                <TextControl onClick={ handleProModal } id="unpublish_on" placeholder="Y/M/D H:M:S" />
            </div>
            <div className="republish">
                <label htmlFor="republish_on">{ __( 'Republish On','wp-scheduled-posts' ) }</label>
                <TextControl onClick={ handleProModal } id="republish_on" placeholder="Y/M/D H:M:S" />
            </div>
        </div>
        <ProModal isOpenModal={ proModal } setProModal={setProModal} />
    </div>
  )
}

export default DummyProFeatures