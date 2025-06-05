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
    const [ success, setSuccess ] = useState( false );
    const [ validation, setValidation ] = useState( {
        APIKey: { isValid: true, message: '' },
        ClientID: { isValid: true, message: '' }
    } );

    // Handle input changes
    const handleChange = ( key, value ) => {
        setSettings( prev => ({
            ...prev,
            [key]: value
        }));
    };

    // Validate form fields
    const validateFields = () => {
        const newValidation = {
            APIKey: { isValid: true, message: '' },
            ClientID: { isValid: true, message: '' }
        };

        // Validate API Key
        if (!settings.APIKey?.trim()) {
            newValidation.APIKey = {
                isValid: false,
                message: __('API Key is required.', 'bu-liaison-inquiry')
            };
        } else if (settings.APIKey.length < 10) {
            newValidation.APIKey = {
                isValid: false,
                message: __('API Key must be at least 10 characters.', 'bu-liaison-inquiry')
            };
        }

        // Validate Client ID
        if (!settings.ClientID?.trim()) {
            newValidation.ClientID = {
                isValid: false,
                message: __('Client ID is required.', 'bu-liaison-inquiry')
            };
        }

        setValidation(newValidation);
        return Object.values(newValidation).every(field => field.isValid);
    };

    // Handle save action
    const handleSave = async () => {
        // Clear previous states
        setError(null);
        setSuccess(false);

        // Validate fields
        if (!validateFields()) {
            return;
        }

        try {
            setIsSaving(true);

            const response = await apiFetch({
                path: '/bu-liaison-inquiry/v1/credentials',
                method: 'POST',
                data: settings
            });

            setSettings(response);
            setSuccess(true);

            // Clear success message after 5 seconds
            setTimeout(() => {
                setSuccess(false);
            }, 5000);

        } catch (err) {
            setError(err.message);
            console.error(err);
        } finally {
            setIsSaving(false);
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

                        {success && (
                            <Notice
                                status="success"
                                isDismissible={false}
                                className="save-success"
                            >
                                {__('Settings saved successfully!', 'bu-liaison-inquiry')}
                            </Notice>
                        )}
                        
                        {isLoading ? (
                            <Spinner />
                        ) : (
                            <>
                                <TextControl
                                    label={ __('API Key:', 'bu-liaison-inquiry') }
                                    help={ validation.APIKey.isValid 
                                        ? __('The API key for the primary organization.', 'bu-liaison-inquiry')
                                        : validation.APIKey.message
                                    }
                                    value={ settings.APIKey || '' }
                                    onChange={ ( value ) => handleChange( 'APIKey', value ) }
                                    placeholder={ __('Enter API key...', 'bu-liaison-inquiry') }
                                    style={{ maxWidth: '400px' }}
                                    disabled={ isSaving }
                                />
                                <TextControl
                                    label={ __('Client ID:', 'bu-liaison-inquiry') }
                                    help={ validation.ClientID.isValid
                                        ? __('The client ID for the primary organization.', 'bu-liaison-inquiry')
                                        : validation.ClientID.message
                                    }
                                    value={ settings.ClientID || '' }
                                    onChange={ ( value ) => handleChange( 'ClientID', value ) }
                                    placeholder={ __('Enter client ID...', 'bu-liaison-inquiry') }
                                    style={{ maxWidth: '100px' }}
                                    disabled={ isSaving }
                                />
                                <Button
                                    isPrimary
                                    variant='primary' // Not effective in 5.4, but compatible with future versions.
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
