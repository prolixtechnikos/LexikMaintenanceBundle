<?php

namespace Lexik\Bundle\MaintenanceBundle\Drivers;

/**
 * Class to handle a memcache driver
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class MemCacheDriver extends AbstractDriver implements DriverTtlInterface
{
    /**
     * Value store in memcache
     *
     * @var string
     */
    const VALUE_TO_STORE = "maintenance";

    /**
     * The key store in memcache
     *
     * @var string keyName
     */
    protected $keyName;

    /**
     * MemCache instance
     *
     * @var \Memcache
     */
    protected $memcacheInstance;

    /**
     * Constructor memCacheDriver
     *
     * @param array $options    Options driver
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if ( ! isset($options['key_name'])) {
            throw new \InvalidArgumentException('$options[\'key_name\'] must be defined if Driver Memcache configuration is used');
        }

        if ( ! isset($options['host'])) {
            throw new \InvalidArgumentException('$options[\'host\'] must be defined if Driver Memcache configuration is used');
        }

        if (! isset($options['port'])) {
            throw new \InvalidArgumentException('$options[\'port\'] must be defined if Driver Memcache configuration is used');
        }

        if (! is_int($options['port'])) {
            throw new \InvalidArgumentException('$options[\'port\'] must be an integer if Driver Memcache configuration is used');
        }

        if (null !== $options) {
            $this->keyName = $options['key_name'];
            $this->memcacheInstance = new \Memcache;
            $this->memcacheInstance->connect($options['host'], $options['port']);
        }

        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function createLock()
    {
        return $this->memcacheInstance->set($this->keyName, self::VALUE_TO_STORE, false, ($this->options['ttl'] ?? 0));
    }

    /**
     * {@inheritdoc}
     */
    protected function createUnlock(): bool
    {
        return $this->memcacheInstance->delete($this->keyName);
    }

    /**
     * {@inheritdoc}
     */
    public function isExists(): bool
    {
        if (false !== $this->memcacheInstance->get($this->keyName)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageLock(bool $resultTest): string
    {
        $key = $resultTest ? 'lexik_maintenance.success_lock_memc' : 'lexik_maintenance.not_success_lock';

        return $this->translator->trans($key, array(), 'maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageUnlock(bool $resultTest): string
    {
        $key = $resultTest ? 'lexik_maintenance.success_unlock' : 'lexik_maintenance.not_success_unlock';

        return $this->translator->trans($key, array(), 'maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function setTtl(int $value): void
    {
        $this->options['ttl'] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl(): int
    {
        return $this->options['ttl'];
    }

    /**
     * {@inheritdoc}
     */
    public function hasTtl(): bool
    {
        return isset($this->options['ttl']);
    }
}
