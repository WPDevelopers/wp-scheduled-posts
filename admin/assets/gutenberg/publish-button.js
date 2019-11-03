/**
 * WordPress dependencies
 */

const { Button } = wp.components;
const { Component, createElement } = wp.element;

class PublishButton extends Component {
	constructor(props) {
		super(props);
		this.handleChange = this.handleChange.bind(this);
		this.state = {
			isChecked : false,
			isClicked : false
		}
	}

	handleChange( value ) {
		if( value === 'clicked' ) {
			value = '{"date" : "'+ this.props.currentTime.date +'", "date_gmt" : "'+ this.props.currentTime.date_gmt +'", "status" : "publish"}';
			this.setState({ isClicked : true });
			this.setState({ isChecked : false });
			this.props.editPost( value );
			return;
		}
	}

	render() {
		if( this.props.isScheduled && ! this.props.isPublished ) {
			return(
				<Button isPrimary onClick={()=>{ this.handleChange( 'clicked' ) }}>
				{ this.props.publish }
				</Button>
			);
		}
		return null;
	}
}

export default PublishButton;