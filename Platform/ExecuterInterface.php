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
    function isSupported(AbstractPlatform $platform);

    /**
     *  Utworzenie nazwy tabeli
     *
     * @param string $class
     *
     * @return string
     */
    function getTableName(string $class): string;

    /**
     *  Utworzenie schematu jeśli dana baza go obsługuje
     *
     * @return void
     */
    function createSchema(): void;

    /**
     *  Utworzenie tabeli historycznej
     *
     * @param string $class
     */
    function createHistoryTable(string $class);

    /**
     *  Dodanie trigger'a na dodawanie danych
     *
     * @param string $class
     */
    function createAfterInsertTrigger(string $class);

    /**
     *  Dodanie trigger'a na aktualizacje danych
     *
     * @param string $class
     */
    function createAfterUpdateTrigger(string $class);

    /**
     *  Dodanie trigger'a na usuwanie danych
     *
     * @param string $class
     */
    function createBeforeDeleteTrigger(string $class);
}