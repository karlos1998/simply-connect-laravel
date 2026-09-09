<?php

namespace SimplyConnect\Laravel\Contracts;

use SimplyConnect\Laravel\Data\MessageDetails;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Data\SmsEndpoint;
use SimplyConnect\Laravel\Data\SmsReceipt;
use SimplyConnect\Laravel\PendingSms;

interface SimplyConnectClient
{
    public function sms(): PendingSms;

    public function sendSms(OutgoingSms $sms): SmsReceipt;

    /** @return list<SmsEndpoint> */
    public function endpoints(): array;

    public function message(string $messageId): MessageDetails;
}
