<?php

namespace SimplyConnect\Laravel\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use SimplyConnect\Laravel\SimplyConnectApplicationServiceProvider;
use SimplyConnect\Laravel\SimplyConnectPanel;
use SimplyConnect\Laravel\SimplyConnectServiceProvider;

final class PanelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PanelApplicationServiceProvider::class,
            SimplyConnectServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('simply-connect.panel.enabled', true);
        $app['config']->set('simply-connect.panel.path', 'simply-connect');
    }

    protected function tearDown(): void
    {
        SimplyConnectPanel::flushAuthorization();

        parent::tearDown();
    }

    public function test_local_environment_can_open_the_panel_without_a_user(): void
    {
        $this->useLocalEnvironment();
        Http::fake(['*' => Http::response([], 403)]);

        $this->get('/simply-connect')
            ->assertOk()
            ->assertSee('Developer panel')
            ->assertSee('Twoje połączenie z API');
    }

    public function test_non_local_environment_is_forbidden_until_the_gate_allows_the_user(): void
    {
        Http::fake();

        $this->get('/simply-connect')->assertForbidden();
    }

    public function test_non_local_environment_uses_the_published_provider_gate(): void
    {
        Http::fake(['*' => Http::response([], 403)]);
        $user = new PanelUser;
        $user->id = 7;
        $user->email = '[email protected]';

        $this->actingAs($user)
            ->get('/simply-connect')
            ->assertOk()
            ->assertSee('Twoje połączenie z API');
    }

    public function test_dashboard_renders_every_supported_api_area(): void
    {
        $this->useLocalEnvironment();
        $this->fakeDashboardApi();

        $this->get('/simply-connect')
            ->assertOk()
            ->assertSee('Support SIM')
            ->assertSee('Your order is ready')
            ->assertSee('Voice gateway')
            ->assertSee('Order confirmation')
            ->assertSee('+48500100200');

        Http::assertSentCount(5);
    }

    public function test_panel_can_send_an_sms_with_a_fresh_idempotency_key(): void
    {
        $this->useLocalEnvironment();
        Http::fake([
            '*/api/v1/external/messages' => Http::response([
                'messageId' => '01994f44-755a-7d10-bb59-597ac6123d5f',
                'dispatchId' => '01994f44-755a-7d10-bb59-597ac6123d60',
                'commandId' => '01994f44-755a-7d10-bb59-597ac6123d61',
                'status' => 'QUEUED',
            ], 202),
        ]);

        $this->withSession(['_token' => 'panel-test-token'])->post('/simply-connect/sms', [
            '_token' => 'panel-test-token',
            'endpointId' => '01994f44-755a-7d10-bb59-597ac6123d50',
            'to' => '+48500100200',
            'body' => 'Test from the panel',
        ])->assertRedirect()->assertSessionHas('simply-connect-success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_starts_with($request->header('Idempotency-Key')[0] ?? '', 'panel-sms-')
            && $request['body'] === 'Test from the panel');
    }

    public function test_panel_can_queue_an_outgoing_call(): void
    {
        $this->useLocalEnvironment();
        Http::fake([
            '*/api/v1/external/call-queue' => Http::response($this->callQueueItem(), 201),
        ]);

        $this->withSession(['_token' => 'panel-test-token'])->post('/simply-connect/call-queue', [
            '_token' => 'panel-test-token',
            'endpointId' => '01994f44-755a-7d10-bb59-597ac6123d70',
            'flowVersionId' => '01994f44-755a-7d10-bb59-597ac6123d71',
            'destination' => '+48500100200',
            'scheduledFor' => '2026-09-28T10:30',
            'timeZone' => 'Europe/Warsaw',
            'intervalSeconds' => 30,
        ])->assertRedirect()->assertSessionHas('simply-connect-success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request['destination'] === '+48500100200'
            && $request['scheduledFor'] === '2026-09-28T10:30:00'
            && is_string($request['requestId']));
    }

    private function fakeDashboardApi(): void
    {
        Http::fake([
            '*/api/v1/external/endpoints' => Http::response([[
                'id' => '01994f44-755a-7d10-bb59-597ac6123d50',
                'name' => 'Support SIM',
                'phoneNumber' => '+48511929271',
                'enabled' => true,
            ]]),
            '*/api/v1/external/messages*' => Http::response([
                'items' => [[
                    'id' => 'message-id',
                    'conversationId' => 'conversation-id',
                    'direction' => 'OUTBOUND',
                    'channel' => 'SMS',
                    'remoteAddress' => '+48500100200',
                    'body' => 'Your order is ready',
                    'occurredAt' => '2026-09-27T10:00:00Z',
                    'status' => 'DELIVERED',
                    'endpoint' => [
                        'id' => '01994f44-755a-7d10-bb59-597ac6123d50',
                        'name' => 'Support SIM',
                        'phoneNumber' => '+48511929271',
                        'enabled' => true,
                    ],
                ]],
                'page' => 0,
                'size' => 25,
                'totalElements' => 1,
                'totalPages' => 1,
            ]),
            '*/api/v1/external/call-queue/endpoints' => Http::response([[
                'id' => '01994f44-755a-7d10-bb59-597ac6123d70',
                'name' => 'Sales line',
                'phoneNumber' => '+48511929272',
                'gatewayId' => 'gateway-id',
                'gatewayName' => 'Voice gateway',
                'gatewayStatus' => 'ONLINE',
            ]]),
            '*/api/v1/external/call-queue/flows' => Http::response([[
                'id' => 'flow-id',
                'name' => 'Order confirmation',
                'description' => 'Ask the customer to confirm.',
                'publishedVersionId' => '01994f44-755a-7d10-bb59-597ac6123d71',
                'updatedAt' => '2026-09-27T09:00:00Z',
            ]]),
            '*/api/v1/external/call-queue*' => Http::response([
                'items' => [$this->callQueueItem()],
                'page' => 0,
                'totalElements' => 1,
                'totalPages' => 1,
            ]),
        ]);
    }

    private function useLocalEnvironment(): void
    {
        if ($this->app === null) {
            throw new \LogicException('The Testbench application has not been created.');
        }

        $this->app->instance('env', 'local');
    }

    /** @return array<string, mixed> */
    private function callQueueItem(): array
    {
        return [
            'id' => 'queue-item-id',
            'endpointId' => '01994f44-755a-7d10-bb59-597ac6123d70',
            'flowVersionId' => '01994f44-755a-7d10-bb59-597ac6123d71',
            'flowName' => 'Order confirmation',
            'flowVersion' => 2,
            'destination' => '+48500100200',
            'source' => 'API',
            'status' => 'QUEUED',
            'notBefore' => '2026-09-28T10:30:00Z',
            'timeZone' => 'Europe/Warsaw',
            'intervalSeconds' => 30,
            'createdAt' => '2026-09-27T10:00:00Z',
            'updatedAt' => '2026-09-27T10:00:00Z',
            'callId' => null,
            'reason' => null,
        ];
    }
}

final class PanelApplicationServiceProvider extends SimplyConnectApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewSimplyConnect', static fn (PanelUser $user): bool => $user->email === '[email protected]');
    }
}

final class PanelUser extends User
{
    public int $id = 0;

    public string $email = '';
}
