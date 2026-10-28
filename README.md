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
| [main](https://github.com/fgtclb/typo3-oauth2-server)     | 3.0.x-dev | ~v12, ~v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3)      |
| [2](https://github.com/fgtclb/typo3-oauth2-server/tree/2) | 2.0.x-dev | ~v11, ~v12 | 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| [1](https://github.com/fgtclb/typo3-oauth2-server/tree/1) | 1.0.x-dev | ~v9        | 7.2, 7.3, 7.4                                     |

## Installation

This package can be installed via Composer:

```shell
composer require 'fgtclb/typo3-oauth2-server':'3.0.*@dev'
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
> `3.x.x` is still in development and not yet fully tested in v12 and v13,
> but can be installed in composer instances to use/test it. Testing and reporting are welcome.

**Testing 3.x.x extension version in projects (composer mode)**

It is already possible to use and test the `3.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring the extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require 'fgtclb/typo3-oauth2-server':'3.*.*@dev'
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

## Create a release (maintainers only)

Prerequisites:

* git binary
* ssh key allowed to push new branches to the repository
* GitHub command line tool `gh` installed and configured with user having permission to create pull requests.

**Prepare release locally**

> Set `RELEASE_BRANCH` to branch release should happen, for example: 'main'.
> Set `RELEASE_VERSION` to release version working on, for example: '5.0.0'.

```bash
echo '>> Create release based on configuration' ; \
  RELEASE_BRANCH='main' ; \
  RELEASE_VERSION='3.0.0' ; \
  DEV_VERSION='3.0.1' ; \
  echo ">> Checkout branches" && \
  git checkout main && \
  git fetch --all && \
  git pull --rebase && \
  git checkout ${RELEASE_BRANCH} && \
  git pull --rebase && \
  echo ">> Create release ${RELEASE_VERSION}" && \
  git checkout -b release-${RELEASE_VERSION} && \
  sed -i "s/^COMPOSER_ROOT_VERSION.*/COMPOSER_ROOT_VERSION=\"${RELEASE_VERSION}\"/" Build/Scripts/runTests.sh && \
  sed -i "s/^  RELEASE_VERSION.*/  RELEASE_VERSION='${RELEASE_VERSION}' ; \\\\/" README.md && \
  sed -i "s/^  DEV_VERSION.*/  DEV_VERSION='${DEV_VERSION}' ; \\\\/" README.md && \
  tailor set-version ${RELEASE_VERSION} && \
  composer config "extra"."typo3/cms"."version" "${RELEASE_VERSION}" && \
  echo "${RELEASE_VERSION}" > VERSION && \
  git add . && \
  git commit -m "[RELEASE] ${RELEASE_VERSION}" && \
  git push --set-upstream origin release-${RELEASE_VERSION} && \
  gh pr create --fill --base ${RELEASE_BRANCH} --title "[RELEASE] ${RELEASE_VERSION}" && \
  sleep 10 && \
  gh pr checks --watch --interval 2 && \
  sleep 10 && \
  gh pr merge -rd --admin && \
  git remote prune origin && \
  git tag ${RELEASE_VERSION} && \
  git push origin ${RELEASE_VERSION} && \
  echo ">> Post-release - set dev version: ${DEV_VERSION}-dev" && \
  git checkout -b set-dev-version-${DEV_VERSION} && \
  sed -i "s/^COMPOSER_ROOT_VERSION.*/COMPOSER_ROOT_VERSION=\"${DEV_VERSION}-dev\"/" Build/Scripts/runTests.sh && \
  tailor set-version ${DEV_VERSION} && \
  composer config "extra"."typo3/cms"."version" "${DEV_VERSION}-dev" && \
  echo "${DEV_VERSION}-dev" > VERSION && \
  git add . && \
  git commit -m "[TASK] Set dev version ${DEV_VERSION}" && \
  git push --set-upstream origin set-dev-version-${DEV_VERSION} && \
  gh pr create --fill --base ${RELEASE_BRANCH} --title "[TASK] Set dev version \"${DEV_VERSION}-dev\"" && \
  sleep 10 && \
  gh pr checks --watch --interval 2 && \
  sleep 10 && \
  gh pr merge -rd --admin && \
  git remote prune origin
```

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
