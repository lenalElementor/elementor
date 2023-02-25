import { Announcements, Overlay } from './components';

const container = document.getElementById( 'e-announcements-root' );

if ( window.elementorAnnouncementsConfig.announcements && container ) {
	ReactDOM.render(
		<>
			<Overlay />
			<Announcements announcements={ window.elementorAnnouncementsConfig.announcements } />
		</>,
		container,
	);
}
