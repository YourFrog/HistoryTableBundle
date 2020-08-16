<?php


namespace HistoryTableBundle\Platform;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  Obsługa tabel historycznych dla platformy MySQL
 *
 * @package HistoryTableBundle\Platform
 */
class MySQL implements ExecuterInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     *  Konstruktor
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function isSupported(AbstractPlatform $platform)
    {
        return $platform instanceof MySqlPlatform;
    }


    /**
     * @inheritDoc
     */
    public function createHistoryTable(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);

        $createTableQuery = 'CREATE TABLE IF NOT EXISTS ' . $historyName . ' (create_at TEXT, operation TEXT, db_user TEXT) SELECT * FROM ' . $name . ' LIMIT 0;';

        $this->entityManager->getConnection()->exec($createTableQuery);
    }

    /**
     * @inheritDoc
     */
    public function createTriggers(string $class)
    {
        $this->createAfterInsertTrigger($class);
        $this->createAfterUpdateTrigger($class);
        $this->createBeforeDeleteTrigger($class);
    }

    /**
     *  Tworzy historyczną nazwe tabeli dla podanej encji
     *
     * @param string $class
     *
     * @return string
     */
    private function getHistoryTableName(string $class): string
    {
        return 'history_' . $this->getTableName($class);
    }

    /**
     *  Pobranie nazwy klucza głównego
     *
     * @param string $class
     *
     * @return string
     */
    private function getPrimaryKey(string $class): string
    {
        $columns = $this->entityManager->getClassMetadata($class)->getIdentifierColumnNames();

        if( count($columns) > 1 ) {
            throw new \Exception('This bundle supported only one column PK');
        }

        return $columns[0];
    }

    /**
     *  Utworzenie nazwy tabeli
     *
     * @param string $class
     *
     * @return string
     */
    function getTableName(string $class): string
    {
        return $this->entityManager->getClassMetadata($class)->getTableName();
    }

    private function createAfterInsertTrigger(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);
        $primaryKey = $this->getPrimaryKey($class);

        $afterInsert = "
CREATE
TRIGGER IF NOT EXISTS `history_trigger_after_insert_" . $name . "`
AFTER INSERT ON `" . $name . "`
FOR EACH ROW INSERT INTO " . $historyName . " SELECT NOW() AS create_at, 'AFTER-INSERT' AS operation, USER() AS db_user, " . $name . ".* FROM " . $name . " WHERE " . $primaryKey . " = NEW." . $primaryKey . ";
";

        $this->entityManager->getConnection()->exec($afterInsert);
    }

    private function createBeforeDeleteTrigger(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);
        $primaryKey = $this->getPrimaryKey($class);

        $beforeDelete = "
CREATE
TRIGGER IF NOT EXISTS `history_trigger_before_delete_" . $name . "`
BEFORE DELETE ON `" . $name . "`
FOR EACH ROW INSERT INTO " . $historyName . " SELECT NOW() AS create_at, 'BEFORE-DELETE' AS operation, USER() AS db_user, " . $name . ".* FROM " . $name . " WHERE " . $primaryKey . " = OLD." . $primaryKey . ";
";

        $this->entityManager->getConnection()->exec($beforeDelete);
    }

    private function createAfterUpdateTrigger(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);
        $primaryKey = $this->getPrimaryKey($class);

        $afterUpdate = "
CREATE
TRIGGER IF NOT EXISTS `history_trigger_after_update_" . $name . "`
AFTER UPDATE ON `" . $name . "`
FOR EACH ROW INSERT INTO " . $historyName . " SELECT NOW() AS create_at, 'AFTER-UPDATE' AS operation, USER() AS db_user, " . $name . ".* FROM " . $name . " WHERE " . $primaryKey . " = NEW." . $primaryKey . ";
";

        $this->entityManager->getConnection()->exec($afterUpdate);
    }

}