<?php

namespace App\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'project:notifications:generate-keys', description: 'Génère une paire de clés VAPID pour les notifications Web Push.')]
final class GenerateWebPushKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $keys = VAPID::createVapidKeys();
        $io->warning('Conserve la clé privée uniquement dans le fichier .env du serveur. Ne la publie jamais dans Git.');
        $io->writeln('WEB_PUSH_VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $io->writeln('WEB_PUSH_VAPID_PRIVATE_KEY='.$keys['privateKey']);

        return Command::SUCCESS;
    }
}
