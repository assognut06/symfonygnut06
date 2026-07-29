<?php

namespace App\Application\DTO\Tih;

final readonly class RateTypeFilterDTO
{
    public function __construct(
        public string $rateType,
        public int $count,
    ) {}
    /**
 * @param array{rateType:string,count:int} $data
 */
    public static function fromArray(array $data): self
    {
        return new self(
            rateType: $data['rateType'],
            count: (int)$data['count']
        );
    }}
