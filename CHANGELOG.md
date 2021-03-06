# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.2.0

### Added

* The backend module shows a warning, when the user tries to register a key, but the connection is not secured by HTTPS.
* Support TYPO3 v10.4
* Support web-auth/webauthn-lib v3

## 1.1.0

### Added

* Optional second factor authentication using username, password and authenticator. [#5](https://github.com/cvc-digital/typo3-webauthn/pull/5)

## 1.0.3

### Added

* PHP 7.4 compatibility [#1](https://github.com/cvc-digital/typo3-webauthn/pull/1)

### Fixed

* Removed version constraint from `ext-json`
* Start authentication process when form is submitted (e.g. by pressing the enter key) [#2](https://github.com/cvc-digital/typo3-webauthn/pull/2)

## 1.0.2

### Added

* Make extension documentation available on [docs.typo3.org](https://docs.typo3.org/p/cvc/typo3-webauthn/master/en-us/).

## 1.0.1

### Changed

* Changed status from alpha to stable.

## 1.0.0

### Added

* Initial release.
