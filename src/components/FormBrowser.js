/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './form-browser.css';
import { useState, useEffect } from '@wordpress/element';
import { 
    Modal,
    Button,
    Notice,
    SelectControl,
    Spinner
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Form Browser Modal component for exploring available forms and their fields.
 *
 * @param {Object} props Component properties.
 * @param {boolean} props.isOpen Whether the modal is open.
 * @param {Function} props.onClose Callback to close the modal.
 * @param {string} [props.orgKey] Optional organization key for alternate credentials.
 * @return {JSX.Element} The form browser modal component.
 */
function FormBrowser({ isOpen, onClose, orgKey }) {
    const [isLoadingForms, setIsLoadingForms] = useState(false);
    const [isLoadingFields, setIsLoadingFields] = useState(false);
    const [error, setError] = useState(null);
    const [formLoadError, setFormLoadError] = useState(null);
    const [forms, setForms] = useState([]);
    const [selectedForm, setSelectedForm] = useState('');
    const [fields, setFields] = useState(null);
    const [isMounted, setIsMounted] = useState(true);

    // Set up cleanup on mount
    useEffect(() => {
        return () => setIsMounted(false);
    }, []);

    // Reset state when modal closes
    useEffect(() => {
        if (!isOpen) {
            setForms([]);
            setSelectedForm('');
            setFields(null);
            setError(null);
        }
    }, [isOpen]);

    // Load forms when modal opens
    useEffect(() => {
        const loadForms = async () => {
            if (!isOpen) return;
            
            try {
                setIsLoadingForms(true);
                setFormLoadError(null);
                setError(null);
                
                const response = await apiFetch({
                    path: `/bu-liaison-inquiry/v1/forms${orgKey ? `?org_key=${orgKey}` : ''}`,
                });
                
                if (!isMounted) return;
                
                if (!response || Object.keys(response).length === 1) {  // Right now, there is always a default form called "Inquiry Form" so if it is only one, then we didn't get any forms back.
                    setFormLoadError(__('No forms found. Please check your credentials and try again.', 'bu-liaison-inquiry'));
                    return;
                }
                
                const formOptions = Object.entries(response).map(([name, id]) => ({
                    label: name + (id ? `: ${id}` : ''),
                    value: id || 'default'
                }));
                
                setForms(formOptions);
            } catch (err) {
                if (isMounted) {
                    setFormLoadError(err.message || __('Failed to load forms. Please check your credentials and try again.', 'bu-liaison-inquiry'));
                    console.error('Error loading forms:', err);
                }
            } finally {
                if (isMounted) {
                    setIsLoadingForms(false);
                }
            }
        };

        loadForms();
    }, [isOpen, isMounted, orgKey]);

    // Load fields when form is selected
    useEffect(() => {
        const loadFields = async () => {
            if (!selectedForm) return;
            
            try {
                setIsLoadingFields(true);
                setError(null);
                
                const response = await apiFetch({
                    path: `/bu-liaison-inquiry/v1/forms/${selectedForm}/fields${orgKey ? `?org_key=${orgKey}` : ''}`,
                });
                
                if (!isMounted) return;
                
                setFields(response);
            } catch (err) {
                if (isMounted) {
                    setError(err.message);
                    console.error('Error loading fields:', err);
                }
            } finally {
                if (isMounted) {
                    setIsLoadingFields(false);
                }
            }
        };

        loadFields();
    }, [selectedForm, isMounted, orgKey]);

    const handleFormChange = (value) => {
        if (!isLoadingForms && !isLoadingFields) {
            setSelectedForm(value);
            setFields(null);
        }
    };

    const generateShortcode = () => {
        if (!selectedForm || selectedForm === 'default') {
            return '[liaison_inquiry_form]';
        }
        return `[liaison_inquiry_form form_id="${selectedForm}"]`;
    };

    const handleClose = () => {
        if (!isLoadingForms && !isLoadingFields) {
            onClose();
        }
    };

    if (!isOpen) return null;

    const isLoading = isLoadingForms || isLoadingFields;

    return (
        <Modal
            title={orgKey 
                ? __('Browse Forms for ' + orgKey, 'bu-liaison-inquiry')
                : __('Browse Default Forms', 'bu-liaison-inquiry')
            }
            onRequestClose={handleClose}
            className="bu-liaison-form-browser-modal"
        >
            {formLoadError && (
                <Notice 
                    status="error" 
                    isDismissible={false}
                    className="form-browser-error"
                >
                    {formLoadError}
                </Notice>
            )}

            {error && (
                <Notice 
                    status="error" 
                    isDismissible={false}
                    className="form-browser-error"
                >
                    {error}
                </Notice>
            )}

            <div className="form-browser-content">
                <SelectControl
                    label={__('Select a form:', 'bu-liaison-inquiry')}
                    value={selectedForm}
                    options={[
                        { label: __('Select', 'bu-liaison-inquiry'), value: '' },
                        ...forms
                    ]}
                    onChange={handleFormChange}
                    disabled={isLoading}
                />

                {selectedForm && (
                    <div className="shortcode-section">
                        <h3>{__('Sample shortcode', 'bu-liaison-inquiry')}</h3>
                        <code>{generateShortcode()}</code>
                        <Button
                            isSecondary
                            onClick={() => {
                                navigator.clipboard.writeText(generateShortcode());
                            }}
                            style={{ marginLeft: '8px' }}
                            disabled={isLoading}
                        >
                            {__('Copy', 'bu-liaison-inquiry')}
                        </Button>
                    </div>
                )}

                {isLoading && <Spinner />}

                {fields && !isLoading && (
                    <div className="field-inventory">
                        <h3>{__('Field inventory', 'bu-liaison-inquiry')}</h3>
                        {fields.sections.map(section => (
                            <div key={section.id || section.name} className="field-section">
                                <h4>{section.name}</h4>
                                {section.fields.map(field => (
                                    <div key={field.id} className="field-item">
                                        <span className="field-name">{field.displayName}</span>
                                        <code className="field-id">{field.id}</code>
                                    </div>
                                ))}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </Modal>
    );
}

export default FormBrowser;
