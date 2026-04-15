<?php

namespace App\Application\Command\Tih;

use App\Application\DTO\Tih\TihContactDTO;

final readonly class SendTihContactCommand
{
    public function __construct(
        public int $tihId,
        public TihContactDTO $contactData
    ) {}
}
