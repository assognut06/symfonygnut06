<?php

namespace App\Application\ViewModel\Tih;

use App\Entity\Tih;

final readonly class TihDetailsViewModel
{
    public function __construct(
        public int $id,
        public string $title,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public string $professionalEmail,
        public string $phone,
        public string $siret,
        public string $address,
        public string $postalCode,
        public string $city,
        public string $fullAddress,
        public array $competences,
        public string $availability,
        public ?\DateTimeInterface $availabilityDate,
        public ?string $rate,
        public ?string $rateType,
        public ?string $cv,
        public ?string $attestationTih,
        public ?string $photo,
    ) {}

    public static function fromEntity(Tih $tih): self
    {
        $competenceNames = [];
        foreach ($tih->getCompetences() as $competence) {
            $competenceNames[] = $competence->getName();
        }

        return new self(
            id: $tih->getId(),
            title: $tih->getTitle() ?? '',
            firstName: $tih->getFirstName() ?? '',
            lastName: $tih->getLastName() ?? '',
            fullName: trim(($tih->getFirstName() ?? '') . ' ' . ($tih->getLastName() ?? '')),
            professionalEmail: $tih->getProfessionalEmail() ?? '',
            phone: $tih->getPhone() ?? '',
            siret: $tih->getSiret() ?? '',
            address: $tih->getAddress() ?? '',
            postalCode: $tih->getPostalCode() ?? '',
            city: $tih->getCity() ?? '',
            fullAddress: trim(sprintf(
                '%s, %s %s',
                $tih->getAddress() ?? '',
                $tih->getPostalCode() ?? '',
                $tih->getCity() ?? ''
            ), ', '),
            competences: $competenceNames,
            availability: $tih->getAvailability() ?? '',
            availabilityDate: $tih->getAvailabilityDate(),
            rate: $tih->getRate(),
            rateType: $tih->getRateType(),
            cv: $tih->getCv(),
            attestationTih: $tih->getAttestationTih(),
            photo: $tih->getPhoto()
        );
    }
}
