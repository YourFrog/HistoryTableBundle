<?php


namespace HistoryTableBundle\Platform;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  Obsługa tabel historycznych dla bazy danych postgresql
 *
 * @package HistoryTableBundle\Platform
 */
class Postgresql implements ExecuterInterface
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
        return $platform instanceof PostgreSqlPlatform;
    }

    /**
     * @inheritDoc
     */
    public function createSchema(): void
    {
        $query = 'CREATE SCHEMA IF NOT EXISTS history;';
        $this->entityManager->getConnection()->exec($query);
    }

    /**
     * @inheritDoc
     */
    public function createHistoryTable(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);

        $createTableQuery = 'CREATE TABLE IF NOT EXISTS ' . $historyName . ' (history_create_at timestamp with time zone, history_operation TEXT, history_db_user TEXT, LIKE ' . $name . ')';

        $this->entityManager->getConnection()->exec($createTableQuery);
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
        return 'history.' . str_replace('.', '_', $this->getTableName($class));
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
    public function getTableName(string $class): string
    {
        $metadata = $this->entityManager->getClassMetadata($class);
        $schema = $metadata->getSchemaName() ?? 'public';

        return $schema . '.' . $metadata->getTableName();
    }

    /**
     * @inheritDoc
     */
    public function createAfterInsertTrigger(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);
        $primaryKey = $this->getPrimaryKey($class);

        $this->createTriggerFunction('insert', 'NEW');
        $this->dropTrigger($name, 'insert');
        $this->bindTrigger($name, 'insert', 'after');
    }

    /**
     * @inheritDoc
     */
    public function createBeforeDeleteTrigger(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);
        $primaryKey = $this->getPrimaryKey($class);


        $this->createTriggerFunction('delete', 'OLD');
        $this->dropTrigger($name, 'delete');
        $this->bindTrigger($name, 'delete', 'before');
    }

    /**
     * @inheritDoc
     */
    public function createAfterUpdateTrigger(string $class)
    {
        $name = $this->getTableName($class);
        $historyName = $this->getHistoryTableName($class);
        $primaryKey = $this->getPrimaryKey($class);


        $this->createTriggerFunction('update', 'OLD');
        $this->dropTrigger($name, 'update');
        $this->bindTrigger($name, 'update', 'after');
    }

    private function createTriggerFunction(string $operation, $recordType)
    {
        $triggerQuery = "
    CREATE OR REPLACE FUNCTION history.trigger_" . $operation . "() RETURNS trigger AS 
    \$BODY\$
    BEGIN
        EXECUTE 'INSERT INTO history.' || TG_TABLE_SCHEMA || '_' || TG_TABLE_NAME || ' SELECT $1, $2, $3, $4.*' USING NOW(), TG_OP, current_user, " . $recordType . ";
        
        RETURN " . $recordType . ";
    END;
    \$BODY\$
    LANGUAGE plpgsql;
";

        $this->entityManager->getConnection()->exec($triggerQuery);
    }

    private function dropTrigger($tableName, string $operation)
    {
        $query = 'DROP TRIGGER IF EXISTS history_' . $operation . ' on ' . $tableName . ';';
        $this->entityManager->getConnection()->exec($query);
    }

    private function bindTrigger($tableName, string $operation, string $when = '')
    {
        $query = '
CREATE TRIGGER history_' . $operation . '
' . $when . ' ' . $operation . ' ON ' . $tableName . '
FOR EACH ROW EXECUTE PROCEDURE history.trigger_' . $operation . '();
        ';

        $this->entityManager->getConnection()->exec($query);
    }
}