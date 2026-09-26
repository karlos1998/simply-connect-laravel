<?php

namespace SimplyConnect\Laravel\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Data\OutgoingCall;
use SimplyConnect\Laravel\Exceptions\AuthenticationException;
use SimplyConnect\Laravel\Exceptions\SimplyConnectException;
use SimplyConnect\Laravel\SimplyConnectManager;

final class PanelController extends Controller
{
    public function __construct(
        private readonly SimplyConnectManager $manager,
        private readonly ViewFactory $views,
    ) {}

    public function index(Request $request): View
    {
        $client = $this->client();
        $query = $request->string('query')->trim()->toString();
        $endpointId = $request->string('endpointId')->trim()->toString();

        return $this->views->file(__DIR__.'/../../../resources/views/dashboard.blade.php', [
            'panelPath' => trim((string) config('simply-connect.panel.path', 'simply-connect'), '/'),
            'defaultEndpoint' => config('simply-connect.connections.'.config('simply-connect.default', 'default').'.default_endpoint'),
            'query' => $query,
            'selectedEndpoint' => $endpointId,
            'smsEndpoints' => $this->attempt(fn (): array => $client->endpoints()),
            'messages' => $this->attempt(fn () => $client->messages([
                'query' => $query !== '' ? $query : null,
                'endpointId' => $endpointId !== '' ? $endpointId : null,
                'page' => 0,
                'size' => 25,
            ])),
            'callEndpoints' => $this->attempt(fn (): array => $client->callQueueEndpoints()),
            'flows' => $this->attempt(fn (): array => $client->publishedCallFlows()),
            'queue' => $this->attempt(fn () => $client->callQueue([
                'page' => 0,
                'size' => 25,
                'sort' => 'notBefore',
                'descending' => false,
            ])),
        ]);
    }

    public function sendSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'endpointId' => ['required', 'uuid'],
            'to' => ['required', 'string', 'max:32'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $receipt = $this->client()->sms()
                ->via($validated['endpointId'])
                ->to($validated['to'])
                ->text($validated['body'])
                ->withIdempotencyKey('panel-sms-'.Str::uuid())
                ->send();

            return back()->with('simply-connect-success', "SMS został dodany do kolejki: {$receipt->messageId} ({$receipt->status->value}).");
        } catch (\Throwable $failure) {
            return back()->withInput()->with('simply-connect-error', $this->friendly($failure));
        }
    }

    public function queueCall(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'endpointId' => ['required', 'uuid'],
            'flowVersionId' => ['required', 'uuid'],
            'destination' => ['required', 'string', 'max:32'],
            'scheduledFor' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'timeZone' => ['required', 'timezone:all', 'max:80'],
            'intervalSeconds' => ['required', 'integer', 'min:0', 'max:3600'],
        ]);

        try {
            $created = $this->client()->queueCall(new OutgoingCall(
                endpointId: $validated['endpointId'],
                flowVersionId: $validated['flowVersionId'],
                destination: $validated['destination'],
                requestId: (string) Str::uuid(),
                scheduledFor: isset($validated['scheduledFor'])
                    ? new DateTimeImmutable($validated['scheduledFor'])
                    : null,
                timeZone: $validated['timeZone'],
                intervalSeconds: (int) $validated['intervalSeconds'],
            ));

            return back()->with('simply-connect-success', "Połączenie zostało dodane do kolejki: {$created->id} ({$created->status}).");
        } catch (\Throwable $failure) {
            return back()->withInput()->with('simply-connect-error', $this->friendly($failure));
        }
    }

    private function client(): SimplyConnectClient
    {
        $connection = config('simply-connect.panel.connection');

        return $this->manager->connection(is_string($connection) && $connection !== '' ? $connection : null);
    }

    /** @return array{value: mixed, error: string|null} */
    private function attempt(callable $action): array
    {
        try {
            return ['value' => $action(), 'error' => null];
        } catch (\Throwable $failure) {
            return ['value' => null, 'error' => $this->friendly($failure)];
        }
    }

    private function friendly(\Throwable $failure): string
    {
        if ($failure instanceof AuthenticationException) {
            return 'Klucz API nie ma wymaganego uprawnienia albo dostępu do endpointu.';
        }

        if ($failure instanceof SimplyConnectException) {
            $suffix = $failure->correlationId !== null ? " Correlation ID: {$failure->correlationId}." : '';

            return $failure->getMessage().$suffix;
        }

        return app()->hasDebugModeEnabled()
            ? $failure->getMessage()
            : 'Operacja nie powiodła się. Sprawdź log aplikacji.';
    }
}
