<?php

/*
 * WebAuthn extension for TYPO3 CMS
 * Copyright (C) 2020 CARL von CHIARI GmbH
 *
 * This file is part of the TYPO3 CMS project.
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Cvc\Typo3\CvcWebauthn\Factory;

use Composer\Semver\Comparator;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Jean85\PrettyVersions;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

/**
 * @internal
 */
abstract class Factory
{
    protected $publicKeyCredentialSourceRepository;

    public function __construct(PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository)
    {
        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository;
    }

    abstract public function getAuthenticatorAssertionResponseValidator(): AuthenticatorAssertionResponseValidator;

    abstract public function getAttestationStatementSupportManager(): AttestationStatementSupportManager;

    public static function createFactory(PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository): self
    {
        $webAuthnLibVersion = PrettyVersions::getVersion('web-auth/webauthn-lib')->getShortVersion();
        $webAuthnLibVersion = ltrim($webAuthnLibVersion, 'v');

        if (Comparator::lessThan($webAuthnLibVersion, '3.0')) {
            return new Version2Factory($publicKeyCredentialSourceRepository);
        }

        return new Version3Factory($publicKeyCredentialSourceRepository);
    }

    public function getPublicKeyCredentialLoader(): PublicKeyCredentialLoader
    {
        return new PublicKeyCredentialLoader(
            new AttestationObjectLoader($this->getAttestationStatementSupportManager())
        );
    }

    public function getAuthenticatorAttestationResponseValidator(): AuthenticatorAttestationResponseValidator
    {
        return new AuthenticatorAttestationResponseValidator(
            $this->getAttestationStatementSupportManager(),
            $this->publicKeyCredentialSourceRepository,
            new TokenBindingNotSupportedHandler(),
            new ExtensionOutputCheckerHandler()
        );
    }

    public function getCoseAlgorithmManager(): Manager
    {
        $coseAlgorithmManager = new Manager();
        $coseAlgorithmManager->add(new ECDSA\ES256());
        $coseAlgorithmManager->add(new ECDSA\ES512());
        $coseAlgorithmManager->add(new EdDSA\EdDSA());
        $coseAlgorithmManager->add(new RSA\RS1());
        $coseAlgorithmManager->add(new RSA\RS256());
        $coseAlgorithmManager->add(new RSA\RS512());

        return $coseAlgorithmManager;
    }
}
