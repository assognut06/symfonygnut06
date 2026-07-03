<?php

namespace App\Application\DTO\Tih;

use App\Entity\Tih;

final readonly class TihMapProfileDTO implements \JsonSerializable
{
    public function __construct(
        public ?int $id,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $city,
        public ?string $postalCode,
        public ?string $photo,
        public ?string $rate,
        public ?string $rateType,
        public ?string $availability,
        public ?string $availabilityDate,
        public string $detailUrl,
    ) {
    }

    public static function fromEntity(Tih $tih, string $detailUrl): self
    {
        return new self(
            id: $tih->getId(),
            firstName: $tih->getFirstName(),
            lastName: $tih->getLastName(),
            city: $tih->getCity(),
            postalCode: $tih->getPostalCode(),
            photo: $tih->getPhoto(),
            rate: $tih->getRate(),
            rateType: $tih->getRateType(),
            availability: $tih->getAvailability(),
            availabilityDate: $tih->getAvailabilityDate()?->format('Y-m-d'),
            detailUrl: $detailUrl,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
            'photo' => $this->photo,
            'rate' => $this->rate,
            'rateType' => $this->rateType,
            'availability' => $this->availability,
            'availabilityDate' => $this->availabilityDate,
            'detailUrl' => $this->detailUrl,
        ];
    }
}
