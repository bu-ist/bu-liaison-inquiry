/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { 
    Card,
    CardBody,
    CardHeader,
    Button
} from '@wordpress/components';

/**
 * Credential Card component for displaying organization credentials.
 *
 * @param {Object} props Component properties.
 * @param {string} props.orgKey The organization key.
 * @param {Object} props.data The credential data.
 * @param {Function} props.onEdit Callback to edit the credential.
 * @param {Function} props.onDelete Callback to delete the credential.
 * @param {Function} props.onBrowseForms Callback to open form browser.
 * @param {boolean} props.disabled Whether the buttons are disabled.
 * @return {JSX.Element} The credential card component.
 */
function CredentialCard({ orgKey, data, onEdit, onDelete, onBrowseForms, disabled }) {
    return (
        <Card className="bu-liaison-credential-card" style={{ marginBottom: '15px' }}>
            <CardHeader>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h3 style={{ margin: 0 }}>{orgKey}</h3>
                    <div>
                        <Button
                            isSecondary // for 5.4 compatibility
                            variant="secondary"
                            onClick={() => onBrowseForms(orgKey)}
                            disabled={disabled}
                            style={{ marginRight: '8px' }}
                        >
                            {__('Browse Forms', 'bu-liaison-inquiry')}
                        </Button>
                        <Button
                            isSecondary // for 5.4 compatibility
                            variant="secondary"
                            onClick={() => onEdit(orgKey)}
                            disabled={disabled}
                            style={{ marginRight: '8px' }}
                        >
                            {__('Edit', 'bu-liaison-inquiry')}
                        </Button>
                        <Button
                            isDestructive
                            variant="secondary"
                            onClick={() => onDelete(orgKey)}
                            disabled={disabled}
                        >
                            {__('Remove', 'bu-liaison-inquiry')}
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardBody>
                <div className="bu-liaison-credential-info">
                    <div className="bu-liaison-credential-field">
                        <strong>{__('API Key:', 'bu-liaison-inquiry')}</strong>                         
                        {" "}  {data.APIKey || __('Not set', 'bu-liaison-inquiry')}
                    </div>
                    <div className="bu-liaison-credential-field">
                        <strong>{__('Client ID:', 'bu-liaison-inquiry')}</strong> 
                        {data.ClientID || __('Not set', 'bu-liaison-inquiry')}
                    </div>
                </div>
            </CardBody>
        </Card>
    );
}

export default CredentialCard;
