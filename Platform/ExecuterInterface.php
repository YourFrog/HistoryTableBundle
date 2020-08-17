<?php


namespace HistoryTableBundle\Platform;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 *  Interfejs opisujący możliwości wytworzenia tabel historycznych
 *
 * @package HistoryTableBundle\Platform
 */
interface ExecuterInterface
{
    /**
     *  Sprawdzenie czy klasa wspiera podaną platformę
     *
     * @param AbstractPlatform $platform
     *
     * @return bool
     */
    public function isSupported(AbstractPlatform $platform);

    /**
     *  Utworzenie nazwy tabeli
     *
     * @param string $class
     *
     * @return string
     */
    function getTableName(string $class): string;

    /**
     *  Utworzenie tabeli historycznej
     *
     * @param string $class
     */
    public function createHistoryTable(string $class);

    /**
     *  Utworzenie triggerów
     *
     * @param string $class
     * @param bool $createInsertTrigger
     */
    public function createTriggers(string $class, bool $createInsertTrigger);
}