<?php

namespace SimplyConnect\Laravel\Contracts;

use SimplyConnect\Laravel\Data\CallQueueEndpoint;
use SimplyConnect\Laravel\Data\CallQueueItem;
use SimplyConnect\Laravel\Data\CallQueuePage;
use SimplyConnect\Laravel\Data\MessageDetails;
use SimplyConnect\Laravel\Data\MessagePage;
use SimplyConnect\Laravel\Data\OutgoingCall;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Data\PublishedCallFlow;
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

    /** @param array<string, scalar|null> $filters */
    public function messages(array $filters = []): MessagePage;

    /** @return list<CallQueueEndpoint> */
    public function callQueueEndpoints(): array;

    /** @return list<PublishedCallFlow> */
    public function publishedCallFlows(): array;

    /** @param array<string, scalar|null> $filters */
    public function callQueue(array $filters = []): CallQueuePage;

    public function queueCall(OutgoingCall $call): CallQueueItem;
}
