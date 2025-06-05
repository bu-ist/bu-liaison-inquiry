/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { 
    Modal,
    TextControl, 
    Button,
    Notice
} from '@wordpress/components';
import { useForm } from 'react-hook-form';

/**
 * Credential Modal component for editing organization credentials.
 *
 * @param {Object} props Component properties.
 * @param {boolean} props.isOpen Whether the modal is open.
 * @param {Function} props.onClose Callback to close the modal.
 * @param {string|null} props.orgKey The organization key (null for new organizations).
 * @param {Object|null} props.initialData Initial credential data.
 * @param {Function} props.onSave Callback to save the credential data.
 * @return {JSX.Element} The credential modal component.
 */
function CredentialModal({ isOpen, onClose, orgKey, initialData, onSave, isSaving: parentIsSaving = false }) {
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [localIsSaving, setLocalIsSaving] = useState(false);
    
    // Combined saving state from parent and local
    const isSaving = parentIsSaving || localIsSaving;
    
    const { 
        register, 
        handleSubmit, 
        setValue,
        reset,
        watch,
        formState: { errors }
    } = useForm({
        defaultValues: initialData || { 
            APIKey: '', 
            ClientID: '' 
        }
    });

    const values = watch();

    const onSubmit = async (data) => {
        // Validate orgKey if this is a new organization
        if (!orgKey && !values.orgKey) {
            setError(__('Organization Key is required.', 'bu-liaison-inquiry'));
            return;
        }

        // For new orgs, use the entered key, otherwise use the existing key
        const key = orgKey || values.orgKey;
        
        // Pass only APIKey and ClientID to parent
        const { APIKey, ClientID } = data;
        
        try {
            setError(null);
            setSuccess(false);
            setLocalIsSaving(true);
            
            await onSave(key, { APIKey, ClientID });
            
            // Show success message and reset form with current values as initial
            setSuccess(true);
            reset({ APIKey, ClientID, orgKey: key });
            
        } catch (err) {
            setError(err.message || __('Failed to save credentials.', 'bu-liaison-inquiry'));
            setSuccess(false);
        } finally {
            setLocalIsSaving(false);
        }
    };

    const handleClose = () => {
        if (!isSaving) {
            onClose();
        }
    };

    if (!isOpen) {
        return null;
    }

    return (
        <Modal
            title={orgKey 
                ? __('Edit Organization Credentials', 'bu-liaison-inquiry') 
                : __('Add New Organization Credentials', 'bu-liaison-inquiry')
            }
            onRequestClose={handleClose}
            className="bu-liaison-credential-modal"
        >
            {error && (
                <Notice 
                    status="error" 
                    isDismissible={false}
                    className="credential-error"
                >
                    {error}
                </Notice>
            )}
            
            {success && (
                <Notice 
                    status="success" 
                    isDismissible={false}
                    className="credential-success"
                >
                    {__('Credentials saved successfully! You can make more changes or close this window.', 'bu-liaison-inquiry')}
                </Notice>
            )}
            
            <form onSubmit={handleSubmit(onSubmit)}>
                {!orgKey && (
                    <TextControl
                        {...register('orgKey', {
                            required: __('Organization Key is required.', 'bu-liaison-inquiry')
                        })}
                        onChange={val => setValue('orgKey', val)}
                        value={values.orgKey || ''}
                        label={__('Organization Key:', 'bu-liaison-inquiry')}
                        help={errors.orgKey?.message || __('A unique identifier for this organization.', 'bu-liaison-inquiry')}
                        placeholder={__('Enter organization key...', 'bu-liaison-inquiry')}
                    />
                )}
                
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
                    help={errors.APIKey?.message || __('The API key for this organization.', 'bu-liaison-inquiry')}
                    placeholder={__('Enter API key...', 'bu-liaison-inquiry')}
                />
                
                <TextControl
                    {...register('ClientID', {
                        required: __('Client ID is required.', 'bu-liaison-inquiry')
                    })}
                    onChange={val => setValue('ClientID', val)}
                    value={values.ClientID || ''}
                    label={__('Client ID:', 'bu-liaison-inquiry')}
                    help={errors.ClientID?.message || __('The client ID for this organization.', 'bu-liaison-inquiry')}
                    placeholder={__('Enter client ID...', 'bu-liaison-inquiry')}
                />
                
                <div className="bu-liaison-modal-actions">
                    <Button
                        isPrimary
                        type="submit"
                        isBusy={isSaving}
                        disabled={isSaving}
                    >
                        {isSaving
                            ? __('Saving...', 'bu-liaison-inquiry')
                            : __('Save', 'bu-liaison-inquiry')
                        }
                    </Button>
                    <Button
                        variant="secondary"
                        onClick={handleClose}
                        disabled={isSaving}
                    >
                        {__('Close', 'bu-liaison-inquiry')}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}

export default CredentialModal;
