<?php

// src/Form/EventListener/PremiereLettreMajuscule.php
namespace App\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PremiereLettreMajuscule implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();

        if (!is_array($data)) {
            return;
        }

        $fieldsToCapitalize = ['nom', 'prenom', 'ville', 'pays'];

        foreach ($fieldsToCapitalize as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $data[$field] = ucfirst(strtolower(trim($data[$field])));
            }
        }

        $event->setData($data);
    }
}
