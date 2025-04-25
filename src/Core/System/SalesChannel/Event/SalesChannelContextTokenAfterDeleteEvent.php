<?php declare(strict_types=1);

namespace Shopware\Core\System\SalesChannel\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class SalesChannelContextTokenAfterDeleteEvent extends Event
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string|null
     */
    protected $salesChannelId;

    /**
     * @var string|null
     */
    protected $customerId;

    public function __construct(string $token, ?string $salesChannelId, ?string $customerId)
    {
        $this->token = $token;
        $this->salesChannelId = $salesChannelId;
        $this->customerId = $customerId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }
}
