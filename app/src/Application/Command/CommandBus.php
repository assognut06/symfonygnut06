<?php

namespace App\Application\Command;

use Psr\Container\ContainerInterface;

class CommandBus
{
    public function __construct(
        private ContainerInterface $handlers
    ) {}

    public function dispatch(object $command): void
    {
        $commandClass = get_class($command);
        
        if (!$this->handlers->has($commandClass)) {
            throw new \RuntimeException(
                sprintf('No handler registered for command "%s"', $commandClass)
            );
        }

        $handler = $this->handlers->get($commandClass);
        $handler($command);
    }
}
