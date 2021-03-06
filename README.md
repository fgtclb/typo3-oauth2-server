# OAuth2 Server for TYPO3

This package implements [OAuth2](https://oauth.net/2/) for TYPO3 to enable 3rd party services to authenticate users using frontend users in TYPO3.

## Installation

This package can be installed via Composer:

    composer require fgtclb/typo3-oauth2-server

For a fully working setup a [RSA keypair needs to be generated](fgtclb/typo3-oauth2-server) and set in the extension configuration:

    # Generate random private key
    openssl genrsa -out private.key 2048
    # Extract public key from private key
    openssl rsa -in private.key -pubout -out public.key

This keypair *must be stored safely* which means outside of the TYPO3 web directory and should be readonly.

## Configuration

### Extension settings

1. Set the paths to your private and public key files.
2. Set the page id where your login form is located. The middleware will redirect users to this page.

### OAuth2 Client access

To register clients in the OAuth2 server you need to create *OAuth2 Client* records on the root page accordingly. Here you can set the identifier and secret as well as redirect URLs to be used in your client code.

## Endpoints

After installation the following endpoints are available and should be set in the 3rd party services:

1. `/oauth/authorize`: endpoint for authorization code requests
2. `/oauth/token`: endpoint for access token requests using a authorization code
3. `/oauth/identity`: endpoint for retrieving a username using an access token

Currently only the [authorization code grant](https://oauth2.thephpleague.com/authorization-server/auth-code-grant/) is available.
