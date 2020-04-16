# WebAuthn TYPO3 Extension

[![Build Status](https://travis-ci.org/cvc-digital/typo3-webauthn.svg?branch=master)](https://travis-ci.org/cvc-digital/typo3-webauthn)
[![GitHub](https://img.shields.io/github/license/cvc-digital/typo3-webauthn)](https://github.com/cvc-digital/typo3-webauthn/blob/master/LICENSE)
[![TYPO3 Version](https://img.shields.io/badge/TYPO3-%5E9.5-orange)](https://extensions.typo3.org/extension/cvc_webauthn)
[![codecov](https://codecov.io/gh/cvc-digital/typo3-webauthn/branch/master/graph/badge.svg)](https://codecov.io/gh/cvc-digital/typo3-webauthn)
[![packagist](https://img.shields.io/packagist/v/cvc/typo3-webauthn)](https://packagist.org/packages/cvc/typo3-webauthn)

This TYPO3 extension gives access to a second login form which uses the [WebAuthn standard](https://webauthn.io).
Backend users are able to login using a WebAuthn Authenticator. They also have the ability to register one or more WebAuthn Authenticators.
This is achieved by using the [Webauthn Framework](https://github.com/web-auth/webauthn-framework).

## Installation

This extension only works when installed in composer mode. If you are not familiar using composer together with TYPO3
yet, you can find a [how to on the TYPO3 website](https://composer.typo3.org/).

You can install the extension with the following command:

```
composer require cvc/typo3-webauthn
```

The Extension does not require any further configuration. After it is activated, a second login provider can be used to login using a WebAuthn authenticator.

## Configuration

You can reach the extension configuration under "settings" in the Install tool of TYPO3.

The following options are available:

* `secondFactorLogin`: If `true`, then the user must provide their username, password and WebAuthn authenticator in order to login.
If the user has not registered any authenticator yet, then they can login without the authenticator as a second factor.
If `false`, then the user can decide whether to login with their username and password or with their WebAuthn authenticator only.

## Usage

### Login

To Login with your previously registered WebAuthn Authenticator, you have to select "Login with WebAuthn" on the login screen. Enter your username, press enter, and follow the instructions on the screen to proceed.

![Picture with login process](Documentation/images/login.png)

### Register credentials

In the backend module "WebAuthn Authenticators" new WebAuthn Authenticators can be registered. Press on register WebAuthn Authenticator.
If you want, you can enter a description for the credential you are about to register.

![Picture with registration process](Documentation/images/registration.png)
