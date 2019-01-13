/**
 * WordPress dependencies
 */
const { compose, ifCondition, withInstanceId } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { PluginPostStatusInfo } = wp.editPost;
const { Component, createElement } = wp.element;
const { schedule, PanelTitle } = WPSchedulePosts;
import ScheduleList from './main';

class AdminPanel extends Component {
	constructor(props) {
		super(props)
	}
  
	render() {
		let ScheduleDates = [];
		ScheduleDates.push({ label: "Select a schedule time", value : '{ "date" : "'+ this.props.post.date +'", "date_gmt" : "'+ this.props.post.date_gmt +'", "status" : "'+ this.props.post.status +'" }' });

		Object.entries(schedule).forEach(([key, item]) => {
			ScheduleDates.push({ label: item.label, value : '{ "date" : "'+ item.date +'", "date_gmt" : "'+ item.date_gmt +'", "status" : "'+ item.status +'" }' });
		});

		return (
			<PluginPostStatusInfo>
				<ScheduleList { ...this.props } label={ PanelTitle } options = { ScheduleDates }/>
			</PluginPostStatusInfo>
		);
	}
}

export default compose( [
	withSelect( ( select ) => {
		const {
			getCurrentPostType,
			getEditedPostAttribute,
			isCurrentPostScheduled,
			isCurrentPostPublished,
			getCurrentPost
		} = select( 'core/editor' );

		return {
			postType: getCurrentPostType(),
			meta: getEditedPostAttribute( 'meta' ),
			isScheduled: isCurrentPostScheduled(),
			isPublished: isCurrentPostPublished(),
			post: getCurrentPost(),
		};
	} ),
	withDispatch( ( dispatch, { meta } ) => {
		const { editPost } = dispatch( 'core/editor' );
		return {
			editPost( newMeta ) {
				var new_date = JSON.parse( newMeta );
				editPost( { date: new_date.date, date_gmt: new_date.date_gmt, status: new_date.status } );
			},
		};

	} ),
	ifCondition( ( { postType } ) => 'post' === postType ),
] )( AdminPanel );