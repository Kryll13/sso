# SSO for Office 365 Tenant

Thanks to **stevenmaguire/oauth2-microsoft** library.

## Install with **Composer** :

```
composer require kryll13/sso
```

## Setup

Add those entries in the .env file and replace examples with your data.

```
APP_URL="https://app.example.com"
TENANT_ID="........-....-....-....-............"
APP_ID="........-....-....-....-............"
APP_SECRET="app_secret"
REDIRECT_URI="https://app.example.com"
AUTHORITY_URL="https://login.microsoftonline.com"
AUTHORITY_ENDPOINT_PATH="/oauth2/v2.0/authorize"
AUTHORITY_TOKEN_PATH="/oauth2/v2.0/token"
SCOPES="openid profile offline_access user.read"
AUTHORITY_LOGOUT_PATH="/oauth2/v2.0/logout?post_logout_redirect_uri="
```

## Usage

Instantiate Office365 class and call methods.

* login
* logout
* getUser
