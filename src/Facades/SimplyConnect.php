<?php

namespace SimplyConnect\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Data\CallQueueEndpoint;
use SimplyConnect\Laravel\Data\CallQueueItem;
use SimplyConnect\Laravel\Data\CallQueuePage;
use SimplyConnect\Laravel\Data\MessageDetails;
use SimplyConnect\Laravel\Data\MessagePage;
use SimplyConnect\Laravel\Data\OutgoingCall;
use SimplyConnect\Laravel\Data\PublishedCallFlow;
use SimplyConnect\Laravel\Data\SmsEndpoint;
use SimplyConnect\Laravel\PendingSms;
use SimplyConnect\Laravel\SimplyConnectManager;
use SimplyConnect\Laravel\Testing\FakeSimplyConnectClient;

/**
 * @method static SimplyConnectClient connection(?string $name = null)
 * @method static PendingSms sms()
 * @method static list<SmsEndpoint> endpoints()
 * @method static MessageDetails message(string $messageId)
 * @method static MessagePage messages(array<string, scalar|null> $filters = [])
 * @method static list<CallQueueEndpoint> callQueueEndpoints()
 * @method static list<PublishedCallFlow> publishedCallFlows()
 * @method static CallQueuePage callQueue(array<string, scalar|null> $filters = [])
 * @method static CallQueueItem queueCall(OutgoingCall $call)
 * @method static FakeSimplyConnectClient fake(?string $connection = null)
 * @method static void assertSmsSentTo(string $recipient, ?callable $callback = null)
 * @method static void assertSmsSentCount(int $count)
 * @method static void assertCallQueuedTo(string $destination, ?callable $callback = null)
 * @method static void assertCallQueuedCount(int $count)
 * @method static void assertNothingSent()
 *
 * @see SimplyConnectManager
 */
final class SimplyConnect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SimplyConnectManager::class;
    }
}
