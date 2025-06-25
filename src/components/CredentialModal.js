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
 * @param {boolean} props.isSaving Whether the parent component is in a saving state.
 * @param {Object} props.existingOrgs Current organization credentials.
 * @return {JSX.Element} The credential modal component.
 */
function CredentialModal({ isOpen, onClose, orgKey, initialData, onSave, isSaving: parentIsSaving = false, existingOrgs = {} }) {
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
        setError: setFieldError,
        clearErrors,
        formState: { errors, isSubmitted, isDirty, dirtyFields }
    } = useForm({
        mode: 'onChange', // Enable real-time validation
        defaultValues: initialData || { 
            APIKey: '', 
            ClientID: '' 
        }
    });

    const values = watch();

    // Check if an organization key already exists
    const checkOrgKeyExists = (key) => {
        if (!key || orgKey) return false;
        return Object.keys(existingOrgs).includes(key);
    };

    // Handle orgKey change for real-time validation
    const handleOrgKeyChange = (val) => {
        setValue('orgKey', val, { 
            shouldValidate: true, // Trigger validation on change
            shouldDirty: true 
        });
        
        // Clear global error if it was about duplicate keys
        if (error && error.includes('already exists')) {
            setError(null);
        }
        
        // Show inline validation for duplicate keys
        if (val && checkOrgKeyExists(val)) {
            setFieldError('orgKey', {
                type: 'manual',
                message: __('This Organization Key already exists. Please choose a unique key.', 'bu-liaison-inquiry')
            });
        } else {
            clearErrors('orgKey');
        }
    };

    // Handle field changes with validation
    const handleFieldChange = (field, val) => {
        setValue(field, val, {
            shouldValidate: true,
            shouldDirty: true
        });
    };

    const onSubmit = async (data) => {
        // Validate orgKey if this is a new organization
        if (!orgKey && !values.orgKey) {
            setError(__('Organization Key is required.', 'bu-liaison-inquiry'));
            return;
        }

        // For new orgs, use the entered key, otherwise use the existing key
        const key = orgKey || values.orgKey;
        
        // Check for duplicate keys when adding a new organization
        if (!orgKey && existingOrgs[key]) {
            setError(__('This Organization Key already exists. Please choose a unique key.', 'bu-liaison-inquiry'));
            return;
        }
        
        // Pass only APIKey and ClientID to parent
        const { APIKey, ClientID } = data;
        
        try {
            setError(null);
            setSuccess(false);
            setLocalIsSaving(true);
            
            await onSave(key, { APIKey, ClientID });
            setSuccess(true);
            
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
                    {__('Credentials saved successfully!', 'bu-liaison-inquiry')}
                </Notice>
            )}
            
            <form onSubmit={handleSubmit(onSubmit)}>
                {!orgKey && (
                    <TextControl
                        {...register('orgKey', {
                            required: __('Organization Key is required.', 'bu-liaison-inquiry'),
                            pattern: {
                                value: /^[a-zA-Z0-9_-]+$/,
                                message: __('Organization Key can only contain letters, numbers, underscores and hyphens.', 'bu-liaison-inquiry')
                            }
                        })}
                        onChange={handleOrgKeyChange}
                        value={values.orgKey || ''}
                        label={__('Organization Key:', 'bu-liaison-inquiry')}
                        help={errors.orgKey?.message || __('A unique identifier for this organization.', 'bu-liaison-inquiry')}
                        className={`components-text-control__input ${errors.orgKey ? 'has-error' : ''}`}
                        __nextHasNoMarginBottom
                        placeholder={__('Enter organization key...', 'bu-liaison-inquiry')}
                        disabled={success}
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
                    onChange={(val) => handleFieldChange('APIKey', val)}
                    value={values.APIKey || ''}
                    label={__('API Key:', 'bu-liaison-inquiry')}
                    help={errors.APIKey?.message || __('The API key for this organization.', 'bu-liaison-inquiry')}
                    className={`components-text-control__input ${errors.APIKey ? 'has-error' : ''}`}
                    __nextHasNoMarginBottom
                    placeholder={__('Enter API key...', 'bu-liaison-inquiry')}
                    disabled={success}
                />
                
                <TextControl
                    {...register('ClientID', {
                        required: __('Client ID is required.', 'bu-liaison-inquiry')
                    })}
                    onChange={(val) => handleFieldChange('ClientID', val)}
                    value={values.ClientID || ''}
                    label={__('Client ID:', 'bu-liaison-inquiry')}
                    help={errors.ClientID?.message || __('The client ID for this organization.', 'bu-liaison-inquiry')}
                    className={`components-text-control__input ${errors.ClientID ? 'has-error' : ''}`}
                    __nextHasNoMarginBottom
                    placeholder={__('Enter client ID...', 'bu-liaison-inquiry')}
                    disabled={success}
                />

                <div className="bu-liaison-modal-actions">
                    {!success && (
                        <Button
                            isPrimary
                            type="submit"
                            isBusy={isSaving}
                            disabled={isSaving}
                            style={{ marginRight: '12px' }}
                        >
                            {isSaving
                                ? __('Saving...', 'bu-liaison-inquiry')
                                : __('Save', 'bu-liaison-inquiry')
                            }
                        </Button>
                    )}
                    <Button
                        isSecondary // for 5.4 compatibility
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
