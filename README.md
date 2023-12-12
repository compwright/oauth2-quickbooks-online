# Quickbooks Online Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/release/compwright/oauth2-quickbooks-online.svg?style=flat-square)](https://github.com/compwright/oauth2-quickbooks-online/releases)
[![Build Status](https://app.travis-ci.com/compwright/oauth2-quickbooks-online.svg?branch=master)](https://app.travis-ci.com/github/compwright/oauth2-quickbooks-online)
[![Total Downloads](https://img.shields.io/packagist/dt/compwright/oauth2-quickbooks-online.svg?style=flat-square)](https://packagist.org/packages/compwright/oauth2-quickbooks-online)

This package provides Quickbooks Online OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require compwright/oauth2-quickbooks-online league/oauth2-client
```

## Usage

Usage is the same as The League's OAuth client, using `\Compwright\OAuth2_Quickbooks_Online\Provider` as the provider.

### Example: Authorization Code Flow

```php
$provider = new Compwright\OAuth2_Quickbooks_Online\Provider([
    'clientId'      => '{quickbooks-online-client-id}',
    'clientSecret'  => '{quickbooks-online-client-secret}',
    'redirectUri'   => 'https://example.com/callback-url'
]);

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}

// Check given state against previously stored one to mitigate CSRF attack
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

// Get an access token using the authorization code grant
$token = $provider->getAccessToken('authorization_code', [
    'code' => $_GET['code'],
    'state' => $_GET['state'],
    'realmId' => $_GET['realmId'], // required for getResourceOwner() to work
]);

// You can look up a users profile data
$user = $provider->getResourceOwner($token);
printf('Hello %s!', $user->getId());

// Use the token to interact with an API on the users behalf
echo $token->getToken();
```

## Testing

```bash
$ make test
```

## Contributing

Please see [CONTRIBUTING](https://github.com/compwright/oauth2-quickbooks-online/blob/master/CONTRIBUTING.md) for details.

## Credits

This package was forked from [chadhutchins/oauth2-quickbooks](https://github.com/chadhutchins/oauth2-quickbooks), which appeared to be abandoned as of December 2023.

- [Jonathon Hill](https://github.com/compwright)
- [All Contributors](https://github.com/compwright/oauth2-quickbooks-online/contributors)

## License

The MIT License (MIT). Please see [License File](https://github.com/compwright/oauth2-quickbooks-online/blob/master/LICENSE) for more information.
