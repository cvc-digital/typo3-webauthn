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

namespace Cvc\Typo3\CvcWebauthn\WebAuthn;

use Doctrine\DBAL\FetchMode;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

class PublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct()
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->connection = $connectionPool->getConnectionForTable('tx_cvcwebauthn_keys');
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->select('content')
            ->from('tx_cvcwebauthn_keys')
            ->where(
                $queryBuilder->expr()->eq('public_key_credential_id', $queryBuilder->createNamedParameter(base64_encode($publicKeyCredentialId)))
            )
            ->execute();

        $publicKeyCredentialSource = $query->fetchColumn();
        if (!$publicKeyCredentialSource) {
            return null;
        }
        $publicKeyCredentialSource = $this->createCredentialSourceFromString($publicKeyCredentialSource);

        return $publicKeyCredentialSource;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $query = $queryBuilder
            ->select('content', 'uid')
            ->from('tx_cvcwebauthn_keys')
            ->where(
                $queryBuilder->expr()->eq('be_user', $queryBuilder->createNamedParameter($publicKeyCredentialUserEntity->getId()))
            )
            ->execute();

        $publicKeyCredentialSources = $query->fetchAll(FetchMode::COLUMN);
        $publicKeyCredentialSources = array_map([$this, 'createCredentialSourceFromString'], $publicKeyCredentialSources);

        return $publicKeyCredentialSources;
    }

    public function updateKeyDescription(PublicKeyCredentialSource $publicKeyCredentialSource, string $keyDescription): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->update('tx_cvcwebauthn_keys')
            ->where(
                $queryBuilder->expr()->eq('public_key_credential_id', $queryBuilder->createNamedParameter(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId())))
            )
            ->set('description', $keyDescription)
            ->execute();
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $timestamp = $context->getPropertyFromAspect('date', 'timestamp');

        $queryBuilder = $this->connection->createQueryBuilder();
        $isKeyAlreadySaved = $this->findOneByCredentialId($publicKeyCredentialSource->getPublicKeyCredentialId());
        if ($isKeyAlreadySaved !== null) {
            $queryBuilder
                ->update('tx_cvcwebauthn_keys')
                ->where(
                    $queryBuilder->expr()->eq('public_key_credential_id', $queryBuilder->createNamedParameter(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId())))
                )
                ->set('content', json_encode($publicKeyCredentialSource))
            ->execute();
        } else {
            $this->connection->insert('tx_cvcwebauthn_keys', [
                'be_user' => $publicKeyCredentialSource->getUserHandle(),
                'content' => json_encode($publicKeyCredentialSource),
                'description' => 'Key '.date('d.m.Y H:i:s'),
                'crdate' => $timestamp,
                'public_key_credential_id' => base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()),
            ]);
        }
    }

    private function createCredentialSourceFromString(string $json): PublicKeyCredentialSource
    {
        $array = json_decode($json, true);

        return PublicKeyCredentialSource::createFromArray($array);
    }
}
