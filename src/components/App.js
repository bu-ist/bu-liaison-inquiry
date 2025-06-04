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
            <h1>
                {__('BU Liaison Inquiry Settings', 'bu-liaison-inquiry')}
            </h1>
            <Card>
                <CardHeader>
                    <h2>{ __('Primary Organization Credentials', 'bu-liaison-inquiry') }</h2>
                </CardHeader>
                <CardBody>
                    <>
                        <TextControl
                            label={ __('API Key', 'bu-liaison-inquiry') }
                            help={ __('The API key for the primary organization.', 'bu-liaison-inquiry') }
                            value={ settings.APIKey || '' }
                            placeholder={ __('Enter API key...', 'bu-liaison-inquiry') }
                            style={{ maxWidth: '400px' }}
                        />
                        <TextControl
                            label={ __('Client ID', 'bu-liaison-inquiry') }
                            help={ __('The client ID for the primary organization.', 'bu-liaison-inquiry') }
                            value={ settings.ClientID || '' }
                            placeholder={ __('Enter client ID...', 'bu-liaison-inquiry') }
                            style={{ maxWidth: '100px' }}
                        />
                        {JSON.stringify(settings, null, 2) }
                    </>
                </CardBody>
            </Card>
            <Card>
                <CardHeader>
                    <h2>{ __('Alternate Organization Credentials', 'bu-liaison-inquiry') }</h2>
                </CardHeader>
                <CardBody>
                    Other controls
                </CardBody>
            </Card>
        </div>
    );
}

export default App;
