<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Metadata;

use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\TimeBasedUidInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
trait ForkAwareTrait
{
    private (AbstractUid&TimeBasedUidInterface)|null $forkedFrom = null;

    public function setForkedFrom((AbstractUid&TimeBasedUidInterface)|null $forkedFrom): void
    {
        $this->forkedFrom = $forkedFrom;
    }

    public function getForkedFrom(): (AbstractUid&TimeBasedUidInterface)|null
    {
        return $this->forkedFrom;
    }
}
