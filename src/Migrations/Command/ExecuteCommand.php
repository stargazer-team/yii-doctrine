<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Migrations\Command;

use Doctrine\DBAL\Exception;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use function array_map;
use function dirname;
use function getcwd;
use function implode;
use function is_dir;
use function is_string;
use function is_writable;
use function sprintf;

/**
 * The ExecuteCommand class is responsible for executing migration versions up or down manually.
 */
final class ExecuteCommand extends BaseMigrationCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:migrations:execute')
            ->setAliases(['execute'])
            ->setDescription(
                'Execute one or more migration versions up or down manually.'
            )
            ->addArgument(
                'versions',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The versions to execute.',
                null,
            )
            ->addOption(
                'write-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to output the migration SQL file. Defaults to current working directory.',
                false,
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration as a dry run.',
            )
            ->addOption(
                'up',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration up.',
            )
            ->addOption(
                'down',
                null,
                InputOption::VALUE_NONE,
                'Execute the migration down.',
            )
            ->addOption(
                'query-time',
                null,
                InputOption::VALUE_NONE,
                'Time all the queries individually.',
            )
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command executes migration versions up or down manually:

    <info>%command.full_name% FQCN</info>

You can show more information about the process by increasing the verbosity level. To see the
executed queries, set the level to debug with <comment>-vv</comment>:

    <info>%command.full_name% FQCN -vv</info>

If no <comment>--up</comment> or <comment>--down</comment> option is specified it defaults to up:

    <info>%command.full_name% FQCN --down</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% FQCN --dry-run</info>

You can output the prepared SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% FQCN --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% FQCN --no-interaction</info>

All the previous commands accept multiple migration versions, allowing you run execute more than
one migration at once:

    <info>%command.full_name% FQCN-1 FQCN-2 ...FQCN-n </info>

EOT
            );

        parent::configure();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migratorConfigurationFactory = $this
            ->getDependencyFactory()
            ->getConsoleInputMigratorConfigurationFactory();

        $migratorConfiguration = $migratorConfigurationFactory->getMigratorConfiguration($input);

        $databaseName = (string)$this
            ->getDependencyFactory()
            ->getConnection()
            ->getDatabase();

        $question = sprintf(
            'WARNING! You are about to execute a migration in database "%s" that could result in schema changes and data loss. Are you sure you wish to continue?',
            $databaseName === '' ? '<unnamed>' : $databaseName,
        );

        if (!$migratorConfiguration->isDryRun() && !$this->canExecute($question, $input)) {
            $this->io->error('Migration cancelled!');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this
            ->getDependencyFactory()
            ->getMetadataStorage()
            ->ensureInitialized();

        /** @psalm-var array<array-key, string>|array<empty> $versions */
        $versions = $input->getArgument('versions');

        $direction = $input->getOption('down') !== false
            ? Direction::DOWN
            : Direction::UP;

        /** @var string|bool $path */
        $path = $input->getOption('write-sql') ?? getcwd();

        if (is_string($path) && !$this->isPathWritable($path)) {
            $this->io->error(sprintf('The path "%s" not writeable!', $path));

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $planCalculator = $this
            ->getDependencyFactory()
            ->getMigrationPlanCalculator();

        $plan = $planCalculator->getPlanForVersions(
            array_map(
                static fn(string $version): Version => new Version($version),
                $versions,
            ),
            $direction,
        );

        $this
            ->getDependencyFactory()
            ->getLogger()
            ->notice(
                'Executing' . ($migratorConfiguration->isDryRun() ? ' (dry-run)' : '') . ' {versions} {direction}',
                [
                    'direction' => $plan->getDirection(),
                    'versions' => implode(', ', $versions),
                ],
            );

        $migrator = $this
            ->getDependencyFactory()
            ->getMigrator();

        $sql = $migrator->migrate($plan, $migratorConfiguration);

        if (is_string($path)) {
            $writer = $this
                ->getDependencyFactory()
                ->getQueryWriter();

            $writer->write($path, $direction, $sql);
        }

        $this->io->success(
            sprintf(
                'Successfully migrated version(s): %s: [%s]',
                implode(', ', $versions),
                strtoupper($plan->getDirection()),
            ),
        );

        $this->io->newLine();

        return ExitCode::OK;
    }

    private function isPathWritable(string $path): bool
    {
        return is_writable($path) || is_dir($path) || is_writable(dirname($path));
    }
}
