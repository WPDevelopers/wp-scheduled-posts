/**
 * WordPress dependencies
 */

const { SelectControl } = wp.components;
const { Component, createElement } = wp.element;

class ScheduleList extends Component {
	constructor(props) {
		super(props);
		this.handleChange = this.handleChange.bind(this);
	}
  
	handleChange( value ) {
        this.props.editPost( value );
	}
  
	render() {
		if( this.props.isScheduled ) {
			return('');
		}

		return (
            <SelectControl
                label = { this.props.label }
				options = { this.props.options }
                onChange={ ( value ) => { this.handleChange( value ) } }
            />
		);
	}
}
export default ScheduleList;