/**
 * NPM dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, SelectControl, TextControl } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';

/**
 * Renders the credentials list and form.
 *
 * @return {JSX.Element} The credentials list component.
 */
export default function CredentialsList() {




    if (isLoading) {
        return <p>{__('Loading...', 'bu-liaison-inquiry')}</p>;
    }

    return (
        <div className="credentials-manager">
            <Card>
                <CardBody>
                    <h2>{__('Add Organization Credentials', 'bu-liaison-inquiry')}</h2>
                    <form onSubmit={handleSubmit}>
                        <TextControl
                            name="org_key"
                            label={__('Organization Key', 'bu-liaison-inquiry')}
                            required
                            style={{ maxWidth: '100px' }}
                        />
                        <TextControl
                            name="api_key"
                            label={__('API Key', 'bu-liaison-inquiry')}
                            required
                            style={{ maxWidth: '300px' }}
                        />
                        <TextControl
                            name="client_id"
                            label={__('Client ID', 'bu-liaison-inquiry')}
                            required
                            style={{ maxWidth: '60px' }}
                        />
                        <Button
                            isPrimary
                            type="submit"
                            disabled={addMutation.isLoading}
                        >
                            {__('Add Organization', 'bu-liaison-inquiry')}
                        </Button>
                    </form>
                </CardBody>
            </Card>

            <Card>
                <CardBody>
                    <h2>{__('Organization Credentials', 'bu-liaison-inquiry')}</h2>
                    <table className="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>{__('Organization Key', 'bu-liaison-inquiry')}</th>
                                <th>{__('API Key', 'bu-liaison-inquiry')}</th>
                                <th>{__('Client ID', 'bu-liaison-inquiry')}</th>
                                <th>{__('Actions', 'bu-liaison-inquiry')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {credentials.map((cred) => (
                                <tr key={cred.org_key}>
                                    <td>{cred.org_key}</td>
                                    <td>{cred.api_key}</td>
                                    <td>{cred.client_id}</td>
                                    <td>
                                        <Button
                                            isSecondary
                                            isDestructive
                                            onClick={() => {
                                                if (window.confirm(__('Are you sure you want to remove these credentials?', 'bu-liaison-inquiry'))) {
                                                    removeMutation.mutate(cred.org_key);
                                                }
                                            }}
                                            disabled={removeMutation.isLoading}
                                        >
                                            {__('Remove', 'bu-liaison-inquiry')}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardBody>
            </Card>
        </div>
    );
}
