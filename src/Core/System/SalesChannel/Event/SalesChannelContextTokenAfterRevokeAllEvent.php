<?php declare(strict_types=1);

namespace Shopware\Core\System\SalesChannel\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class SalesChannelContextTokenAfterRevokeAllEvent extends Event
{
    /**
     * @var array<array<string, mixed>>
     */
    protected $revokedTokens;

    public function __construct(array $revokedTokens)
    {
        $this->revokedTokens = $revokedTokens;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getRevokedTokens(): array
    {
        return $this->revokedTokens;
    }
}
