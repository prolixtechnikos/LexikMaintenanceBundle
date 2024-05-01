<?php

namespace Lexik\Bundle\MaintenanceBundle\Drivers\Query;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;

/**
 * Default Class for handle database with a doctrine connection
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class DefaultQuery extends PdoQuery
{
    /**
     * @var EntityManager
     */
    protected $em;

    public const NAME_TABLE   = 'lexik_maintenance';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function initDb()
    {
        if (null === $this->db) {
            $db = $this->em->getConnection();
            $this->db = $db;
            $this->createTableQuery();
        }

        return $this->db;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function createTableQuery(): void
    {
        $type = $this->em->getConnection()->getDatabasePlatform()->getName() != 'mysql' ? 'timestamp' : 'datetime';

        $this->db->exec(
            sprintf('CREATE TABLE IF NOT EXISTS %s (ttl %s DEFAULT NULL)', self::NAME_TABLE, $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQuery(\PDO $db): bool
    {
        return $this->exec($db, sprintf('DELETE FROM %s', self::NAME_TABLE));
    }

    /**
     * {@inheritdoc}
     */
    public function selectQuery(\PDO $db): array
    {
        return $this->fetch($db, sprintf('SELECT ttl FROM %s', self::NAME_TABLE));
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery(int $ttl, \PDO $db): bool
    {
        return $this->exec(
            $db, sprintf('INSERT INTO %s (ttl) VALUES (:ttl)',
            self::NAME_TABLE),
            array(':ttl' => $ttl)
        );
    }
}
