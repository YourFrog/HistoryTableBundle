<?php

namespace HistoryTableBundle\Command;

use Doctrine\DBAL\Platforms;
use Exception;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use HistoryTableBundle\Annotation;
use Doctrine\ORM\Mapping as ORM;
use HistoryTableBundle\Platform\ExecuterInterface;
use ReflectionClass;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  Komenda tworząca tabele historyczne
 *
 * @package AppBundle\Command
 */
class HistoryTriggersCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:triggers';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ExecuterInterface[]
     */
    private $executers;

    /**
     *  Konstruktor
     *
     * @param EntityManagerInterface $em
     * @param ExecuterInterface[] $executers
     */
    public function __construct(EntityManagerInterface $em, array $executers)
    {
        parent::__construct();

        $this->em = $em;
        $this->executers = $executers;
    }


    protected function configure()
    {
        $this->setDescription('Create history triggers');
    }

    /**
     *  Pobiera klasę która może podjąć się zadania utworzenia tabel historycznych
     *
     * @return ExecuterInterface
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getExecuter(): ExecuterInterface
    {
        $platform = $this->em->getConnection()->getDatabasePlatform();

        foreach($this->executers as $executer) {
            if( $executer->isSupported($platform) ) {
                return $executer;
            }
        }

        throw new Exception('Platform "' . get_class($platform) . '" not supported by this bundle');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $classes = $this->getClasses();
        $executer = $this->getExecuter();

        foreach($classes as $class) {
            $output->writeln('Table: ' . $executer->getTablename($class), OutputInterface::VERBOSITY_VERBOSE);

            $reflection = new ReflectionClass($class);

            $reader = new AnnotationReader();

            $annotationOfEntity = $reader->getClassAnnotation($reflection, ORM\Entity::class);
            if( $annotationOfEntity == null ) {
                $output->writeln("\t- It's not entity", OutputInterface::VERBOSITY_VERBOSE);
                continue;
            }

            if( $reader->getClassAnnotation($reflection, Annotation\DisableHistoryTable::class) !== null ) {
                // This entity has disable history table
                $output->writeln("\t- History table is disabled", OutputInterface::VERBOSITY_VERBOSE);
                return;
            }

            $output->writeln("\t- Create history table", OutputInterface::VERBOSITY_VERBOSE);
            $executer->createHistoryTable($class);

            $output->writeln("\t- Create history triggers", OutputInterface::VERBOSITY_VERBOSE);
            $executer->createTriggers($class);
        }

        return 0;
    }

    /**
     *  Pobranie wszystkich klas
     *
     * @return string[]
     */
    private function getClasses(): array
    {
        $data = $this->em->getMetadataFactory()->getAllMetadata();

        $classes = [];
        foreach($data as $item) {
            $classes[] = $item->getName();
        }

        return $classes;
    }
}