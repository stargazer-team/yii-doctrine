<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Migrations\Command;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function sprintf;
use function uasort;

/**
 * The UpToDateCommand class outputs if your database is up to date or if there are new migrations
 * that need to be executed.
 */
final class UpToDateCommand extends BaseMigrationCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:migrations:up-to-date')
            ->setAliases(['up-to-date'])
            ->setDescription('Tells you if your schema is up-to-date.')
            ->addOption(
                'fail-on-unregistered',
                'u',
                InputOption::VALUE_NONE,
                'Whether to fail when there are unregistered extra migrations found',
            )
            ->addOption(
                'list-migrations',
                'l',
                InputOption::VALUE_NONE,
                'Show a list of missing or not migrated versions.',
            )
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command tells you if your schema is up-to-date:

    <info>%command.full_name%</info>
EOT
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $statusCalculator = $this
            ->getDependencyFactory()
            ->getMigrationStatusCalculator();

        $executedUnavailableMigrations = $statusCalculator->getExecutedUnavailableMigrations();
        $newMigrations = $statusCalculator->getNewMigrations();
        $newMigrationsCount = count($newMigrations);
        $executedUnavailableMigrationsCount = count($executedUnavailableMigrations);

        if ($newMigrationsCount === 0 && $executedUnavailableMigrationsCount === 0) {
            $this->io->success('Up-to-date! No migrations to execute.');

            return ExitCode::OK;
        }

        $exitCode = ExitCode::OK;

        if ($newMigrationsCount > 0) {
            $this->io->error(
                sprintf(
                    'Out-of-date! %u migration%s available to execute.',
                    $newMigrationsCount,
                    $newMigrationsCount > 1 ? 's are' : ' is',
                ),
            );
            $exitCode = ExitCode::UNSPECIFIED_ERROR;
        }

        if ($executedUnavailableMigrationsCount > 0) {
            $this->io->error(
                sprintf(
                    'You have %1$u previously executed migration%3$s in the database that %2$s registered migration%3$s.',
                    $executedUnavailableMigrationsCount,
                    $executedUnavailableMigrationsCount > 1 ? 'are not' : 'is not a',
                    $executedUnavailableMigrationsCount > 1 ? 's' : '',
                ),
            );

            if ($input->getOption('fail-on-unregistered')) {
                $exitCode = 2;
            }
        }

        if ($input->getOption('list-migrations')) {
            $versions = $this->getSortedVersions($newMigrations, $executedUnavailableMigrations);

            $this
                ->getDependencyFactory()
                ->getMigrationStatusInfosHelper()
                ->listVersions($versions, $output);

            $this->io->newLine();
        }

        return $exitCode;
    }

    /**
     * @return Version[]
     */
    private function getSortedVersions(
        AvailableMigrationsList $newMigrations,
        ExecutedMigrationsList $executedUnavailableMigrations,
    ): array {
        $executedUnavailableVersion = array_map(
            static fn(ExecutedMigration $executedMigration): Version => $executedMigration->getVersion(),
            $executedUnavailableMigrations->getItems(),
        );

        $newVersions = array_map(
            static fn(AvailableMigration $availableMigration): Version => $availableMigration->getVersion(),
            $newMigrations->getItems(),
        );

        $versions = array_unique(array_merge($executedUnavailableVersion, $newVersions));

        $comparator = $this
            ->getDependencyFactory()
            ->getVersionComparator();

        uasort($versions, $comparator->compare(...));

        return $versions;
    }
}
