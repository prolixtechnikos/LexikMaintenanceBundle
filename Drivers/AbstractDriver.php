<?php

namespace Lexik\Bundle\MaintenanceBundle\Drivers;

use Symfony\Component\Translation\Translator;

/**
 * Abstract class for drivers
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
abstract class AbstractDriver
{
    protected array $options;
    protected Translator $translator;

    /**
     * Constructor
     *
     * @param array $options Array of options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    abstract public function isExists(): bool;

    /** @return bool|resource */
    abstract protected function createLock();

    abstract protected function createUnlock(): bool;

    /**
     * The feedback message
     */
    abstract public function getMessageLock(bool $resultTest): string;

    /**
     * The feedback message
     */
    abstract public function getMessageUnlock(bool $resultTest): string;

    public function lock(): bool
    {
        if (!$this->isExists()) {
            return (bool) $this->createLock();
        }

        return false;
    }

    public function unlock(): bool
    {
        if ($this->isExists()) {
            return $this->createUnlock();
        }

        return false;
    }

    /**
     * the choice of the driver to less pass or not the user
     */
    public function decide(): bool
    {
        return ($this->isExists());
    }

    /**
     * Options of driver
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }
}
