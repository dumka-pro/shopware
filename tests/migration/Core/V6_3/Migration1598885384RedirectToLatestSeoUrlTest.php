<?php declare(strict_types=1);

namespace Shopware\Tests\Migration\Core\V6_3;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\V6_3\Migration1598885384RedirectToLatestSeoUrl;
use Shopware\Tests\Migration\MigrationTestTrait;

/**
 * @internal
 *
 * @covers \Shopware\Core\Migration\V6_3\Migration1598885384RedirectToLatestSeoUrl
 */
class Migration1598885384RedirectToLatestSeoUrlTest extends TestCase
{
    use MigrationTestTrait;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = KernelLifecycleManager::getConnection();
    }

    /**
     * @before
     */
    public function initialise(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $this->removeConfig($connection);
        $this->removeAllSeoUrls($connection);
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function testConfigValuesStateIsCorrectDependingOnSeoUrls(bool $seoUrlPresent): void
    {
        $migration = new Migration1598885384RedirectToLatestSeoUrl();

        if ($seoUrlPresent) {
            $this->insertSeoUrl($this->connection);
        }

        $migration->update($this->connection);

        static::assertSame(
            !$seoUrlPresent,
            $this->readConfigValue($this->connection),
            $seoUrlPresent
                ? 'The "Redirect to latest SEO-URL" setting should have been inactive, since there are already SEO-URLs present.'
                : 'The "Redirect to latest SEO-URL" setting should have been active, since there are no SEO-URLs present.'
        );
    }

    private function removeConfig(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `system_config` WHERE `configuration_key` = :config_key;',
            ['config_key' => Migration1598885384RedirectToLatestSeoUrl::CONFIG_KEY]
        );
    }

    private function removeAllSeoUrls(Connection $connection): void
    {
        $connection->executeStatement('DELETE FROM `seo_url`;');
    }

    private function insertSeoUrl(Connection $connection): void
    {
        $connection->insert('seo_url', [
            'id' => Uuid::randomBytes(),
            'language_id' => $connection->fetchOne('SELECT `id` FROM `language` LIMIT 1;'),
            'sales_channel_id' => null,
            'foreign_key' => Uuid::randomBytes(),
            'route_name' => 'route_name_Migration1598885384',
            'path_info' => 'path_info_Migration1598885384',
            'seo_path_info' => 'seo_path_info_Migration1598885384',
            'is_canonical' => null,
            'is_modified' => 0,
            'is_deleted' => 0,
            'custom_fields' => null,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'updated_at' => null,
        ]);
    }

    private function readConfigValue(Connection $connection): bool
    {
        $val = $connection->fetchOne(
            'SELECT `configuration_value` FROM `system_config` WHERE `configuration_key` = :config_key LIMIT 1;',
            ['config_key' => Migration1598885384RedirectToLatestSeoUrl::CONFIG_KEY]
        );
        static::assertIsString($val);

        if ($val === '{"_value": true}') {
            return true;
        }

        if ($val === '{"_value": false}') {
            return false;
        }

        throw new \UnexpectedValueException(sprintf('Unexpected value \'%s\' for setting: %s', $val, Migration1598885384RedirectToLatestSeoUrl::CONFIG_KEY));
    }
}
