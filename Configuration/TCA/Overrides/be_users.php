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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', [
    'tx_cvcwebauthn_keys' => [
        'label' => 'LLL:EXT:cvc_webauthn/Resources/Private/Language/locallang_tca.xlf:be_users.tx_cvcwebauthn_keys',
        'config' => [
            'type' => 'inline',
            'foreign_table' => 'tx_cvcwebauthn_keys',
            'foreign_field' => 'be_user',
            'maxitems' => 99,
            'appearance' => [
                'collapseAll' => true,
                'expandSingle' => true,
                'enabledControls' => [
                    'new' => false,
                ],
            ],
        ],
    ],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'be_users',
    'tx_cvcwebauthn_keys'
);
