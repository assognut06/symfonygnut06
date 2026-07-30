<?php

namespace App\Tests\Unit\Service;

use App\Entity\AssoRecommander;
use App\Repository\AssoRecommanderRepository;
use App\Service\PaginationService;
use PHPUnit\Framework\TestCase;

class PaginationServiceTest extends TestCase
{
    public function testRecommendedAssociationsArePaginated(): void
    {
        $associations = [new AssoRecommander(), new AssoRecommander()];
        $cities = [['city' => 'Nice'], ['city' => 'Cannes']];
        $repository = $this->createMock(AssoRecommanderRepository::class);

        $repository->expects(self::once())
            ->method('findBy')
            ->with([], [], 10, 10)
            ->willReturn($associations);
        $repository->expects(self::once())
            ->method('count')
            ->with([])
            ->willReturn(23);
        $repository->expects(self::once())
            ->method('findDistinctCities')
            ->willReturn($cities);

        $pagination = (new PaginationService(10, $repository))
            ->getPaginatedData(AssoRecommander::class, 2);

        self::assertSame($associations, $pagination['data']);
        self::assertSame(23, $pagination['total']);
        self::assertSame(3, $pagination['pages']);
        self::assertSame(2, $pagination['current_page']);
        self::assertSame($cities, $pagination['cities']);
    }
}
