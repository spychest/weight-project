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
    private const SUPPORTED_SCHEMA_VERSION = 1;

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
     *     '$schema': string,
     *     schemaVersion: int,
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
        if (!is_array($decodedCatalog)) {
            throw new \RuntimeException('Le catalogue des ingrédients est invalide.');
        }

        $topLevelKeys = array_keys($decodedCatalog);
        sort($topLevelKeys);
        if ($topLevelKeys !== ['$schema', 'ingredients', 'schemaVersion']) {
            throw new \RuntimeException('La structure principale du catalogue est invalide.');
        }

        if (($decodedCatalog['schemaVersion'] ?? null) !== self::SUPPORTED_SCHEMA_VERSION) {
            throw new \RuntimeException(sprintf(
                'La version du catalogue est absente ou incompatible. Version attendue : %d.',
                self::SUPPORTED_SCHEMA_VERSION,
            ));
        }

        if (($decodedCatalog['$schema'] ?? null) !== './ingredient_catalog.schema.json') {
            throw new \RuntimeException('La référence au schéma JSON du catalogue est invalide.');
        }

        $ingredients = $decodedCatalog['ingredients'] ?? null;
        if (!is_array($ingredients) || !array_is_list($ingredients) || $ingredients === []) {
            throw new \RuntimeException('La liste des ingrédients est absente ou invalide.');
        }

        foreach ($ingredients as $ingredientIndex => $ingredientData) {
            $this->validateIngredientData($ingredientData, $ingredientIndex);
        }

        /** @var array{'$schema': string, schemaVersion: int, ingredients: list<array{canonicalName: string, category: string, aliases: list<string>}>} $decodedCatalog */
        return $decodedCatalog;
    }

    private function validateIngredientData(mixed $ingredientData, int $ingredientIndex): void
    {
        if (!is_array($ingredientData)) {
            throw new \RuntimeException(sprintf('L’ingrédient à la position %d est invalide.', $ingredientIndex));
        }

        $expectedKeys = ['aliases', 'canonicalName', 'category'];
        $actualKeys = array_keys($ingredientData);
        sort($actualKeys);
        if ($actualKeys !== $expectedKeys) {
            throw new \RuntimeException(sprintf(
                'La structure de l’ingrédient à la position %d est invalide.',
                $ingredientIndex,
            ));
        }

        foreach (['canonicalName', 'category'] as $requiredStringKey) {
            $value = $ingredientData[$requiredStringKey];
            if (!is_string($value) || trim($value) === '' || mb_strlen($value) > 160) {
                throw new \RuntimeException(sprintf(
                    'Le champ « %s » de l’ingrédient à la position %d est invalide.',
                    $requiredStringKey,
                    $ingredientIndex,
                ));
            }
        }

        $aliases = $ingredientData['aliases'];
        if (!is_array($aliases) || !array_is_list($aliases)) {
            throw new \RuntimeException(sprintf(
                'Les alias de l’ingrédient à la position %d doivent former une liste.',
                $ingredientIndex,
            ));
        }

        foreach ($aliases as $alias) {
            if (!is_string($alias) || trim($alias) === '' || mb_strlen($alias) > 160) {
                throw new \RuntimeException(sprintf(
                    'Un alias de l’ingrédient à la position %d est invalide.',
                    $ingredientIndex,
                ));
            }
        }

        if (count($aliases) !== count(array_unique($aliases))) {
            throw new \RuntimeException(sprintf(
                'L’ingrédient à la position %d contient des alias en double.',
                $ingredientIndex,
            ));
        }
    }
}
