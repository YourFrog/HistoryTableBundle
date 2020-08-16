<?php


namespace HistoryTableBundle\Annotation;

use Doctrine\ORM\Mapping\Annotation;

/**
 *  Adnotacja odnotowująca informacje o tym że encja powinna mieć tabele historyczną
 *
 * @Annotation
 * @Target("CLASS")
 */
class DisableHistoryTable implements Annotation
{

}