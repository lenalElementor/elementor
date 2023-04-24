import * as React from 'react';
import { render, waitFor } from '@testing-library/react';
import { useHostDocument, useActiveDocument } from '@elementor/documents';
import RecentlyEdited from '../recently-edited';
import { createMockDocument } from 'test-utils';
import useRecentPosts, { Post } from '../../../hooks/use-recent-posts';
import apiFetch from '@wordpress/api-fetch';
import { endpointPath } from '../../../hooks/use-create-page';

jest.mock( '@elementor/documents', () => ( {
	useActiveDocument: jest.fn(),
	useHostDocument: jest.fn(),
} ) );

jest.mock( '../../../hooks/use-recent-posts', () => (
	{
		default: jest.fn( () => ( { isLoading: false, recentPosts: [] } ) ),
		__esModule: true,
	}
) );
jest.mock( '@wordpress/api-fetch' );

describe( '@elementor/recently-edited - Top bar add new page', () => {
	beforeEach( () => {
		jest.mocked( apiFetch ).mockImplementation( () => Promise.resolve( [] ) );

		jest.mocked( useActiveDocument ).mockImplementation( () =>
			createMockDocument( { id: 1, title: 'Active Document' } )
		);

		jest.mocked( useHostDocument ).mockImplementation( () =>
			createMockDocument( { id: 2, title: 'Host Document' } )
		);
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render add new page button', () => {
		// Arrange.
		mockActiveDocument();

		const isLoading = false;
		const recentPosts: Post[] = [];

		jest.mocked( useRecentPosts ).mockReturnValue( { isLoading, recentPosts } );

		const { getByText, getAllByRole } = render( <RecentlyEdited /> );

		// Act.
		const buttons = getAllByRole( 'button' );
		buttons[ 0 ].click(); // Opens the recently edited menu

		// Assert.
		const label = getByText( 'Add new page', { exact: false } );
		expect( label ).toBeInTheDocument();
	} );

	it( 'should trigger create page hook on click', async () => {
		// Arrange.
		mockActiveDocument();

		const newPost = {
			id: 1,
			edit_url: 'editurl.com',
		};
		jest.mocked( apiFetch ).mockImplementation( () => Promise.resolve( newPost ) );

		const isLoading = false;
		const recentPosts: Post[] = [];

		jest.mocked( useRecentPosts ).mockReturnValue( { isLoading, recentPosts } );

		const { getByText, getAllByRole } = render( <RecentlyEdited /> );

		// Act.
		const buttons = getAllByRole( 'button' );
		buttons[ 0 ].click(); // Opens the recently edited menu
		const addNewPage = getByText( 'Add new page', { exact: false } );
		addNewPage.click();

		// Assert.
		await waitFor( () => {
			expect( apiFetch ).toHaveBeenCalledWith( {
				data: { post_type: 'page' },
				method: 'POST',
				path: endpointPath,
			} );
			expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );

function mockActiveDocument() {
	jest.mocked( useActiveDocument ).mockImplementation( () =>
		createMockDocument( {
			id: 1,
			title: 'Header',
			type: {
				value: 'header',
				label: 'Header',
			},
		} )
	);
}
