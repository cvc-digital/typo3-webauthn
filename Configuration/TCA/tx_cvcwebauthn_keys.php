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

return [
    'ctrl' => [
        'title' => 'LLL:EXT:cvc_webauthn/Resources/Private/Language/locallang_tca.xlf:tx_cvc_webauthn_keys.title',
        'label' => 'description',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:cvc_webauthn/Resources/Public/Icons/tx_cvcwebauthn_keys.svg',
        'rootLevel' => 1,
        'adminOnly' => 1,
    ],
    'interface' => [
        'showRecordFieldList' => 'headline',
    ],
    'columns' => [
        'description' => [
            'label' => 'LLL:EXT:cvc_webauthn/Resources/Private/Language/locallang_tca.xlf:tx_cvc_webauthn_keys.description',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => '50',
                'eval' => 'trim',
            ],
        ],
        'content' => [
            'label' => 'LLL:EXT:cvc_webauthn/Resources/Private/Language/locallang_tca.xlf:tx_cvc_webauthn_keys.content',
            'config' => [
                'readOnly' => true,
                'type' => 'text',
                'eval' => 'trim',
            ],
        ],
        'be_user' => [
            'label' => 'LLL:EXT:cvc_webauthn/Resources/Private/Language/locallang_tca.xlf:tx_cvc_webauthn_keys.beUser',
            'config' => [
                'readOnly' => true,
                'eval' => 'int',
                'type' => 'select',
                'foreign_table' => 'be_users',
                'foreign_field' => 'uid',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'description, content, be_user',
        ],
    ],
];
