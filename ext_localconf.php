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

defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'Cvc.'.$_EXTKEY,
    'auth',
    Cvc\Typo3\CvcWebauthn\Service\WebAuthnAuthenticationService::class,
    [
        'title' => 'WebAuthn Credential Service',
        'description' => 'Manages login with WebAuthn Credentials',
        'subtype' => 'authUserBE',
        'available' => true,
        'priority' => 60,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'className' => Cvc\Typo3\CvcWebauthn\Service\WebAuthnAuthenticationService::class,
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1569568896] = [
    'provider' => \Cvc\Typo3\CvcWebauthn\LoginProvider\WebAuthnLoginProvider::class,
    'sorting' => 50,
    'icon-class' => 'fa-key',
    'label' => 'LLL:EXT:cvc_webauthn/Resources/Private/Language/locallang.xlf:login.link',
];

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['webauthn_challenges'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['webauthn_challenges'] = [
        'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'options' => [
            'defaultLifetime' => \Cvc\Typo3\CvcWebauthn\Service\WebAuthnService::TIMEOUT,
        ],
    ];
}
