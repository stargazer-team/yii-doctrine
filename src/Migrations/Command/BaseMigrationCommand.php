<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Migrations\Command;

use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Exception\DependenciesNotSatisfied;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Migrations\MigrationConfigurationRegistry;

use function array_keys;
use function assert;
use function count;
use function is_string;
use function key;
use function sprintf;

class BaseMigrationCommand extends Command
{
    protected ?DependencyFactory $dependencyFactory = null;

    protected ?SymfonyStyle $io = null;

    public function __construct(
        private readonly DoctrineManager $doctrineManager,
        private readonly MigrationConfigurationRegistry $migrationConfigurationRegistry,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct();
    }

    protected function canExecute(string $question, InputInterface $input): bool
    {
        return !$input->isInteractive() || $this->io->confirm($question);
    }

    protected function configure(): void
    {
        $this->addOption(
            'configuration',
            null,
            InputOption::VALUE_OPTIONAL,
            'The name to a migrations configuration on config.',
        );
    }

    protected function getDependencyFactory(): DependencyFactory
    {
        if ($this->dependencyFactory === null) {
            throw DependenciesNotSatisfied::new();
        }

        return $this->dependencyFactory;
    }

    /**
     * @throws Exception
     */
    final protected function getNamespace(InputInterface $input, OutputInterface $output): string
    {
        $configuration = $this->getDependencyFactory()->getConfiguration();

        $namespace = $input->getOption('namespace');

        if ($namespace === '') {
            $namespace = null;
        }

        $dirs = $configuration->getMigrationDirectories();

        if ($namespace === null && count($dirs) === 1) {
            $namespace = key($dirs);
        } elseif ($namespace === null && count($dirs) > 1) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please choose a namespace (defaults to the first one)',
                array_keys($dirs),
                0,
            );
            $namespace = $helper->ask($input, $output, $question);
            $this->io->text(sprintf('You have selected the "%s" namespace', $namespace));
        }

        if (!isset($dirs[$namespace])) {
            throw new Exception(sprintf('Path not defined for the namespace "%s"', $namespace));
        }

        assert(is_string($namespace));

        return $namespace;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        /** @var string $configurationName */
        $configurationName = $input->getOption(
            'configuration'
        ) ?? MigrationConfigurationRegistry::DEFAULT_CONFIGURATION;

        $configuration = $this->migrationConfigurationRegistry->getConfiguration($configurationName);

        $configurationLoader = new ExistingConfiguration($configuration);

        if (null !== $configuration->getConnectionName() && null === $configuration->getEntityManagerName()) {
            $connectionLoader = ConnectionRegistryConnection::withSimpleDefault($this->doctrineManager);

            $this->dependencyFactory = DependencyFactory::fromConnection(
                $configurationLoader,
                $connectionLoader,
                $this->logger
            );
        } elseif (null === $configuration->getConnectionName() && null !== $configuration->getEntityManagerName()) {
            $entityManagerLoader = ManagerRegistryEntityManager::withSimpleDefault($this->doctrineManager);

            $this->dependencyFactory = DependencyFactory::fromEntityManager(
                $configurationLoader,
                $entityManagerLoader,
                $this->logger
            );
        } else {
            throw new RuntimeException('Error config');
        }

        if ($this->dependencyFactory->isFrozen()) {
            return;
        }

        $this->dependencyFactory->freeze();
    }
}
