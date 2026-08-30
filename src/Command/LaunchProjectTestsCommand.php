<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'project:test:launch',
    description: 'Lance tous les tests du projet et génère un rapport HTML.',
)]
final class LaunchProjectTestsCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $projectDirectory = $this->kernel->getProjectDir();
        $reportDirectory = $projectDirectory . '/var/test-reports';
        $htmlReportPath = $reportDirectory . '/test-report.html';

        if (!is_dir($reportDirectory) && !mkdir($reportDirectory, 0775, true) && !is_dir($reportDirectory)) {
            $console->error(sprintf('Impossible de créer le dossier de rapports : %s', $reportDirectory));

            return Command::FAILURE;
        }

        $console->title('Tests automatisés du projet');
        $console->text('Exécution de la suite PHPUnit complète…');

        $testProcess = new Process([
            PHP_BINARY,
            $projectDirectory . '/bin/phpunit',
            '--testdox',
            '--testdox-html',
            $htmlReportPath,
            '--colors=always',
        ], $projectDirectory, [
            'APP_ENV' => 'test',
            'KERNEL_CLASS' => 'App\\Kernel',
        ]);
        $testProcess->setTimeout(null);
        $testProcess->run(static function (string $outputType, string $outputChunk) use ($output): void {
            $output->write($outputChunk);
        });

        if (!$testProcess->isSuccessful()) {
            $console->error('La suite de tests a échoué. Le rapport HTML contient le détail des erreurs.');
            $console->writeln(sprintf('Rapport : <href=file:///%s>%s</>', str_replace('\\', '/', $htmlReportPath), $htmlReportPath));

            return Command::FAILURE;
        }

        $console->success('Tous les tests sont passés.');
        $console->writeln(sprintf('Rapport HTML : <href=file:///%s>%s</>', str_replace('\\', '/', $htmlReportPath), $htmlReportPath));

        return Command::SUCCESS;
    }
}
