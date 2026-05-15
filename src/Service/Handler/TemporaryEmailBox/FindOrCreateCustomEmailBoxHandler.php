<?php

namespace App\Service\Handler\TemporaryEmailBox;

use App\Entity\TemporaryEmailBox;
use App\Exception\Domain\ThereAreNoDomainsException;
use App\Repository\DomainRepository;
use App\Repository\TemporaryEmailBoxRepository;
use App\Service\Factory\TemporaryEmailBoxFactory;
use Doctrine\ORM\EntityManagerInterface;

class FindOrCreateCustomEmailBoxHandler
{
    public function __construct(
        private DomainRepository $domainRepository,
        private TemporaryEmailBoxRepository $temporaryEmailBoxRepository,
        private TemporaryEmailBoxFactory $emailBoxFactory,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findOrCreate(string $name, string $creatorIp, ?string $countryCode): TemporaryEmailBox
    {
        $domain = $this->domainRepository->findOneActiveDomain();

        if ($domain === null) {
            throw new ThereAreNoDomainsException();
        }

        $emailAddress = strtolower($name) . '@' . $domain->getDomain();

        $existing = $this->temporaryEmailBoxRepository->findOneBy(['email' => $emailAddress]);
        if ($existing instanceof TemporaryEmailBox) {
            return $existing;
        }

        $emailBox = $this->emailBoxFactory->create($emailAddress, $creatorIp, $countryCode);

        $this->entityManager->persist($emailBox);
        $this->entityManager->flush();

        return $emailBox;
    }
}
