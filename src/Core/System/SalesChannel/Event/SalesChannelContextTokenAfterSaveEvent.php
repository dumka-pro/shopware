<?php declare(strict_types=1);

namespace Shopware\Core\System\SalesChannel\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class SalesChannelContextTokenAfterSaveEvent extends Event
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var array<string, mixed>
     */
    protected $newParameters;

    /**
     * @var array<string, mixed>
     */
    protected $existing;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var string|null
     */
    protected $customerId;

    public function __construct(string $token, array $newParameters, array $existing, string $salesChannelId, ?string $customerId = null)
    {
        $this->token = $token;
        $this->newParameters = $newParameters;
        $this->existing = $existing;
        $this->salesChannelId = $salesChannelId;
        $this->customerId = $customerId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return array<string, mixed>
     */
    public function getNewParameters(): array
    {
        return $this->newParameters;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExisting(): array
    {
        return $this->existing;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }
}
