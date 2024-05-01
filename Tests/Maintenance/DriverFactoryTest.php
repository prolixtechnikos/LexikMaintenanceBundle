<?php

namespace Lexik\Bundle\MaintenanceBundle\Tests\Maintenance;

use ErrorException;
use Lexik\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Lexik\Bundle\MaintenanceBundle\Tests\TestHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Translation\IdentityTranslator;
use Lexik\Bundle\MaintenanceBundle\Drivers\FileDriver;
use PHPUnit\Framework\TestCase;
use Lexik\Bundle\MaintenanceBundle\Drivers\DatabaseDriver;

/**
 * Test driver factory
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class DriverFactoryTest extends TestCase
{
    protected ?DriverFactory $factory;
    protected ?ContainerBuilder $container;

    public function setUp(): void
    {
        $driverOptions = [
            'class' => FileDriver::class,
            'options' => ['file_path' => sys_get_temp_dir().'/lock']
        ];

        $this->container = $this->initContainer();

        $this->factory = new DriverFactory($this->getDatabaseDriver(), $this->getTranslator(), $driverOptions);
        $this->container->set('lexik_maintenance.driver.factory', $this->factory);
    }

    protected function tearDown(): void
    {
        $this->factory = null;
    }

    public function testDriver(): void
    {
        $driver = $this->factory->getDriver();
        $this->assertInstanceOf(FileDriver::class, $driver);
    }

    public function testExceptionConstructor(): void
    {
        $this->expectException(ErrorException::class);
        $factory = new DriverFactory($this->getDatabaseDriver(), $this->getTranslator(), array());
    }

    public function testWithDatabaseChoice(): void
    {
        $driverOptions = array('class' => DriverFactory::DATABASE_DRIVER, 'options' => null);

        $factory = new DriverFactory($this->getDatabaseDriver(), $this->getTranslator(), $driverOptions);

        $this->container->set('lexik_maintenance.driver.factory', $factory);

        $this->assertInstanceOf(DatabaseDriver::class, $factory->getDriver());
    }

    public function testExceptionGetDriver(): void
    {
        $driverOptions = array('class' => '\Unknown', 'options' => null);

        $factory = new DriverFactory($this->getDatabaseDriver(), $this->getTranslator(), $driverOptions);
        $this->container->set('lexik_maintenance.driver.factory', $factory);

        try {
            $factory->getDriver();
        } catch (\ErrorException $expected) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
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

    protected function getDatabaseDriver()
    {
        return $this->getMockbuilder(DatabaseDriver::class)
                ->disableOriginalConstructor()
                ->getMock();
    }

    public function getTranslator(): Translator
    {
        /** @var IdentityTranslator|MockObject $identityTranslator */
        $identityTranslator = $this->getMockBuilder(IdentityTranslator::class)
            ->disableOriginalConstructor()
            ->getMock();

        return TestHelper::getTranslator($this->container, $identityTranslator);
    }
}
