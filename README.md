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
