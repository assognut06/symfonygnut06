<?php

namespace App\MessageHandler;

use App\Message\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotificationHandler
{


    public function __construct(private EntityManagerInterface $em, 
    private LoggerInterface $logger) 
    {
        $this->em = $em;
        $this->logger = $logger;
    }


    public function __invoke(Notification $message) 
    {
        $this->logger->info("Received SmsNotification message: " . $message->getContent());
        $this->em->persist($message);
        $this->em->flush();
        
    }
}
