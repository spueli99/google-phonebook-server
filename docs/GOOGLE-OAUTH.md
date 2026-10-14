# Google Cloud / OAuth setup

Phonebook Sync uses the Google People API and requests only read-only contact access.

Required OAuth scope:

```text
https://www.googleapis.com/auth/contacts.readonly
```

The application does not request permission to edit or delete Google contacts.

## 1. Create a Google Cloud project

Open Google Cloud Console and create or select a project dedicated to Phonebook Sync.

A separate project is recommended instead of reusing unrelated OAuth applications.

## 2. Enable Google People API

In the selected project open the API Library, search for:

```text
Google People API
```

and enable it.

## 3. Configure Google Auth Platform

Open:

```text
Google Auth Platform → Branding
```

If the platform has not yet been configured, start the setup.

Set at least:

- application name, for example `Phonebook Sync`
- user support email
- developer/contact email

## 4. Choose the audience

Open:

```text
Google Auth Platform → Audience
```

For an ordinary private Google account choose **External**.

If the application belongs to a Google Workspace/Cloud Identity organization and is intended only for users in that organization, `Internal` may be available instead.

### External + Testing

When the publishing status is **Testing**, only accounts listed as test users can authorize the application.

Add the Google account whose contacts will be synchronized under:

```text
Audience → Test users → Add users
```

Important: for an External app in **Testing** status, Google documents that refresh tokens normally expire after **7 days** when scopes beyond basic identity scopes are requested. `contacts.readonly` is such a scope. That means a cron-driven installation can stop synchronizing after about a week even though the initial login worked.

For unattended long-term use, review the application's publishing status in Google Auth Platform before relying on cron synchronization. Do not assume that adding yourself as a test user removes the seven-day refresh-token limit.

Google also documents a personal-use exception from OAuth verification for an app used only by you or a small number of people personally known to you. This is separate from the Testing refresh-token lifetime: an unverified personal-use app can still show an unverified-app warning and remains subject to Google's applicable limits. Follow the current Google Auth Platform guidance for the account and scope in use.

## 5. Configure data access

Open:

```text
Google Auth Platform → Data Access
```

Add only:

```text
https://www.googleapis.com/auth/contacts.readonly
```

Do not add the write scope `https://www.googleapis.com/auth/contacts` because Phonebook Sync does not need it.

## 6. Create the OAuth client

Open:

```text
Google Auth Platform → Clients
```

Create a new client:

```text
Application type: Web application
Name: Phonebook Sync Web
```

For this server-side application an Authorized JavaScript Origin is not required.

Under **Authorized redirect URIs**, add the exact callback URL:

```text
https://phonebook.example.de/oauth/callback.php
```

The scheme, hostname and path must match exactly.

## 7. Copy Client ID and Client Secret

After creating the client, copy:

- Client ID
- Client Secret

Store them only in `config/local.php`:

```php
'google' => [
    'client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'redirect_uri' => 'https://phonebook.example.de/oauth/callback.php',
    'scope' => 'https://www.googleapis.com/auth/contacts.readonly',
],
```

Never commit `config/local.php`.

## 8. Connect the account

Sign in to Phonebook Sync and choose **Google verbinden**.

Google will show the consent flow. Sign in with the account that contains the contacts and grant read-only contacts access.

If the application is External + Testing and the account is not configured as a test user, Google returns `403 access_denied`.

After success, Google redirects to:

```text
/oauth/callback.php
```

and Phonebook Sync stores the OAuth data in:

```text
storage/google_token.json
```

## 9. Run the first sync

Choose **Jetzt synchronisieren**.

The server requests contacts from People API, normalizes phone numbers and writes:

```text
storage/cache/contacts.json
```

## Troubleshooting

### `403 access_denied`

For an External app in Testing status, add the Google account under **Audience → Test users**.

### `redirect_uri_mismatch`

The redirect URI in Google Cloud must exactly equal the value in `config/local.php`, including `https://`, hostname and `/oauth/callback.php`.

### Google disconnects after about seven days

Check whether the OAuth application is still External + Testing. Google documents a seven-day lifetime for refresh tokens in this configuration when non-basic scopes such as contact access are used. For unattended synchronization, review the publishing status in Google Auth Platform and reconnect Google after changing the OAuth configuration if necessary.

### Contacts are missing

Phonebook Sync synchronizes the contacts returned through the People API contact connection list. Verify that the missing entry is actually stored as a Google Contact and contains a phone number.

## Official Google references

- OAuth 2.0 for Web Server Applications: https://developers.google.com/identity/protocols/oauth2/web-server
- OAuth scopes: https://developers.google.com/identity/protocols/oauth2/scopes
- People API: https://developers.google.com/people
- OAuth refresh-token lifetime: https://developers.google.com/identity/protocols/oauth2/#expiration
- Sensitive-scope verification and personal-use exceptions: https://developers.google.com/identity/protocols/oauth2/production-readiness/sensitive-scope-verification
