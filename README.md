# TYPO3 Extension `OAuth2 Server`

|                 | URL                                                                                            |
|-----------------|------------------------------------------------------------------------------------------------|
| **Repository:** | [https://github.com/fgtclb/typo3-oauth2-server](https://github.com/fgtclb/typo3-oauth2-server) |
| **TER:**        | -                                                                                              |

## Description

This package implements [OAuth2](https://oauth.net/2/) for TYPO3 to enable 3rd party services to authenticate users
using frontend users in TYPO3.

## Compatibility

| Branch                                                    | Version   | TYPO3      | PHP                                               |
|-----------------------------------------------------------|-----------|------------|---------------------------------------------------|
| [main](https://github.com/fgtclb/typo3-oauth2-server)     | 2.0.x-dev | ~v11, ~v12 | 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| [1](https://github.com/fgtclb/typo3-oauth2-server/tree/1) | 1.0.x-dev | ~v9        | 7.2, 7.3, 7.4                                     |

## Installation

This package can be installed via Composer:

```shell
composer require 'fgtclb/typo3-oauth2-server':'^2'
```

For a fully working setup a [RSA keypair needs to be generated](fgtclb/typo3-oauth2-server) and set
in the extension configuration:

```shell
# Generate random private key
openssl genrsa -out private.key 2048
# Extract public key from private key
openssl rsa -in private.key -pubout -out public.key
```

This keypair *must be stored safely* which means outside of the TYPO3 web directory and should be readonly.

> [!IMPORTANT]
> `2.x.x` is still in development and not all academics extension are fully tested in v12 and v13,
> but can be installed in composer instances to use/test them. Testing and reporting are welcome.

**Testing 2.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require 'fgtclb/typo3-oauth2-server':'2.*.*@dev'
```


## Configuration

### Extension settings

1. Set the paths to your private and public key files.
2. Set the page id where your login form is located. The middleware will redirect users to this page.

### OAuth2 Client access

To register clients in the OAuth2 server you need to create *OAuth2 Client* records on the root page accordingly.
Here you can set the identifier and secret as well as redirect URLs to be used in your client code.

## Endpoints

After installation the following endpoints are available and should be set in the 3rd party services:

1. `/oauth/authorize`: endpoint for authorization code requests
2. `/oauth/token`: endpoint for access token requests using a authorization code
3. `/oauth/identity`: endpoint for retrieving a username using an access token

Currently only the [authorization code grant](https://oauth2.thephpleague.com/authorization-server/auth-code-grant/) is available.

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
