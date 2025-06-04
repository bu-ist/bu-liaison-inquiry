/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { useState, useEffect } from '@wordpress/element';

import { Card, CardHeader, CardBody, TextControl } from '@wordpress/components';

import apiFetch from '@wordpress/api-fetch';


/**
 * The main admin application component.
 *
 * @return {JSX.Element} The application interface.
 */
function App() {

    const [ settings, setSettings ] = useState( {} );

    useEffect(() => {
        // Define a local function to fetch settings from the REST API.
        const fetchSettings = async () => {
            // Load the plugin settings options value from the custom endpoint.
            const result = await apiFetch( {
                path: '/bu-liaison-inquiry/v1/credentials',
            } );
            setSettings( result );
            console.log('result', result);
        };

        // Call the fetchSettings function to load the settings when the component is first loaded.
        fetchSettings().catch( ( error ) => {
            console.error( error );
        } );
    }, [])

    return (
        <div className="bu-liaison-inquiry-admin-app">
            <Card>
                <CardHeader>
                    <h1>
                        {__('BU Liaison Inquiry Settings', 'bu-liaison-inquiry')}
                    </h1>
                </CardHeader>
                <CardBody>
                    <TextControl
                        label={ __('Custom Field', 'bu-liaison-inquiry') }
                        help={ __('This is a custom field for demonstration purposes.', 'bu-liaison-inquiry') }
                        placeholder={ __('Enter value...', 'bu-liaison-inquiry') }
                    />

                    {JSON.stringify(settings, null, 2)}

                </CardBody>
            </Card>
        </div>
    );
}

export default App;
