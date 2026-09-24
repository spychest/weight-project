<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'project:user:grant-admin',
    description: 'Attribue le rôle administrateur à un compte existant.',
)]
final class GrantAdministratorRoleCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail du compte à promouvoir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $user = $this->userRepository->findOneByEmail($email);

        if ($user === null) {
            $console->error(sprintf('Aucun compte trouvé pour « %s ».', $email));

            return Command::FAILURE;
        }

        $user->setRoles([...$user->getRoles(), 'ROLE_ADMIN']);
        $this->entityManager->flush();
        $console->success(sprintf('Le compte « %s » est maintenant administrateur.', $user->getEmail()));

        return Command::SUCCESS;
    }
}
