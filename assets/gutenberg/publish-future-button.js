/**
 * WordPress dependencies
 */

const { Button } = wp.components;
const { Component, createElement } = wp.element;

class PublishFutureButton extends Component {
	constructor(props) {
		super(props);
		this.handleChange = this.handleChange.bind(this);
		this.state = {
			isChecked : false,
			isClicked : false,
			prevent_future_post : false,
		}
	}

	handleChange( value ) {
		if( value === 'clicked' ) {
			this.setState({ isClicked : true });
			this.setState({ isChecked : false });
			this.setState({ prevent_future_post : true });
			value = '{"status" : "publish"}';
			this.props.editPost( value );
			this.props.setMetaValue( {
				publishImmediately: true,
				prevent_future_post: true,
				date_type: "future"
			} );
			this.props.savePost(  );
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

export default PublishFutureButton;