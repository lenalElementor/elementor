import { AnnouncementsHeader, Announcement } from './';
import PropTypes from 'prop-types';

export default function Announcements( { announcements } ) {
	return (
		<div className="announcements-container">
			<AnnouncementsHeader />
			{ Object.values( announcements ).map( ( announcement, index ) => <Announcement key={ `announcement${ index }` } announcement={ announcement } /> ) }
		</div>
	);
}

Announcements.propTypes = {
	announcements: PropTypes.oneOfType( [ PropTypes.array, PropTypes.object ] ).isRequired,
};
