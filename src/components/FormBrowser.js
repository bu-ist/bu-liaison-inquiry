/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
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
 * @return {JSX.Element} The form browser modal component.
 */
function FormBrowser({ isOpen, onClose }) {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const [forms, setForms] = useState([]);
    const [selectedForm, setSelectedForm] = useState('');
    const [fields, setFields] = useState(null);

    // Load forms when modal opens
    useEffect(() => {
        if (isOpen) {
            loadForms();
        }
    }, [isOpen]);

    const loadForms = async () => {
        try {
            setIsLoading(true);
            setError(null);
            
            const response = await apiFetch({
                path: '/bu-liaison-inquiry/v1/forms',
            });
            
            const formOptions = Object.entries(response).map(([name, id]) => ({
                label: name + (id ? `: ${id}` : ''),
                value: id || 'default'
            }));
            
            setForms(formOptions);
        } catch (err) {
            setError(err.message);
            console.error(err);
        } finally {
            setIsLoading(false);
        }
    };

    const loadFields = async (formId) => {
        if (!formId) return;
        
        try {
            setIsLoading(true);
            setError(null);
            
            const response = await apiFetch({
                path: `/bu-liaison-inquiry/v1/forms/${formId}/fields`,
            });
            
            setFields(response);
        } catch (err) {
            setError(err.message);
            console.error(err);
        } finally {
            setIsLoading(false);
        }
    };

    const handleFormChange = (value) => {
        setSelectedForm(value);
        setFields(null);
        loadFields(value);
    };

    const generateShortcode = () => {
        if (!selectedForm || selectedForm === 'default') {
            return '[liaison_inquiry_form]';
        }
        return `[liaison_inquiry_form form_id="${selectedForm}"]`;
    };

    if (!isOpen) return null;

    return (
        <Modal
            title={__('Browse Liaison Forms', 'bu-liaison-inquiry')}
            onRequestClose={onClose}
            className="bu-liaison-form-browser-modal"
        >
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
                            section.fields.map(field => (
                                <p key={field.id}>
                                    {field.displayName} = {field.id}
                                </p>
                            ))
                        ))}
                    </div>
                )}
            </div>
        </Modal>
    );
}

export default FormBrowser;
