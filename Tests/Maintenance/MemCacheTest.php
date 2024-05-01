<?php

namespace Lexik\Bundle\MaintenanceBundle\Tests\Maintenance;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Lexik\Bundle\MaintenanceBundle\Drivers\MemCacheDriver;
use PHPUnit\Framework\TestCase;

/**
 * Test mem cache
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class MemCacheTest extends TestCase
{
    public function testConstructWithNotKeyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $memC = new MemCacheDriver([]);
    }

    public function testConstructWithNotHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $memC = new MemCacheDriver(['key_name' => 'mnt']);
    }

    public function testConstructWithNotPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $memC = new MemCacheDriver(['key_name' => 'mnt', 'host' => '127.0.0.1']);
    }

    public function testConstructWithNotPortNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $memC = new MemCacheDriver(['key_name' => 'mnt', 'host' => '127.0.0.1', 'port' => 'roti']);
    }

    protected function initContainer(): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'          => false,
            'kernel.bundles'        => ['MaintenanceBundle' => 'Lexik\Bundle\MaintenanceBundle'],
            'kernel.cache_dir'      => sys_get_temp_dir(),
            'kernel.environment'    => 'dev',
            'kernel.root_dir'       => __DIR__.'/../../../../', // src dir
            'kernel.default_locale' => 'fr',
        ]));
    }
}
