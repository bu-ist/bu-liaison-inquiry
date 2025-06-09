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
 * Internal dependencies
 */
import './app.css';
import CredentialModal from './CredentialModal';
import CredentialCard from './CredentialCard';
import FormBrowser from './FormBrowser';

/**
 * The main admin application component.
 *
 * @return {JSX.Element} The application interface.
 */
const styles = {
    utmContainer: {
        display: 'flex',
        flexWrap: 'wrap',
        margin: '0 -8px', // Negative margin to offset the padding of children
    },
    utmField: {
        flex: '1 1 250px',
        minWidth: '250px',
        padding: '0 8px',
        marginBottom: '16px',
    },
    utmSection: {
        marginTop: '24px',
        marginBottom: '24px',
    },
    utmDetails: {
        border: '1px solid #ccd0d4',
        borderRadius: '4px',
        backgroundColor: '#fff',
    },
    utmSummary: {
        padding: '12px 16px',
        cursor: 'pointer',
        fontWeight: 500,
        backgroundColor: '#f0f0f1',
        borderBottom: '1px solid #ccd0d4',
    },
    utmContent: {
        padding: '16px',
    },
    utmDescription: {
        fontSize: '13px',
        margin: '0 0 16px 0',
        color: '#757575',
    }
};

function App() {
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isSaving, setIsSaving ] = useState( false );
    const [ error, setError ] = useState( null );
    const [ success, setSuccess ] = useState( false );
    const [ alternateCredentials, setAlternateCredentials ] = useState( {} );
    const [ isModalOpen, setIsModalOpen ] = useState( false );
    const [ currentOrgKey, setCurrentOrgKey ] = useState( null );
    const [ isFormBrowserOpen, setIsFormBrowserOpen ] = useState( false );
    const [ formBrowserOrgKey, setFormBrowserOrgKey ] = useState( null );

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
            ClientID: '',
            utm_source: '',
            utm_campaign: '',
            utm_content: '',
            utm_medium: '',
            utm_term: '',
            page_title: ''
        }
    });

    const values = watch();

    // Load the initial settings data through apiFetch and populate the form with it.
    // This uses the React Hook Form's reset method to set the initial values.
    // It also extracts alternate credentials and sets them in the component state.
    useEffect(() => {
        const fetchSettings = async () => {
            try {
                setIsLoading(true);
                setError(null);
                const result = await apiFetch({
                    path: '/bu-liaison-inquiry/v1/credentials',
                });
                
                // Extract alternate credentials
                const { alternate_credentials, ...primaryCreds } = result;
                
                // Update primary credentials form
                reset(primaryCreds);
                
                // Update alternate credentials state
                setAlternateCredentials(alternate_credentials || {});
            } catch (err) {
                setError(err.message);
                console.error(err);
            } finally {
                setIsLoading(false);
            }
        };

        fetchSettings();
    }, [reset]);

    /**
     * Trigger form submission programmatically.
     * 
     * This helper function allows us to trigger the React Hook Form submission
     * from outside the form component, while still using its validation.
     * 
     * @param {Object} customData - Additional data to merge with the form values.
     * @return {Promise} A promise that wraps the entire form submission process and resolves when submission is complete.
     */
    const triggerFormSubmission = async (customData) => {
        return new Promise((resolve, reject) => {
            //
            handleSubmit(async (formData) => {
                try {
                    await onSubmit({
                        ...formData,
                        ...customData
                    });
                    resolve();
                } catch (err) {
                    reject(err);
                }
            })();  // Immediately invoke the handleSubmit function, simulating a form submission
        });
    };

    // Open modal to edit an organization
    const editOrganization = (orgKey) => {
        setCurrentOrgKey(orgKey);
        setIsModalOpen(true);
    };
    
    // Open modal to add a new organization
    const addNewOrganization = () => {
        setCurrentOrgKey(null);
        setIsModalOpen(true);
    };
    
    // Save credential from modal and trigger form submission
    const saveCredential = async (orgKey, data) => {
        try {
            // Update alternate credentials first
            const newCreds = {
                ...alternateCredentials,
                [orgKey]: data
            };
            
            // Trigger form submission and wait for it.
            // We use a Promise and an IIFE directly trigger the React Hook form's handleSubmit event outside of the form component itself.
            // This lets us rely on the form validation and submission logic.
            await triggerFormSubmission({ alternate_credentials: newCreds });

            // Update state but keep modal open
            setAlternateCredentials(newCreds);
            return true; // Indicate success to modal
            
        } catch (err) {
            // Let the modal handle the error display
            throw err;
        }
    };
    
    // Delete an organization
    const deleteOrganization = async (orgKey) => {
        // Ask for confirmation
        if (!window.confirm(__('Are you sure you want to delete this organization? This cannot be undone.', 'bu-liaison-inquiry'))) {
            return;
        }
        
        try {
            // Update alternate credentials
            const newCreds = { ...alternateCredentials };
            delete newCreds[orgKey];
            
            // Save changes to server
            await triggerFormSubmission({ alternate_credentials: newCreds });
            
            // Update local state
            setAlternateCredentials(newCreds);
            setSuccess(true);
            
            // Clear success message after 10 seconds
            setTimeout(() => {
                setSuccess(false);
            }, 10000);
            
        } catch (err) {
            setError(err.message);
            console.error(err);
        }
    };
    
    // Handle form browser for specific organization
    const handleBrowseForms = (orgKey = null) => {
        setFormBrowserOrgKey(orgKey);
        setIsFormBrowserOpen(true);
    };

    // Handle form browser close
    const handleFormBrowserClose = () => {
        setIsFormBrowserOpen(false);
        setFormBrowserOrgKey(null);
    };

    // Handle form submission
    const onSubmit = async (data) => {
        setError(null);
        setSuccess(false);
        
        try {
            setIsSaving(true);
            
            // Use alternate_credentials if provided in data, otherwise use state
            const formData = {
                ...data,
                alternate_credentials: data.alternate_credentials || alternateCredentials
            };
            
            // Ensure all UTM parameters are included
            const utmParameters = ['utm_source', 'utm_campaign', 'utm_content', 'utm_medium', 'utm_term'];
            utmParameters.forEach(param => {
                if (formData[param] === undefined) {
                    formData[param] = '';
                }
            });
            
            const response = await apiFetch({
                path: '/bu-liaison-inquiry/v1/credentials',
                method: 'POST',
                data: formData
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

    return (
        <div className="bu-liaison-inquiry-admin-app">
            <h1>
                {__('BU Liaison Inquiry Settings', 'bu-liaison-inquiry')}
            </h1>
            <Card>
                <CardHeader>
                    <h2>{__('Primary Organization Credentials and UTM parameters', 'bu-liaison-inquiry')}</h2>
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
                        
                        <div className="bu-liaison-inquiry-description">
                            <p>
                                {__('These credentials are used by default. You can also add alternate organizations below, any shortcode that does not specify and alternate organization will use these credentials.  The UTM parameters are used for all organizations unless individually overridden in the shortcode.', 'bu-liaison-inquiry')}
                            </p>
                        </div>

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

                                <div style={styles.utmSection}>
                                    <details style={styles.utmDetails}>
                                        <summary style={styles.utmSummary}>
                                            {__('UTM Parameters', 'bu-liaison-inquiry')}
                                        </summary>
                                        <div style={styles.utmContent}>
                                            <p style={styles.utmDescription}>
                                                {__('These UTM parameters will be used for all form submissions unless overridden in the shortcode.', 'bu-liaison-inquiry')}
                                            </p>

                                            <div style={styles.utmContainer}>
                                                <div style={styles.utmField}>
                                                    <TextControl
                                                        {...register('utm_source')}
                                                        onChange={val => setValue('utm_source', val)}
                                                        value={values.utm_source || ''}
                                                        label={__('Source:', 'bu-liaison-inquiry')}
                                                        help={__('Identifies which site sent the traffic (e.g., google, newsletter)', 'bu-liaison-inquiry')}
                                                        placeholder={__('Enter UTM source...', 'bu-liaison-inquiry')}
                                                        disabled={isSaving}
                                                    />
                                                </div>
                                                
                                                <div style={styles.utmField}>
                                                    <TextControl
                                                        {...register('utm_medium')}
                                                        onChange={val => setValue('utm_medium', val)}
                                                        value={values.utm_medium || ''}
                                                        label={__('Medium:', 'bu-liaison-inquiry')}
                                                        help={__('Identifies marketing medium (e.g., cpc, banner, email)', 'bu-liaison-inquiry')}
                                                        placeholder={__('Enter UTM medium...', 'bu-liaison-inquiry')}
                                                        disabled={isSaving}
                                                    />
                                                </div>
                                                
                                                <div style={styles.utmField}>
                                                    <TextControl
                                                        {...register('utm_campaign')}
                                                        onChange={val => setValue('utm_campaign', val)}
                                                        value={values.utm_campaign || ''}
                                                        label={__('Campaign Name:', 'bu-liaison-inquiry')}
                                                        help={__('Name of the campaign (e.g., spring-promotion)', 'bu-liaison-inquiry')}
                                                        placeholder={__('Enter UTM campaign...', 'bu-liaison-inquiry')}
                                                        disabled={isSaving}
                                                    />
                                                </div>
                                                
                                                <div style={styles.utmField}>
                                                    <TextControl
                                                        {...register('utm_content')}
                                                        onChange={val => setValue('utm_content', val)}
                                                        value={values.utm_content || ''}
                                                        label={__('Content:', 'bu-liaison-inquiry')}
                                                        help={__('Used to differentiate similar content (e.g., text-link-1)', 'bu-liaison-inquiry')}
                                                        placeholder={__('Enter UTM content...', 'bu-liaison-inquiry')}
                                                        disabled={isSaving}
                                                    />
                                                </div>
                                                
                                                <div style={styles.utmField}>
                                                    <TextControl
                                                        {...register('utm_term')}
                                                        onChange={val => setValue('utm_term', val)}
                                                        value={values.utm_term || ''}
                                                        label={__('Term:', 'bu-liaison-inquiry')}
                                                        help={__('Identifies search terms used (e.g., university-application)', 'bu-liaison-inquiry')}
                                                        placeholder={__('Enter UTM term...', 'bu-liaison-inquiry')}
                                                        disabled={isSaving}
                                                    />
                                                </div>
                                                <div style={styles.utmField}>
                                                    <TextControl
                                                        {...register('page_title')}
                                                        onChange={val => setValue('page_title', val)}
                                                        value={values.page_title || ''}
                                                        label={__('Page Title:', 'bu-liaison-inquiry')}
                                                        help={__('The default page title for inquiry forms. Can be overridden in the shortcode.', 'bu-liaison-inquiry')}
                                                        placeholder={__('Enter page title...', 'bu-liaison-inquiry')}
                                                        style={{ maxWidth: '400px' }}
                                                        disabled={isSaving}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <Button
                                    type="submit"
                                    isPrimary
                                    variant="primary"
                                    style={{ marginTop: '10px', marginRight: '10px' }}
                                    isBusy={isSaving}
                                    disabled={isSaving}
                                >
                                    {isSaving 
                                        ? __('Saving...', 'bu-liaison-inquiry')
                                        : __('Save Settings', 'bu-liaison-inquiry')
                                    }
                                </Button>

                                <Button
                                    isSecondary
                                    variant="secondary"
                                    onClick={() => setIsFormBrowserOpen(true)}
                                    style={{ marginTop: '10px' }}
                                    disabled={isSaving}
                                >
                                    {__('Browse Forms', 'bu-liaison-inquiry')}
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
                    {Object.entries(alternateCredentials).map(([orgKey, data]) => (
                        <CredentialCard
                            key={orgKey}
                            orgKey={orgKey}
                            data={data}
                            onEdit={editOrganization}
                            onDelete={deleteOrganization}
                            onBrowseForms={handleBrowseForms}
                            disabled={isSaving}
                        />
                    ))}
                    
                    <Button
                        isSecondary // for 5.4 compatibility
                        variant="secondary"
                        onClick={addNewOrganization}
                        style={{ marginTop: '10px' }}
                        disabled={isSaving}
                    >
                        {__('Add Organization', 'bu-liaison-inquiry')}
                    </Button>
                </CardBody>
            </Card>
            
            {isModalOpen && (
                <CredentialModal
                    isOpen={isModalOpen}
                    onClose={() => setIsModalOpen(false)}
                    orgKey={currentOrgKey}
                    initialData={currentOrgKey ? alternateCredentials[currentOrgKey] : null}
                    onSave={saveCredential}
                    isSaving={isSaving}
                    existingOrgs={alternateCredentials}
                />
            )}
            
            {isFormBrowserOpen && (
                <FormBrowser
                    isOpen={isFormBrowserOpen}
                    onClose={handleFormBrowserClose}
                    orgKey={formBrowserOrgKey}
                />
            )}
        </div>
    );
}

export default App;
