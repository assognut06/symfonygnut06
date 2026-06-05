<?php

namespace App\Tests\Rules\data;

class SuperGlobalCall
{
    private string $value;
    private string $value2;
    private string $value3;
    private string $value4;
    private string $value5;
    private string $value6;
    private string $value7;

    public function __construct()
    {
        $this->value = $_GET['param']; // This should trigger the NoSuperGlobalRule
        $this->value2 = $_POST['param']; // This should also trigger the NoSuperGlobalRule
        $this->value3 = $_SERVER['HTTP_HOST']; // This should also trigger the NoSuperGlobalRule
        $this->value4 = $_COOKIE['cookie']; // This should also trigger the NoSuperGlobalRule
        $this->value5 = $_FILES['file']; // This should also trigger the NoSuperGlobalRule
        $this->value6 = $_ENV['ENV_VAR']; // This should also trigger the NoSuperGlobalRule
        $this->value7 = $_REQUEST['request']; // This should also trigger the NoSuperGlobalRule
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getValue2(): string
    {
        return $this->value2;
    }
    public function getValue3(): string
    {
        return $this->value3;
    }
    public function getValue4(): string
    {
        return $this->value4;
    }
    public function getValue5(): string
    {
        return $this->value5;
    }
    public function getValue6(): string
    {
        return $this->value6;
    }
    public function getValue7(): string
    {
        return $this->value7;
    }
}
