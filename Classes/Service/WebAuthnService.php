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

namespace Cvc\Typo3\CvcWebauthn\Service;

use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Cose\Algorithms;
use Cvc\Typo3\CvcWebauthn\WebAuthn\PublicKeyCredentialSourceRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

class WebAuthnService
{
    public const TIMEOUT = 60000;
    private const CHALLENGE_LENGTH = 32;

    /**
     * @var PublicKeyCredentialRpEntity
     */
    private $rpEntity;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $publicKeyCredentialSourceRepository;

    public function __construct(string $name, string $id, PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository)
    {
        $this->rpEntity = new PublicKeyCredentialRpEntity($name, $id);
        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository;
    }

    public function createCredentialCreationOptions(BackendUser $backendUser, ?string $challenge = null): PublicKeyCredentialCreationOptions
    {
        $publicKeyCredentialParameters = [
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256),
        ];

        $userEntity = $this->createUserEntity($backendUser);

        $publicKeySources = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $excludedCredentialDescriptors = array_map([$this, 'returnPublicKeyCredentialDescriptor'], $publicKeySources);

        return new PublicKeyCredentialCreationOptions(
            $this->rpEntity,
            $userEntity,
            $challenge ?? $this->createChellenge(),
            $publicKeyCredentialParameters,
            static::TIMEOUT,
            $excludedCredentialDescriptors,
            new AuthenticatorSelectionCriteria(),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            new AuthenticationExtensionsClientInputs()
        );
    }

    public function createCredentialsRequestOptions(BackendUser $backendUser, string $challenge = null): PublicKeyCredentialRequestOptions
    {
        $userEntity = $this->createUserEntity($backendUser);
        $publicKeySources = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $registeredPublicKeyCredentialDescriptors = array_map([$this, 'returnPublicKeyCredentialDescriptor'], $publicKeySources);

        $publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
            $challenge ?? $this->createChellenge(),
            self::TIMEOUT,
            $this->rpEntity->getId(),
            $registeredPublicKeyCredentialDescriptors,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED
        );

        return $publicKeyCredentialRequestOptions;
    }

    public function register(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data, string $keyDescription)
    {
        [$publicKeyCredentialLoader, $authenticatorAttestationResponseValidator] = $this->createLoaderAndValidator();

        // init PSR7 request
        try {
            $psr7Request = new ServerRequest(new Uri('https://'.$this->rpEntity->getId()));

            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->load($data);
            $response = $publicKeyCredential->getResponse();

            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new \RuntimeException('Not an authenticator attestation response');
            }

            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $psr7Request);

            $this->publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);
            $this->publicKeyCredentialSourceRepository->updateKeyDescription($publicKeyCredentialSource, $keyDescription);
        } catch (\Throwable $exception) {
            throw new WebAuthnException($exception->getMessage(), 1569585845, $exception);
        }
    }

    public function authenticate(PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, string $data, BackendUser $beUser)
    {
        $user = $this->createUserEntity($beUser);
        [$publicKeyCredentialLoader, $authenticatorAssertionResponseValidator] = $this->createaLoaderAndAssertionValidator();

        // init PSR7 request
        try {
            $psr7Request = new ServerRequest(new Uri('https://'.$this->rpEntity->getId()));

            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->load($data);
            $assertionResponse = $publicKeyCredential->getResponse();

            if (!$assertionResponse instanceof AuthenticatorAssertionResponse) {
                throw new \RuntimeException('Not an authenticator assertion response');
            }

            $authenticatorAssertionResponseValidator->check(
                $publicKeyCredential->getRawId(),
                $publicKeyCredential->getResponse(),
                $publicKeyCredentialRequestOptions,
                $psr7Request,
                $user->getId()
            );
        } catch (\Throwable $exception) {
            throw new WebAuthnException($exception->getMessage(), 1569585846, $exception);
        }
    }

    public function createUserEntity(BackendUser $backendUser): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $backendUser->getUserName(),
            (string) $backendUser->getUid(),
            $backendUser->getRealName() ?: $backendUser->getUserName()
        );
    }

    private function createChellenge(): string
    {
        return bin2hex(random_bytes(static::CHALLENGE_LENGTH));
    }

    private function createLoaderAndValidator(): array
    {
        $coseAlgorithmManager = $this->createCoseAlgorithmManager();

        // Create a CBOR Decoder object
        $decoder = new Decoder(new TagObjectManager(), new OtherObjectManager());

        // The token binding handler
        $tokenBindnigHandler = new TokenBindingNotSupportedHandler();

        // Attestation Statement Support Manager
        $attestationStatementSupportManager = $this->createAttestationStatementSupportManager($coseAlgorithmManager);

        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);

        // Extension Output Checker Handler
        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $this->publicKeyCredentialSourceRepository,
            $tokenBindnigHandler,
            $extensionOutputCheckerHandler
        );

        return [$publicKeyCredentialLoader, $authenticatorAttestationResponseValidator];
    }

    private function createaLoaderAndAssertionValidator(): array
    {
        // Cose Algorithm Manager
        $coseAlgorithmManager = $this->createCoseAlgorithmManager();

        // Create a CBOR Decoder object
        $decoder = new Decoder(new TagObjectManager(), new OtherObjectManager());

        // The token binding handler
        $tokenBindingHandler = new TokenBindingNotSupportedHandler();

        // Attestation Statement Support Manager
        $attestationStatementSupportManager = $this->createAttestationStatementSupportManager($coseAlgorithmManager);

        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);

        // Public Key Credential Source Repository
        $publicKeyCredentialSourceRepository = $this->publicKeyCredentialSourceRepository;

        // Extension Output Checker Handler
        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Assertion Response Validator
        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            $publicKeyCredentialSourceRepository,
            $tokenBindingHandler,
            $extensionOutputCheckerHandler,
            $coseAlgorithmManager
        );

        return [$publicKeyCredentialLoader, $authenticatorAssertionResponseValidator];
    }

    private function createCoseAlgorithmManager(): Manager
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

    private function createAttestationStatementSupportManager(Manager $coseAlgorithmManager): AttestationStatementSupportManager
    {
        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport());
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport());
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport());
        $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($coseAlgorithmManager));

        return $attestationStatementSupportManager;
    }
}
