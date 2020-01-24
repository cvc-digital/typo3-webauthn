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

namespace Cvc\Typo3\CvcWebauthn\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class KeyRepository extends Repository
{
    public function findKeyOwner(int $beUser): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('be_user', $beUser)
        );

        return $query->execute();
    }
}
