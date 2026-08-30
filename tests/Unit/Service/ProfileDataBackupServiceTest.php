<?php

namespace App\Tests\Unit\Service;

use App\Entity\Profile;
use App\Entity\WeightEntry;
use App\Service\ProfileDataBackupService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProfileDataBackupServiceTest extends TestCase
{
    #[Test]
    public function itExportsAndRestoresACompleteCompatibleBackup(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(static fn (callable $operation) => $operation());
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(WeightEntry::class));

        $sourceProfile = (new Profile())
            ->setHeight(178.0)
            ->setBirthDate(new \DateTimeImmutable('1990-04-12'))
            ->setBiologicalGender('homme')
            ->setStartingWeight(105.5)
            ->setTargetWeight(82.0);
        $sourceProfile->addWeightEntry(
            (new WeightEntry())->setWeight(99.4)->setMeasuredAt(new \DateTimeImmutable('2026-08-30 08:00:00'))->setNote('Sauvegarde'),
        );

        $backupService = new ProfileDataBackupService($entityManager);
        $backupJson = json_encode($backupService->exportProfile($sourceProfile), JSON_THROW_ON_ERROR);

        $targetProfile = (new Profile())->setHeight(160)->setBirthDate(new \DateTimeImmutable('2000-01-01'))->setBiologicalGender('autre');
        $importedEntryCount = $backupService->importProfile($targetProfile, $backupJson);

        self::assertSame(1, $importedEntryCount);
        self::assertSame(178.0, $targetProfile->getHeight());
        self::assertSame('1990-04-12', $targetProfile->getBirthDate()->format('Y-m-d'));
        self::assertSame(105.5, $targetProfile->getStartingWeight());
        self::assertSame(82.0, $targetProfile->getTargetWeight());
    }

    #[Test]
    public function itRejectsAnInvalidDocumentBeforeTouchingTheDatabase(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('wrapInTransaction');
        $backupService = new ProfileDataBackupService($entityManager);

        $this->expectException(\InvalidArgumentException::class);
        $backupService->importProfile(new Profile(), '{invalid json');
    }
}
