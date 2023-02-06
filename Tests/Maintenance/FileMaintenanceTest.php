<?php

namespace Lexik\Bundle\MaintenanceBundle\Tests\Maintenance;

use InvalidArgumentException;
use Lexik\Bundle\MaintenanceBundle\Drivers\FileDriver;
use Lexik\Bundle\MaintenanceBundle\Tests\TestHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * Test driver file
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class FileMaintenanceTest extends TestCase
{
    static protected string $tmpDir;
    protected ?ContainerBuilder $container;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$tmpDir = sys_get_temp_dir().'/symfony2_finder';
    }

    public function setUp(): void
    {
        $this->container = $this->initContainer();
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testDecide(): void
    {
        $options = ['file_path' => self::$tmpDir.'/lock.lock'];

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());

        $this->assertTrue($fileM->decide());

        $options = ['file_path' => self::$tmpDir.'/clok'];

        $fileM2 = new FileDriver($options);
        $fileM2->setTranslator($this->getTranslator());
        $this->assertFalse($fileM2->decide());
    }

    public function testExceptionInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $fileM = new FileDriver(array());
        $fileM->setTranslator($this->getTranslator());
    }

    public function testLock(): void
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $this->assertFileExists($options['file_path']);
    }

    public function testUnlock(): void
    {
        $options = array('file_path' => self::$tmpDir.'/lock.lock');

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $fileM->unlock();

        $this->assertFileDoesNotExist($options['file_path']);
    }

    public function testIsExists(): void
    {
        $options = ['file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600];

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        $this->assertTrue($fileM->isEndTime(3600));
    }

    public function testMessages(): void
    {
        $options = ['file_path' => self::$tmpDir.'/lock.lock', 'ttl' => 3600];

        $fileM = new FileDriver($options);
        $fileM->setTranslator($this->getTranslator());
        $fileM->lock();

        // lock
        $this->assertEquals('lexik_maintenance.success_lock_file', $fileM->getMessageLock(true));
        $this->assertEquals('lexik_maintenance.not_success_lock', $fileM->getMessageLock(false));

        // unlock
        $this->assertEquals('lexik_maintenance.success_unlock', $fileM->getMessageUnlock(true));
        $this->assertEquals('lexik_maintenance.not_success_unlock', $fileM->getMessageUnlock(false));
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

    public function getTranslator(): Translator
    {
        /** @var IdentityTranslator|MockObject $identityTranslator */
        $identityTranslator = $this->getMockBuilder(IdentityTranslator::class)
            ->disableOriginalConstructor()
            ->getMock();

        return TestHelper::getTranslator($this->container, $identityTranslator);
    }
}
