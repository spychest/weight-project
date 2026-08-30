<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'project:user:adopt-existing-profile', description: 'Crée un compte local et lui rattache l’unique profil historique.')]
final class AdoptExistingProfileCommand extends Command
{
    public function __construct(private readonly ProfileRepository $profileRepository, private readonly UserRepository $userRepository, private readonly UserPasswordHasherInterface $passwordHasher, private readonly ValidatorInterface $validator, private readonly EntityManagerInterface $entityManager) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse e-mail du compte')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe (déconseillé dans l’historique du terminal)');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orphanProfiles = $this->profileRepository->findProfilesWithoutUser();
        if (count($orphanProfiles) !== 1) { $io->error(sprintf('La commande exige exactement un profil sans propriétaire ; %d trouvé(s).', count($orphanProfiles))); return Command::FAILURE; }
        $email = (string) ($input->getOption('email') ?: $io->ask('Adresse e-mail'));
        $password = (string) ($input->getOption('password') ?: $io->askHidden('Mot de passe (12 caractères minimum)'));
        if ($this->userRepository->findOneByEmail($email) !== null) { $io->error('Cette adresse e-mail possède déjà un compte.'); return Command::FAILURE; }
        $violations = $this->validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($violations) > 0 || mb_strlen($password) < 12) { $io->error('Adresse e-mail invalide ou mot de passe inférieur à 12 caractères.'); return Command::INVALID; }
        $user = (new User())->setEmail($email)->setEmailVerified(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $orphanProfiles[0]->setUser($user);
        $this->entityManager->wrapInTransaction(function () use ($user): void { $this->entityManager->persist($user); $this->entityManager->flush(); });
        $io->success('Le compte a été créé et toutes les données du profil historique lui appartiennent désormais.');
        return Command::SUCCESS;
    }
}
