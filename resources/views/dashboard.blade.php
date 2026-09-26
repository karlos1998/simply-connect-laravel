<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Simply Connect · Developer Panel</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #090d14;
            --panel: #101722;
            --panel-soft: #151e2c;
            --line: #263348;
            --text: #f6f8fb;
            --muted: #92a0b6;
            --brand: #ff6b35;
            --brand-soft: rgba(255, 107, 53, .14);
            --green: #35d59a;
            --red: #ff6b76;
            --yellow: #f6c85f;
            --radius: 16px;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.55; }
        a { color: inherit; }
        code, pre, .mono { font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace; }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 250px minmax(0, 1fr); }
        .sidebar { position: sticky; top: 0; height: 100vh; border-right: 1px solid var(--line); padding: 28px 20px; background: #0c111a; }
        .brand { display: flex; gap: 12px; align-items: center; font-weight: 760; font-size: 16px; }
        .brand-mark { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: linear-gradient(145deg, #ff8a3d, #ff4d35); box-shadow: 0 8px 24px rgba(255,107,53,.2); }
        .brand-mark svg { width: 21px; height: 21px; }
        .environment { margin: 28px 0 20px; padding: 12px; border: 1px solid var(--line); border-radius: 12px; background: var(--panel); }
        .eyebrow { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .11em; font-weight: 750; }
        .environment strong { display: block; margin-top: 3px; }
        .nav { display: grid; gap: 5px; }
        .nav a { text-decoration: none; color: var(--muted); padding: 9px 11px; border-radius: 9px; font-weight: 600; }
        .nav a:hover { color: var(--text); background: var(--panel-soft); }
        .nav a:first-child { color: var(--text); background: var(--brand-soft); }
        .sidebar-note { position: absolute; bottom: 22px; left: 20px; right: 20px; color: var(--muted); font-size: 12px; }
        .main { min-width: 0; padding: 38px clamp(24px, 5vw, 72px) 80px; }
        .top { display: flex; justify-content: space-between; gap: 24px; align-items: start; margin-bottom: 30px; }
        h1 { margin: 4px 0 8px; font-size: clamp(27px, 4vw, 40px); line-height: 1.1; letter-spacing: -.035em; }
        h2 { margin: 0; font-size: 19px; letter-spacing: -.015em; }
        h3 { margin: 0 0 5px; font-size: 15px; }
        p { margin: 0; }
        .muted { color: var(--muted); }
        .badge { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--line); background: var(--panel); border-radius: 99px; padding: 8px 12px; color: var(--muted); white-space: nowrap; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); box-shadow: 0 0 0 4px rgba(53,213,154,.12); }
        .flash { margin-bottom: 22px; padding: 14px 16px; border-radius: 12px; border: 1px solid; }
        .flash.success { color: #baf4df; border-color: rgba(53,213,154,.35); background: rgba(53,213,154,.1); }
        .flash.error { color: #ffc2c7; border-color: rgba(255,107,118,.35); background: rgba(255,107,118,.1); }
        .grid { display: grid; gap: 16px; }
        .stats { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 34px; }
        .stat, .card { border: 1px solid var(--line); background: var(--panel); border-radius: var(--radius); }
        .stat { padding: 18px; }
        .stat-value { display: block; margin-top: 8px; font-size: 27px; font-weight: 760; letter-spacing: -.03em; }
        .section { scroll-margin-top: 24px; margin-top: 34px; }
        .section-head { display: flex; justify-content: space-between; gap: 18px; align-items: end; margin-bottom: 14px; }
        .section-head p { color: var(--muted); margin-top: 4px; }
        .two { grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); }
        .card { padding: 21px; min-width: 0; }
        .card-head { display: flex; justify-content: space-between; gap: 12px; align-items: start; margin-bottom: 18px; }
        .pill { display: inline-flex; align-items: center; border-radius: 99px; padding: 4px 9px; font-size: 11px; font-weight: 750; letter-spacing: .03em; text-transform: uppercase; color: var(--green); background: rgba(53,213,154,.1); }
        .pill.off { color: var(--yellow); background: rgba(246,200,95,.1); }
        .stack { display: grid; gap: 14px; }
        label { display: grid; gap: 6px; color: var(--muted); font-size: 12px; font-weight: 650; }
        input, select, textarea { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: #0b111a; color: var(--text); padding: 10px 12px; font: inherit; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
        textarea { min-height: 105px; resize: vertical; }
        button { border: 0; border-radius: 10px; background: var(--brand); color: #fff; padding: 11px 16px; font: inherit; font-weight: 750; cursor: pointer; }
        button:hover { filter: brightness(1.08); }
        .hint { font-size: 12px; color: var(--muted); }
        .error-list { margin: 0 0 18px; padding: 12px 14px 12px 31px; border: 1px solid rgba(255,107,118,.35); border-radius: 10px; color: #ffc2c7; background: rgba(255,107,118,.08); }
        .capabilities { display: grid; gap: 10px; }
        .capability { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .capability:last-child { border-bottom: 0; }
        .capability-meta { min-width: 0; }
        .capability-meta strong { display: block; }
        .capability-meta span { color: var(--muted); font-size: 12px; }
        .status { flex: none; color: var(--green); font-size: 12px; font-weight: 750; }
        .status.error { color: var(--red); }
        .endpoint-list { display: grid; gap: 10px; }
        .endpoint { padding: 13px; border: 1px solid var(--line); border-radius: 11px; background: #0b111a; }
        .endpoint-top { display: flex; justify-content: space-between; gap: 12px; }
        .endpoint small { display: block; margin-top: 5px; color: var(--muted); overflow-wrap: anywhere; }
        .table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--line); }
        th { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .07em; }
        tr:last-child td { border-bottom: 0; }
        td.body { max-width: 320px; white-space: normal; }
        .filters { display: grid; grid-template-columns: 1fr minmax(180px, .65fr) auto; gap: 10px; margin-bottom: 14px; }
        .filters button { align-self: end; }
        .empty, .request-error { padding: 25px; text-align: center; color: var(--muted); border: 1px dashed var(--line); border-radius: 12px; }
        .request-error { color: #ffc2c7; border-color: rgba(255,107,118,.35); background: rgba(255,107,118,.06); }
        pre { margin: 0; overflow: auto; padding: 18px; border-radius: 12px; border: 1px solid var(--line); background: #080d14; color: #dbe5f5; font-size: 12px; line-height: 1.65; }
        .code-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .code-card h3 { margin-bottom: 10px; }
        .footer { margin-top: 46px; padding-top: 20px; border-top: 1px solid var(--line); color: var(--muted); font-size: 12px; }
        @media (max-width: 1050px) { .stats { grid-template-columns: repeat(2, 1fr); } .two, .code-grid { grid-template-columns: 1fr; } }
        @media (max-width: 740px) { .shell { display: block; } .sidebar { position: static; width: 100%; height: auto; border-right: 0; border-bottom: 1px solid var(--line); } .nav { grid-template-columns: repeat(3, 1fr); } .sidebar-note { display: none; } .environment { margin: 18px 0 12px; } .main { padding: 28px 17px 60px; } .top { display: block; } .top .badge { margin-top: 16px; } .stats { grid-template-columns: 1fr 1fr; } .filters { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
@php
    $smsEndpointItems = $smsEndpoints['value'] ?? [];
    $messagePage = $messages['value'] ?? null;
    $callEndpointItems = $callEndpoints['value'] ?? [];
    $flowItems = $flows['value'] ?? [];
    $queuePage = $queue['value'] ?? null;
@endphp
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M6.2 4.8h3.4v8.7a2.5 2.5 0 0 0 2.5 2.5h5.7v3.2h-6.1a5.5 5.5 0 0 1-5.5-5.5V4.8Z" fill="white"/><path d="M14.4 4.8h3.4v7.4h-3.4z" fill="white"/></svg>
            </span>
            <span>Simply Connect</span>
        </div>
        <div class="environment">
            <span class="eyebrow">Środowisko</span>
            <strong>{{ app()->environment() }}</strong>
        </div>
        <nav class="nav" aria-label="Panel">
            <a href="#overview">Przegląd</a>
            <a href="#sms">SMS</a>
            <a href="#messages">Wiadomości</a>
            <a href="#calls">Połączenia</a>
            <a href="#queue">Kolejka</a>
            <a href="#examples">Przykłady</a>
        </nav>
        <p class="sidebar-note">Panel używa autoryzacji Twojej aplikacji. Klucz API nigdy nie trafia do przeglądarki.</p>
    </aside>

    <main class="main">
        <header class="top" id="overview">
            <div>
                <span class="eyebrow">Developer panel</span>
                <h1>Twoje połączenie z API</h1>
                <p class="muted">Sprawdź dostępne zasoby, wykonaj bezpieczny test i skopiuj gotowy kod do aplikacji.</p>
            </div>
            <span class="badge"><span class="dot"></span> Panel aktywny</span>
        </header>

        @if (session('simply-connect-success'))
            <div class="flash success">{{ session('simply-connect-success') }}</div>
        @endif
        @if (session('simply-connect-error'))
            <div class="flash error">{{ session('simply-connect-error') }}</div>
        @endif
        @if ($errors->any())
            <ul class="error-list">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif

        <section class="grid stats" aria-label="Podsumowanie">
            <div class="stat"><span class="eyebrow">Endpointy SMS</span><strong class="stat-value">{{ is_countable($smsEndpointItems) ? count($smsEndpointItems) : '—' }}</strong></div>
            <div class="stat"><span class="eyebrow">Wiadomości</span><strong class="stat-value">{{ $messagePage?->totalElements ?? '—' }}</strong></div>
            <div class="stat"><span class="eyebrow">Bramki połączeń</span><strong class="stat-value">{{ is_countable($callEndpointItems) ? count($callEndpointItems) : '—' }}</strong></div>
            <div class="stat"><span class="eyebrow">Pozycje kolejki</span><strong class="stat-value">{{ $queuePage?->totalElements ?? '—' }}</strong></div>
        </section>

        <section class="section">
            <div class="section-head"><div><h2>Dostęp klucza API</h2><p>Każdy moduł jest odpytywany niezależnie, więc brak jednego uprawnienia nie blokuje całego panelu.</p></div></div>
            <div class="card capabilities">
                @foreach ([
                    ['Endpointy SMS', 'SMS_SEND', $smsEndpoints],
                    ['Historia wiadomości', 'MESSAGES_READ', $messages],
                    ['Bramki i flow połączeń', 'CALL_QUEUE_WRITE', $callEndpoints['error'] ? $callEndpoints : $flows],
                    ['Kolejka połączeń', 'CALL_QUEUE_READ', $queue],
                ] as [$label, $permission, $result])
                    <div class="capability">
                        <div class="capability-meta"><strong>{{ $label }}</strong><span>{{ $permission }}</span></div>
                        <span class="status {{ $result['error'] ? 'error' : '' }}">{{ $result['error'] ? 'Brak dostępu' : 'Dostępne' }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="section" id="sms">
            <div class="section-head"><div><h2>Wyślij testowy SMS</h2><p>Żądanie trafia do tej samej kolejki co wiadomości wysyłane z kodu.</p></div></div>
            <div class="grid two">
                <form class="card stack" method="post" action="{{ route('simply-connect.sms') }}">
                    @csrf
                    <label>Endpoint
                        <select name="endpointId" required>
                            <option value="">Wybierz endpoint</option>
                            @foreach ($smsEndpointItems as $endpoint)
                                <option value="{{ $endpoint->id }}" @selected(old('endpointId', $defaultEndpoint) === $endpoint->id)>{{ $endpoint->name }}{{ $endpoint->phoneNumber ? ' · '.$endpoint->phoneNumber : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Numer odbiorcy <input name="to" value="{{ old('to') }}" placeholder="+48500100200" autocomplete="tel" required></label>
                    <label>Treść <textarea name="body" placeholder="Wiadomość testowa z Simply Connect" required>{{ old('body') }}</textarea></label>
                    <button type="submit">Dodaj SMS do kolejki</button>
                    <p class="hint">Panel generuje unikalny klucz idempotencji dla każdego kliknięcia.</p>
                </form>
                <div class="card">
                    <div class="card-head"><div><h3>Dostępne endpointy</h3><p class="muted">Urządzenia i bramki przypisane do klucza API.</p></div></div>
                    @if ($smsEndpoints['error'])
                        <div class="request-error">{{ $smsEndpoints['error'] }}</div>
                    @elseif (count($smsEndpointItems) === 0)
                        <div class="empty">Brak dostępnych endpointów SMS.</div>
                    @else
                        <div class="endpoint-list">
                            @foreach ($smsEndpointItems as $endpoint)
                                <div class="endpoint"><div class="endpoint-top"><strong>{{ $endpoint->name }}</strong><span class="pill {{ $endpoint->enabled ? '' : 'off' }}">{{ $endpoint->enabled ? 'Aktywny' : 'Wyłączony' }}</span></div><small>{{ $endpoint->phoneNumber ?? 'Bez przypisanego numeru' }} · {{ $endpoint->id }}</small></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="section" id="messages">
            <div class="section-head"><div><h2>Ostatnie wiadomości</h2><p>Podgląd wiadomości przychodzących, wychodzących i ich aktualnego statusu.</p></div></div>
            <div class="card">
                <form class="filters" method="get" action="{{ route('simply-connect.index') }}">
                    <label>Szukaj <input name="query" value="{{ $query }}" placeholder="Numer lub fragment treści"></label>
                    <label>Endpoint
                        <select name="endpointId"><option value="">Wszystkie</option>@foreach ($smsEndpointItems as $endpoint)<option value="{{ $endpoint->id }}" @selected($selectedEndpoint === $endpoint->id)>{{ $endpoint->name }}</option>@endforeach</select>
                    </label>
                    <button type="submit">Filtruj</button>
                </form>
                @if ($messages['error'])
                    <div class="request-error">{{ $messages['error'] }}</div>
                @elseif (!$messagePage || count($messagePage->items) === 0)
                    <div class="empty">Nie znaleziono wiadomości.</div>
                @else
                    <div class="table-wrap"><table><thead><tr><th>Czas</th><th>Kierunek</th><th>Numer</th><th>Treść</th><th>Status</th><th>Endpoint</th></tr></thead><tbody>
                    @foreach ($messagePage->items as $message)
                        <tr><td>{{ $message->occurredAt->format('Y-m-d H:i') }}</td><td>{{ $message->direction }}</td><td class="mono">{{ $message->remoteAddress }}</td><td class="body">{{ $message->body }}</td><td><span class="pill">{{ $message->status->value }}</span></td><td>{{ $message->endpoint->name }}</td></tr>
                    @endforeach
                    </tbody></table></div>
                @endif
            </div>
        </section>

        <section class="section" id="calls">
            <div class="section-head"><div><h2>Dodaj połączenie</h2><p>Uruchom opublikowany IVR flow teraz albo zaplanuj go na później.</p></div></div>
            <div class="grid two">
                <form class="card stack" method="post" action="{{ route('simply-connect.call-queue') }}">
                    @csrf
                    <label>Bramka
                        <select name="endpointId" required><option value="">Wybierz bramkę</option>@foreach ($callEndpointItems as $endpoint)<option value="{{ $endpoint->id }}" @selected(old('endpointId') === $endpoint->id)>{{ $endpoint->name }} · {{ $endpoint->gatewayName }}{{ $endpoint->phoneNumber ? ' · '.$endpoint->phoneNumber : '' }}</option>@endforeach</select>
                    </label>
                    <label>Opublikowany flow
                        <select name="flowVersionId" required><option value="">Wybierz flow</option>@foreach ($flowItems as $flow)<option value="{{ $flow->publishedVersionId }}" @selected(old('flowVersionId') === $flow->publishedVersionId)>{{ $flow->name }}</option>@endforeach</select>
                    </label>
                    <label>Numer docelowy <input name="destination" value="{{ old('destination') }}" placeholder="+48500100200" required></label>
                    <label>Zaplanowane na <input type="datetime-local" name="scheduledFor" value="{{ old('scheduledFor') }}"></label>
                    <div class="grid" style="grid-template-columns: 1fr 1fr">
                        <label>Strefa czasowa <input name="timeZone" value="{{ old('timeZone', config('app.timezone', 'Europe/Warsaw')) }}" required></label>
                        <label>Odstęp (sekundy) <input type="number" name="intervalSeconds" value="{{ old('intervalSeconds', 30) }}" min="0" max="3600" required></label>
                    </div>
                    <button type="submit">Dodaj połączenie do kolejki</button>
                </form>
                <div class="card">
                    <div class="card-head"><div><h3>Gotowość telefonii</h3><p class="muted">Widoczne są tylko zasoby udostępnione temu kluczowi.</p></div></div>
                    @if ($callEndpoints['error'] || $flows['error'])
                        <div class="request-error">{{ $callEndpoints['error'] ?? $flows['error'] }}</div>
                    @else
                        <div class="capabilities">
                            <div class="capability"><div class="capability-meta"><strong>Bramki</strong><span>Endpointy zdolne wykonywać połączenia</span></div><span class="status">{{ count($callEndpointItems) }}</span></div>
                            <div class="capability"><div class="capability-meta"><strong>Opublikowane flow</strong><span>Wersje możliwe do uruchomienia</span></div><span class="status">{{ count($flowItems) }}</span></div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="section" id="queue">
            <div class="section-head"><div><h2>Kolejka połączeń</h2><p>Najbliższe oraz ostatnio zaplanowane połączenia wychodzące.</p></div></div>
            <div class="card">
                @if ($queue['error'])
                    <div class="request-error">{{ $queue['error'] }}</div>
                @elseif (!$queuePage || count($queuePage->items) === 0)
                    <div class="empty">Kolejka połączeń jest pusta.</div>
                @else
                    <div class="table-wrap"><table><thead><tr><th>Termin</th><th>Numer</th><th>Flow</th><th>Źródło</th><th>Status</th><th>Powód</th></tr></thead><tbody>
                    @foreach ($queuePage->items as $item)
                        <tr><td>{{ $item->notBefore->format('Y-m-d H:i') }}</td><td class="mono">{{ $item->destination }}</td><td>{{ $item->flowName }} v{{ $item->flowVersion }}</td><td>{{ $item->source }}</td><td><span class="pill">{{ $item->status }}</span></td><td>{{ $item->reason ?? '—' }}</td></tr>
                    @endforeach
                    </tbody></table></div>
                @endif
            </div>
        </section>

        <section class="section" id="examples">
            <div class="section-head"><div><h2>Przykłady do skopiowania</h2><p>Panel pokazuje te same operacje, które udostępnia klient PHP.</p></div></div>
            <div class="grid code-grid">
                <div class="card code-card"><h3>Wyślij SMS</h3><pre><code>use SimplyConnect\Laravel\Facades\SimplyConnect;

$receipt = SimplyConnect::sms()
    ->via('support')
    ->to('+48500100200')
    ->text('Twoje zamówienie jest gotowe.')
    ->send();</code></pre></div>
                <div class="card code-card"><h3>Pobierz wiadomości</h3><pre><code>$messages = SimplyConnect::messages([
    'endpointId' =&gt; $endpointId,
    'query' =&gt; $phoneNumber,
    'size' =&gt; 25,
]);

foreach ($messages->items as $message) {
    logger($message->status->value);
}</code></pre></div>
                <div class="card code-card"><h3>Dodaj połączenie</h3><pre><code>use Illuminate\Support\Str;
use SimplyConnect\Laravel\Data\OutgoingCall;

$call = SimplyConnect::queueCall(new OutgoingCall(
    endpointId: $endpointId,
    flowVersionId: $flowVersionId,
    destination: '+48500100200',
    requestId: (string) Str::uuid(),
));</code></pre></div>
                <div class="card code-card"><h3>Autoryzacja panelu</h3><pre><code>Gate::define('viewSimplyConnect',
    fn (User $user): bool =&gt; in_array(
        $user->email,
        ['developer@example.com'],
        true,
    )
);</code></pre></div>
            </div>
        </section>

        <footer class="footer">Simply Connect developer panel · ścieżka <span class="mono">/{{ $panelPath }}</span> · dostęp kontrolowany przez Twoją aplikację Laravel</footer>
    </main>
</div>
</body>
</html>
