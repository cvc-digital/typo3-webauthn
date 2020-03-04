<?php
declare(strict_types=1);

return [
    \Cvc\Typo3\CvcWebauthn\Domain\Model\Key::class => [
        'tableName' => 'tx_cvcwebauthn_keys',
        'properties' => [
            'crdate' => [
                'fieldname' => 'crdate'
            ],
        ],
    ],
];
