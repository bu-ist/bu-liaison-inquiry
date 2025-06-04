/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { useState, useEffect } from '@wordpress/element';

import { 
    Card,
    CardHeader, 
    CardBody, 
    TextControl, 
    Button,
    Notice,
    Spinner
} from '@wordpress/components';

import apiFetch from '@wordpress/api-fetch';


/**
 * The main admin application component.
 *
 * @return {JSX.Element} The application interface.
 */
function App() {
    const [ settings, setSettings ] = useState( {} );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ error, setError ] = useState( null );

    // Handle input changes
    const handleChange = ( key, value ) => {
        setSettings( prev => ({
            ...prev,
            [key]: value
        }));
    };

    // Handle save action
    const handleSave = async () => {
        try {
            setIsSaving( true );
            setError( null );

            const response = await apiFetch({
                path: '/bu-liaison-inquiry/v1/credentials',
                method: 'POST',
                data: settings
            });

            setSettings( response );
        } catch ( err ) {
            setError( err.message );
            console.error( err );
        } finally {
            setIsSaving( false );
        }
    };

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                setIsLoading( true );
                setError( null );
                const result = await apiFetch({
                    path: '/bu-liaison-inquiry/v1/credentials',
                });
                setSettings( result );
            } catch ( err ) {
                setError( err.message );
                console.error( err );
            } finally {
                setIsLoading( false );
            }
        };

        fetchSettings();
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
                        {error && (
                            <Notice 
                                status="error" 
                                isDismissible={false}
                                className="save-error"
                            >
                                {error}
                            </Notice>
                        )}
                        
                        {isLoading ? (
                            <Spinner />
                        ) : (
                            <>
                                <TextControl
                                    label={ __('API Key', 'bu-liaison-inquiry') }
                                    help={ __('The API key for the primary organization.', 'bu-liaison-inquiry') }
                                    value={ settings.APIKey || '' }
                                    onChange={ ( value ) => handleChange( 'APIKey', value ) }
                                    placeholder={ __('Enter API key...', 'bu-liaison-inquiry') }
                                    style={{ maxWidth: '400px' }}
                                    disabled={ isSaving }
                                />
                                <TextControl
                                    label={ __('Client ID', 'bu-liaison-inquiry') }
                                    help={ __('The client ID for the primary organization.', 'bu-liaison-inquiry') }
                                    value={ settings.ClientID || '' }
                                    onChange={ ( value ) => handleChange( 'ClientID', value ) }
                                    placeholder={ __('Enter client ID...', 'bu-liaison-inquiry') }
                                    style={{ maxWidth: '100px' }}
                                    disabled={ isSaving }
                                />
                                <Button
                                    variant='primary'
                                    style={{ marginTop: '10px' }}
                                    onClick={handleSave}
                                    isBusy={isSaving}
                                    disabled={isSaving}
                                >
                                    {isSaving 
                                        ? __('Saving...', 'bu-liaison-inquiry')
                                        : __('Save Settings', 'bu-liaison-inquiry')
                                    }
                                </Button>
                            </>
                        )}
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
