<?php

namespace App\Command;

use App\Entity\IngredientCatalogItem;
use App\Repository\IngredientCatalogItemRepository;
use App\Service\Shopping\IngredientClassifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'project:ingredient-catalog:import',
    description: 'Importe les ingrédients manquants du catalogue JSON sans écraser les corrections en base.',
)]
final class ImportIngredientCatalogCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/ingredient_catalog.json')]
        private readonly string $catalogFilePath,
        private readonly IngredientCatalogItemRepository $ingredientCatalogItemRepository,
        private readonly IngredientClassifier $ingredientClassifier,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $catalog = $this->readCatalog();
        $importedIngredientCount = 0;

        foreach ($catalog['ingredients'] as $ingredientData) {
            $normalizedName = $this->ingredientClassifier->normalizeName($ingredientData['canonicalName']);
            $existingIngredient = $this->ingredientCatalogItemRepository->findOneBy([
                'normalizedName' => $normalizedName,
            ]);

            if ($existingIngredient !== null) {
                continue;
            }

            $catalogItem = (new IngredientCatalogItem())
                ->setCanonicalName($ingredientData['canonicalName'])
                ->setNormalizedName($normalizedName)
                ->setCategory($ingredientData['category'])
                ->setAliases($ingredientData['aliases']);
            $this->entityManager->persist($catalogItem);
            ++$importedIngredientCount;
        }

        $this->entityManager->flush();
        $importedIngredientLabel = $importedIngredientCount === 1
            ? 'nouvel ingrédient importé'
            : 'nouveaux ingrédients importés';
        $console->success(sprintf(
            '%d %s. Les entrées existantes ont été conservées.',
            $importedIngredientCount,
            $importedIngredientLabel,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array{
     *     ingredients: list<array{canonicalName: string, category: string, aliases: list<string>}>
     * }
     */
    private function readCatalog(): array
    {
        $catalogContents = file_get_contents($this->catalogFilePath);
        if ($catalogContents === false) {
            throw new \RuntimeException('Impossible de lire le catalogue des ingrédients.');
        }

        /** @var mixed $decodedCatalog */
        $decodedCatalog = json_decode($catalogContents, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decodedCatalog) || !isset($decodedCatalog['ingredients'])) {
            throw new \RuntimeException('Le catalogue des ingrédients est invalide.');
        }

        return $decodedCatalog;
    }
}
