<?php

namespace App\Command;

use App\Repository\ShoppingListRepository;
use App\Service\Shopping\ShoppingListBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'project:shopping-list:reclassify',
    description: 'Reclasse les produits des listes actives sans écraser les corrections manuelles.',
)]
final class ReclassifyShoppingListsCommand extends Command
{
    public function __construct(
        private readonly ShoppingListRepository $shoppingListRepository,
        private readonly ShoppingListBuilder $shoppingListBuilder,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeShoppingLists = $this->shoppingListRepository->findBy(['active' => true]);
        foreach ($activeShoppingLists as $shoppingList) {
            $this->shoppingListBuilder->reclassifyGeneratedItems($shoppingList);
        }

        $this->entityManager->flush();
        (new SymfonyStyle($input, $output))->success(sprintf(
            '%d liste(s) de courses active(s) reclassée(s).',
            count($activeShoppingLists),
        ));

        return Command::SUCCESS;
    }
}
