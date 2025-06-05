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
import { useForm } from 'react-hook-form';

/**
 * The main admin application component.
 *
 * @return {JSX.Element} The application interface.
 */
function App() {
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ error, setError ] = useState( null );
    const [ success, setSuccess ] = useState( false );

    const { 
        register, 
        handleSubmit, 
        reset,
        setValue,
        watch,
        formState: { errors }
    } = useForm({
        defaultValues: {
            APIKey: '',
            ClientID: ''
        }
    });

    const values = watch();

    // Handle form submission
    const onSubmit = async (data) => {
        setError(null);
        setSuccess(false);
        
        try {
            setIsSaving(true);
            const response = await apiFetch({
                path: '/bu-liaison-inquiry/v1/credentials',
                method: 'POST',
                data
            });

            reset(response);
            setSuccess(true);

            // Clear success message after 10 seconds
            setTimeout(() => {
                setSuccess(false);
            }, 10000);

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
                setIsLoading(true);
                setError(null);
                const result = await apiFetch({
                    path: '/bu-liaison-inquiry/v1/credentials',
                });
                reset(result); // Update form with fetched data
            } catch (err) {
                setError(err.message);
                console.error(err);
            } finally {
                setIsLoading(false);
            }
        };

        fetchSettings();
    }, [reset]);

    return (
        <div className="bu-liaison-inquiry-admin-app">
            <h1>
                {__('BU Liaison Inquiry Settings', 'bu-liaison-inquiry')}
            </h1>
            <Card>
                <CardHeader>
                    <h2>{__('Primary Organization Credentials', 'bu-liaison-inquiry')}</h2>
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
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <TextControl
                                    {...register('APIKey', {
                                        required: __('API Key is required.', 'bu-liaison-inquiry'),
                                        minLength: {
                                            value: 10,
                                            message: __('API Key must be at least 10 characters.', 'bu-liaison-inquiry')
                                        }
                                    })}
                                    onChange={val => setValue('APIKey', val)}
                                    value={values.APIKey || ''}
                                    label={__('API Key:', 'bu-liaison-inquiry')}
                                    help={errors.APIKey?.message || __('The API key for the primary organization.', 'bu-liaison-inquiry')}
                                    placeholder={__('Enter API key...', 'bu-liaison-inquiry')}
                                    style={{ maxWidth: '400px' }}
                                    disabled={isSaving}
                                />
                                <TextControl
                                    {...register('ClientID', {
                                        required: __('Client ID is required.', 'bu-liaison-inquiry')
                                    })}
                                    onChange={val => setValue('ClientID', val)}
                                    value={values.ClientID || ''}
                                    label={__('Client ID:', 'bu-liaison-inquiry')}
                                    help={errors.ClientID?.message || __('The client ID for the primary organization.', 'bu-liaison-inquiry')}
                                    placeholder={__('Enter client ID...', 'bu-liaison-inquiry')}
                                    style={{ maxWidth: '100px' }}
                                    disabled={isSaving}
                                />
                                <Button
                                    type="submit"
                                    isPrimary
                                    variant="primary"
                                    style={{ marginTop: '10px' }}
                                    isBusy={isSaving}
                                    disabled={isSaving}
                                >
                                    {isSaving 
                                        ? __('Saving...', 'bu-liaison-inquiry')
                                        : __('Save Settings', 'bu-liaison-inquiry')
                                    }
                                </Button>
                            </form>
                        )}
                    </>
                </CardBody>
            </Card>
            <Card>
                <CardHeader>
                    <h2>{__('Alternate Organization Credentials', 'bu-liaison-inquiry')}</h2>
                </CardHeader>
                <CardBody>
                    Other controls
                </CardBody>
            </Card>
        </div>
    );
}

export default App;
