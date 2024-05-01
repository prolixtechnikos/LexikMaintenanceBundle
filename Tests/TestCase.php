<?php

namespace Lexik\Bundle\MaintenanceBundle\Tests;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use App\Kernel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

require_once __DIR__.'/../../../../app/AppKernel.php';

/**
 * A PHPUnit testcase with some Symfony2 tools.
 */
abstract class TestCase extends PhpUnitTestCase
{
    protected \Symfony\Component\HttpKernel\Kernel $kernel;
    protected EntityManager $entityManager;
    protected ContainerInterface|Container $container;

    /**
     * Initialize kernel app and some Symfony2 services.
     *
     * @see \PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp(): void
    {
        // Boot the AppKernel in the test environment and with the debug.
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();

        // Store the container and the entity manager in test case properties
        $this->container = $this->kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine')->getManager();

        $this->entityManager->getConnection()->beginTransaction();

        parent::setUp();
    }

    /**
     * @see \PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollback();

        // Shutdown the kernel.
        $this->kernel->shutdown();

        parent::tearDown();
    }
}
