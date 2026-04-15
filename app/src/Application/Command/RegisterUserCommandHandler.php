<?php

namespace App\Application\Command;

use App\Domain\Event\UserRegisteredEvent;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\PasswordHasherInterface;
use App\Entity\Tih;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RegisterUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(RegisterUserCommand $command): User
    {
        // Create user with proper encapsulation
        $user = new User();
        $user->setEmail($command->getEmail());
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $command->getPlainPassword()
        );
        $user->setPassword($hashedPassword);

        // Set roles based on user type (domain logic)
        $user->setRoles($command->getUserType()->getRoles());

        // Handle TIH-specific setup
        if ($command->isTih()) {
            $tih = new Tih();
            $user->setTih($tih);
        }

        // Persist
        $this->userRepository->save($user, true);

        // Dispatch domain event
        $event = new UserRegisteredEvent(
            userId: $user->getId(),
            email: $user->getEmail(),
            isTih: $command->isTih()
        );
        $this->eventDispatcher->dispatch($event);

        return $user;
    }
}
