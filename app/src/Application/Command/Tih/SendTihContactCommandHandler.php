<?php

namespace App\Application\Command\Tih;

use App\Repository\TihRepository;
use App\Service\TihEmailService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final readonly class SendTihContactCommandHandler
{
    public function __construct(
        private TihRepository $tihRepository,
        private TihEmailService $emailService
    ) {}

    /**
     * @throws EntityNotFoundException
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendTihContactCommand $command): void
    {
        $tih = $this->tihRepository->find($command->tihId);
        
        if (!$tih) {
            throw new EntityNotFoundException(
                sprintf('TIH with ID %d not found', $command->tihId)
            );
        }

        $this->emailService->sendContactEmail($tih, $command->contactData);
    }
}
