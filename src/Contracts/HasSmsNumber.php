<?php

namespace SimplyConnect\Laravel\Contracts;

interface HasSmsNumber
{
    public function smsNumber(): string;
}
