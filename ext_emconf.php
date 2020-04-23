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

$EM_CONF[$_EXTKEY] = [
    'title' => 'WebAuthn Backend Login',
    'description' => 'Backend Users can login using a WebAuthn compatible security key.',
    'category' => 'be',
    'state' => 'stable',
    'author' => 'CARL von CHIARI GmbH',
    'author_email' => 'opensource@cvc.digital',
    'version' => '1.2.0',
    'uploadfolder' => true,
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
            'extbase' => '9.5.0-10.4.99',
            'backend' => '9.5.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
