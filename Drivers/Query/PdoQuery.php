<?php

namespace Lexik\Bundle\MaintenanceBundle\Drivers\Query;

use Doctrine\ORM\EntityManager;

/**
 * Abstract class to handle PDO connection
 *
 * @package LexikMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
abstract class PdoQuery
{
    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor PdoDriver
     *
     * @param array $options Options driver
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Execute create query
     */
    abstract public function createTableQuery(): void;

    /**
     * Result of delete query
     *
     * @param \PDO $db PDO instance
     */
    abstract public function deleteQuery(\PDO $db): bool;

    /**
     * Result of select query
     *
     * @param \PDO $db PDO instance
     */
    abstract public function selectQuery(\PDO $db): array;

    /**
     * Result of insert query
     *
     * @param integer $ttl ttl value
     * @param \PDO $db  PDO instance
     */
    abstract public function insertQuery(int $ttl, \PDO $db): bool;

    /**
     * Initialize pdo connection
     *
     * @return \PDO
     */
    abstract function initDb(): \PDO;

    /**
     * Execute sql
     *
     * @param \PDO $db    PDO instance
     * @param string $query Query
     * @param array  $args  Arguments
     *
     * @return boolean
     *
     * @throws \RuntimeException
     */
    protected function exec(\PDO $db, string $query, array $args = []): bool
    {
        $stmt = $this->prepareStatement($db, $query);

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }

        $success = $stmt->execute();

        if (!$success) {
            throw new \RuntimeException(sprintf('Error executing query "%s"', $query));
        }

        return $success;
    }

    /**
     * PrepareStatement
     *
     * @param \PDO $db    PDO instance
     * @param string $query Query
     *
     * @return Statement
     *
     * @throws \RuntimeException
     */
    protected function prepareStatement(\PDO $db, string $query): Statement
    {
        try {
            $stmt = $db->prepare($query);
        } catch (\Exception $e) {
            $stmt = false;
        }

        if (false === $stmt) {
            throw new \RuntimeException('The database cannot successfully prepare the statement');
        }

        return $stmt;
    }

    /**
     * Fetch All
     *
     * @param \PDO $db    PDO instance
     * @param string $query Query
     * @param array  $args  Arguments
     *
     * @return array
     */
    protected function fetch(\PDO $db, string $query, array $args = []): array
    {
        $stmt = $this->prepareStatement($db, $query);

        if (false === $stmt) {
            throw new \RuntimeException('The database cannot successfully prepare the statement');
        }

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }

        $result = $stmt->execute();
        $return = $result->fetchAll(\PDO::FETCH_ASSOC);

        return $return;
    }
}

