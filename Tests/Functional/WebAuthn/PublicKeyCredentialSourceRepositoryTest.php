<?php

/*
 * WebAuthn extension for TYPO3 CMS
 * Copyright (C) 2019 CARL von CHIARI GmbH
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

namespace Cvc\Typo3\CvcWebauthn\Tests\WebAuthn;

use Cvc\Typo3\CvcWebauthn\WebAuthn\PublicKeyCredentialSourceRepository;
use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\CertificateTrustPath;

class PublicKeyCredentialSourceRepositoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/cvc_webauthn',
    ];

    public function testSaveCredentialSource()
    {
        $repository = new PublicKeyCredentialSourceRepository();

        $publicKeyCredentialSource1 = $this->initializeDummyPublicKeyCredentialSource();

        $repository->saveCredentialSource($publicKeyCredentialSource1);
        $result = $repository->findOneByCredentialId($publicKeyCredentialSource1->getPublicKeyCredentialId());
        $result = json_encode($result, JSON_PRETTY_PRINT);
        $expected = json_encode($publicKeyCredentialSource1, JSON_PRETTY_PRINT);

        static::assertSame($expected, $result);
    }

    public function testSaveIfKeyExists()
    {
        $repository = new PublicKeyCredentialSourceRepository();
        $credentialSourceId = base64_decode('eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==', true);
        $trustPath = new CertificateTrustPath(['TESTTEST', 'ABC']);

        $publicKeyCredentialSource1 = $this->initializeDummyPublicKeyCredentialSource();
        $publicKeyCredentialSource2 = new PublicKeyCredentialSource(
            $credentialSourceId,
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            [],
            AttestationStatement::TYPE_NONE,
            $trustPath,
            Uuid::fromBytes(base64_decode('BBBBBBBBBBBBBBBBBBBBBB==', true)),
            base64_decode('pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruTESTTESTTEST/nq63l8IMJcIdKDJcXRh9hoz0L+nVwP1Oxil3/oNQYs=', true),
            '1',
            100
        );

        $repository->saveCredentialSource($publicKeyCredentialSource1);
        $repository->saveCredentialSource($publicKeyCredentialSource2);
        $result = $repository->findOneByCredentialId($publicKeyCredentialSource2->getPublicKeyCredentialId());
        $result = json_encode($result, JSON_PRETTY_PRINT);
        $expected = json_encode($publicKeyCredentialSource2, JSON_PRETTY_PRINT);

        static::assertSame($expected, $result);
    }

    public function testUpdateKeyDescription()
    {
        $repository = new PublicKeyCredentialSourceRepository();
        $connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_cvcwebauthn_keys');

        $publicKeyCredentialSource1 = $this->initializeDummyPublicKeyCredentialSource();
        $repository->saveCredentialSource($publicKeyCredentialSource1);
        $repository->updateKeyDescription($publicKeyCredentialSource1, 'TEST');

        $queryBuilder = $connection->createQueryBuilder();
        $query = $queryBuilder
            ->select('description')
            ->from('tx_cvcwebauthn_keys')
            ->where(
                $queryBuilder->expr()->eq('public_key_credential_id', $queryBuilder->createNamedParameter(base64_encode($publicKeyCredentialSource1->getPublicKeyCredentialId())))
            )
            ->execute();

        $keyDescription = $query->fetchColumn();

        static::assertSame('TEST', $keyDescription);
    }

    public function testFindAllForUserEntity()
    {
        $repository = new PublicKeyCredentialSourceRepository();

        $publicKeyCredentialSource1 = $this->initializeDummyPublicKeyCredentialSource();
        $dummyUser = $this->initializeDummyUser();
        $repository->saveCredentialSource($publicKeyCredentialSource1);
        $result = $repository->findAllForUserEntity($dummyUser);

        static::assertNotEmpty($result);
    }

    private function initializeDummyPublicKeyCredentialSource()
    {
        $credentialSourceId = base64_decode('eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==', true);
        $trustPath = new CertificateTrustPath(['TESTTEST', 'ABC']);

        return new PublicKeyCredentialSource(
            $credentialSourceId,
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            [],
            AttestationStatement::TYPE_NONE,
            $trustPath,
            Uuid::fromBytes(base64_decode('AAAAAAAAAAAAAAAAAAAAAA==', true)),
            base64_decode('pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruKw4kWx7zIlgg/nq63l8IMJcIdKDJcXRh9hoz0L+nVwP1Oxil3/oNQYs=', true),
            '1',
            100
        );
    }

    private function initializeDummyUser(): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            'admin',
            1,
            'admin'
        );
    }
}
