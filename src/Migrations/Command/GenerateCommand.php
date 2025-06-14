<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Migrations\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

use function sprintf;

/**
 * The GenerateCommand class is responsible for generating a blank migration class for you to modify to your needs.
 */
final class GenerateCommand extends BaseMigrationCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:migrations:generate')
            ->setAliases(['generate'])
            ->setDescription('Generate a blank migration class.')
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'The namespace to use for the migration (must be in the list of configured namespaces)',
            )
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command generates a blank migration class:

    <info>%command.full_name%</info>

EOT
            );

        parent::configure();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrationGenerator = $this
            ->getDependencyFactory()
            ->getMigrationGenerator();

        $namespace = $this->getNamespace($input, $output);

        $fqcn = $this
            ->getDependencyFactory()
            ->getClassNameGenerator()
            ->generateClassName($namespace);

        $path = $migrationGenerator->generateMigration($fqcn);

        $this->io->text(
            [
                sprintf('Generated new migration class to "<info>%s</info>"', $path),
                '',
                sprintf(
                    'To run just this migration for testing purposes, you can use <info>doctrine:migrations:execute --up \'%s\'</info>',
                    $fqcn
                ),
                '',
                sprintf(
                    'To revert the migration you can use <info>doctrine:migrations:execute --down \'%s\'</info>',
                    $fqcn
                ),
                '',
            ],
        );

        return ExitCode::OK;
    }
}
