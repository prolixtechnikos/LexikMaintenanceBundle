<?php

namespace Lexik\Bundle\MaintenanceBundle\Drivers;

/**
 * Interface DriverTtlInterface
 *
 * @package Lexik\Bundle\MaintenanceBundle\Drivers
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
interface DriverTtlInterface
{
    /**
     * Set time to life for overwrite basic configuration
     */
    public function setTtl(int $value);

    /**
     * Return time to life
     */
    public function getTtl(): int;

    public function hasTtl(): bool;
}
