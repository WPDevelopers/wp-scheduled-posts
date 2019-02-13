/**
 * WordPress dependencies
 */

const { CheckboxControl } = wp.components;
const { Component, createElement } = wp.element;

class AutoSchedule extends Component {
	constructor(props) {
		super(props);
		this.handleChange = this.handleChange.bind(this);
		this.state = {
			isChecked : false
		}
	}
  
	handleChange( value ) {
		if( this.state.isChecked ) {
			value = '{ "date" : "'+ this.props.post.date +'", "date_gmt" : "'+ this.props.post.date_gmt +'", "status" : "'+ this.props.post.status +'" }';
			this.setState({ isChecked : false });
		} else {
			this.setState({ isChecked : true });
		}

        this.props.editPost( JSON.stringify(value) );
	}
  
	render() {

		console.log( this.props.options )

		if( this.props.isScheduled ) {
			return('');
		}

		return (
            <CheckboxControl
                heading = { this.props.label }
                label = { this.props.options.label }
                checked={ this.state.isChecked }
                onChange={ () => { this.handleChange( this.props.options ) } }
            />
		);
	}
}

export default AutoSchedule;