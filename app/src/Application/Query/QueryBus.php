<?php

namespace App\Application\Query;

use Symfony\Component\DependencyInjection\ServiceLocator;

class QueryBus
{
    /**
     * @param ServiceLocator<mixed> $handlers
     */
    public function __construct(
        private ServiceLocator $handlers
    ) {}

    public function ask(Query $query): mixed
    {
        $handler = $this->handlers->get($query::class);
        
        return $handler($query);
    }
}
