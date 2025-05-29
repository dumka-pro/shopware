<?php declare(strict_types=1);

namespace Shopware\Core\System\SalesChannel\Event;

use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class SalesChannelContextTokenAfterRevokeAllEvent extends Event
{
    /**
     * @var string|null
     */
    protected $customerId;

    /**
     * @var array<array<string, mixed>>
     */
    protected $revokedTokens;

    public function __construct(string $customerId, array $revokedTokens)
    {
        $this->customerId = $customerId;
        $this->revokedTokens = $revokedTokens;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getRevokedTokens(): array
    {
        return $this->revokedTokens;
    }
}
