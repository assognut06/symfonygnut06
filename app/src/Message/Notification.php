<?php

namespace App\Message;

class Notification {


    public function __construct(private string $content) {
        $this->content = $content;
    }

    public function getContent(): string {
        return $this->content;
    }
}