<?php

namespace App\Application\Query;

use Symfony\Component\DependencyInjection\ServiceLocator;

class QueryBus
{
    public function __construct(
        private ServiceLocator $handlers
    ) {}

    public function ask(object $query): mixed
    {
        $handler = $this->handlers->get($query::class);
        
        return $handler($query);
    }
}
