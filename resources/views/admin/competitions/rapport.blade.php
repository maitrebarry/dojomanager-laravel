@php
    $r = $report;
    $t = fn (string $key, array $params = []) => __('rapport.' . $key, $params, $lang);
    // Mots des catégories et des tours traduits en arabe ; noms des athlètes inchangés.
    $words = __('rapport.words', [], $lang);
    $w = fn (?string $text) => strtr((string) $text, is_array($words) ? $words : []);
    $name = fn ($athlete) => $athlete ? $athlete->fullName() : '—';
    $date = fn ($d) => $d->copy()->locale($lang)->translatedFormat('l j F Y');
    $duration = function (int $seconds) use ($t) {
        if ($seconds < 60) {
            return $t('duration_s', ['s' => $seconds]);
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);

        return $h ? $t('duration_hm', ['h' => $h, 'm' => $m]) : $t('duration_m', ['m' => max(1, $m)]);
    };
    $percent = $r['fights_total'] ? round($r['fights_played'] / $r['fights_total'] * 100) : 0;
    $attention = array_filter([
        $r['to_weigh'] ? $t('to_weigh', ['count' => $r['to_weigh']]) : null,
        $r['unclassified'] ? $t('unclassified', ['count' => $r['unclassified']]) : null,
        $r['not_drawn'] ? $t('not_drawn', ['count' => $r['not_drawn']]) : null,
    ]);
    $otherLang = request()->fullUrlWithQuery(['lang' => $lang === 'ar' ? 'fr' : 'ar']);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $t('title') }} — {{ $competition->nom }} — {{ $r['day']->format('d-m-Y') }}</title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; background: #eef2f7; color: #0f172a; font-family: {{ $lang === 'ar' ? '"Noto Naskh Arabic", "Amiri", Tahoma, Arial' : '"Segoe UI", Roboto, Arial' }}, sans-serif; font-size: 13px; line-height: 1.5; }
        .actions { max-width: 210mm; margin: 12px auto; display: flex; gap: 8px; justify-content: center; }
        .actions a, .actions button { border: 0; border-radius: 6px; padding: 8px 14px; background: #0f5132; color: #fff; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 14px; }
        .actions .light { background: #6b7280; }
        .actions.settings { flex-wrap: wrap; align-items: center; font-size: 13px; }
        .actions.settings input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px; font-size: 14px; }
        .page { max-width: 210mm; margin: 0 auto 20px; background: #fff; padding: 12mm; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .banner { background: #0f5132; color: #fff; border-radius: 8px; padding: 10px 16px; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .banner h1 { margin: 0; font-size: 20px; }
        .banner .kicker { color: #d4af37; font-weight: 700; font-size: 12px; letter-spacing: 1px; }
        .banner .event { text-align: end; font-size: 13px; }
        h2 { color: #0f5132; font-size: 15px; margin: 18px 0 6px; border-bottom: 2px solid #e8f3ed; padding-bottom: 3px; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 12px; }
        .kpi { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center; }
        .kpi b { display: block; font-size: 20px; }
        .kpi span { color: #64748b; font-size: 11px; }
        .narrative p { margin: 4px 0; text-align: justify; }
        .progress { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin: 6px 0 2px; }
        .progress div { height: 100%; background: #16a34a; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: start; vertical-align: top; }
        th { background: #e8f3ed; color: #0f5132; }
        .muted { color: #64748b; }
        .empty { color: #64748b; font-style: italic; }
        .lines div { border-bottom: 1px dotted #94a3b8; height: 22px; }
        .signs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 10px; text-align: center; }
        .signs div { border-top: 1px solid #334155; padding-top: 4px; margin-top: 40px; font-size: 12px; }
        .footer { margin-top: 12px; color: #94a3b8; font-size: 11px; text-align: center; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #fff; }
            .actions { display: none; }
            .page { box-shadow: none; margin: 0; padding: 0; max-width: none; }
            h2 { break-after: avoid; }
            tr { break-inside: avoid; }
        }
        @media (max-width: 640px) { .kpis { grid-template-columns: repeat(2, 1fr); } .page { padding: 12px; } }
    </style>
</head>
<body>
<div class="actions">
    <button type="button" onclick="window.print()">🖨️ {{ $t('print') }}</button>
    <a href="{{ $otherLang }}" class="light">{{ $t('other_language') }}</a>
    <button type="button" class="light" onclick="window.close()">✖ {{ $t('close') }}</button>
</div>
{{-- Horaires de la journée et rythme annoncé : servent à la prévision (non imprimé). --}}
<form class="actions settings" method="GET">
    @foreach(['lang', 'date', 'aires'] as $keep)<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endforeach
    <label>{{ $t('settings_start') }} <input name="debut" value="{{ $options['start'] }}" placeholder="09:00" size="5"></label>
    <label>{{ $t('settings_end') }} <input name="fin" value="{{ $options['end'] }}" placeholder="14:00" size="5"></label>
    <label>{{ $t('settings_pace') }} <input name="rythme" value="{{ $options['pace'] ? \App\Support\Duration::format($options['pace']) : '' }}" placeholder="4" size="4"></label>
    <button type="submit">↻ {{ $t('settings_apply') }}</button>
</form>

<div class="page">
    <div class="banner">
        <div>
            <div class="kicker">{{ mb_strtoupper($t('title')) }}</div>
            <h1>{{ $t('day', ['date' => $date($r['day'])]) }}</h1>
        </div>
        <div class="event">
            <strong>{{ $competition->nom }}</strong><br>
            {{ $w($profile['label']) }}@if($competition->lieu) — {{ $competition->lieu }}@endif
        </div>
    </div>

    <div class="kpis">
        <div class="kpi"><b>{{ $r['fights_today']->count() }}</b><span>{{ $t('kpi_today') }}</span></div>
        <div class="kpi"><b>{{ $r['fights_played'] }} / {{ $r['fights_total'] }}</b><span>{{ $t('kpi_played') }}</span></div>
        <div class="kpi"><b>{{ $r['fights_remaining'] }}</b><span>{{ $t('kpi_remaining') }}</span></div>
        <div class="kpi"><b>{{ $r['categories_finished'] }} / {{ $r['categories'] }}</b><span>{{ $t('kpi_finished') }}</span></div>
    </div>
    <div class="progress"><div style="width: {{ $percent }}%;"></div></div>
    <div class="muted" style="font-size: 11px;">{{ $t('kpi_athletes') }} : {{ $r['athletes'] }} · {{ $t('kpi_clubs') }} : {{ $r['clubs'] }} · {{ $t('kpi_categories') }} : {{ $r['categories'] }}</div>

    {{-- Texte explicatif généré à partir des chiffres --}}
    <h2>{{ $t('summary') }}</h2>
    <div class="narrative">
        <p>{{ $r['fights_today']->isNotEmpty() ? $t('narrative_today', ['date' => $date($r['day']), 'count' => $r['fights_today']->count()]) : $t('narrative_none', ['date' => $date($r['day'])]) }}
           {{ $t('narrative_total', ['played' => $r['fights_played'], 'total' => $r['fights_total'], 'percent' => $percent]) }}</p>
        @if($r['day_start'] && $r['day_end'])<p>{{ $t('narrative_day', ['start' => $r['day_start']->format('H:i'), 'end' => $r['day_end']->format('H:i')]) }}</p>@endif
        <p>{{ $t('narrative_categories', ['finished' => $r['categories_finished'], 'categories' => $r['categories'], 'today' => $r['finished_today']->count()]) }}</p>
        @if($r['fights_remaining'])
            <p>
                {{ $t('narrative_remaining', ['count' => $r['fights_remaining']]) }}
                @if($r['pace_source'] === 'set')
                    {{ $t('narrative_pace_set', ['pace' => $duration($r['pace_per_area']), 'aires' => $r['aires'], 'duration' => $duration($r['seconds_left'])]) }}
                @elseif($r['pace_seconds'])
                    {{ $t('narrative_pace', ['pace' => $duration($r['pace_seconds']), 'duration' => $duration($r['seconds_left'])]) }}
                @else
                    {{ $t('narrative_theory', ['per_fight' => $duration($r['theoretical_seconds'] * $r['aires']), 'aires' => $r['aires'], 'duration' => $duration($r['seconds_left'])]) }}
                @endif
                @if($r['resume_at'] && $r['estimated_end']){{ $t('narrative_resume', ['start' => $r['resume_at']->format('H:i'), 'time' => $r['estimated_end']->format('H:i')]) }}
                @elseif($r['estimated_end']){{ $t('narrative_end', ['time' => $r['estimated_end']->format('H:i')]) }}@endif
            </p>
        @elseif($r['fights_total'])
            <p>{{ $t('narrative_done') }}</p>
        @endif
        @if($attention)
            <p><strong>{{ $t('narrative_attention', ['items' => implode(' ; ', $attention)]) }}</strong></p>
        @endif
    </div>

    {{-- Combats du jour --}}
    <h2>{{ $t('results') }} ({{ $r['fights_today']->count() }})</h2>
    @if($r['fights_today']->isEmpty())
        <p class="empty">{{ $t('none') }}</p>
    @else
        <table>
            <thead><tr><th>{{ $t('col_time') }}</th><th>{{ $t('col_category') }}</th><th>{{ $t('col_round') }}</th><th>{{ $t('col_winner') }}</th><th>{{ $t('col_loser') }}</th><th>{{ $t('col_score') }}</th></tr></thead>
            <tbody>
                @foreach($r['fights_today'] as $f)
                    <tr>
                        <td>{{ $f['time']?->format('H:i') }}</td>
                        <td>{{ $w($f['category']) }}</td>
                        <td>{{ $w($f['stage']) }}</td>
                        <td><strong>{{ $name($f['winner']) }}</strong></td>
                        <td>{{ $name($f['loser']) }}</td>
                        <td>{{ $f['score'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Podiums des catégories terminées dans la journée --}}
    <h2>{{ $t('podiums') }} ({{ $r['finished_today']->count() }})</h2>
    @if($r['finished_today']->isEmpty())
        <p class="empty">{{ $t('none') }}</p>
    @else
        <table>
            <thead><tr><th>{{ $t('col_category') }}</th><th>🥇 {{ $t('gold') }}</th><th>🥈 {{ $t('silver') }}</th><th>🥉 {{ $t('bronze') }}</th></tr></thead>
            <tbody>
                @foreach($r['finished_today'] as $p)
                    <tr>
                        <td>{{ $w($p['category']) }}</td>
                        <td><strong>{{ $name($p['podium']['gold']) }}</strong></td>
                        <td>{{ $name($p['podium']['silver']) }}</td>
                        <td>{{ collect($p['podium']['bronze'])->map(fn ($a) => $name($a))->implode(', ') ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Médailles cumulées --}}
    <h2>{{ $t('medals') }}</h2>
    @if(empty($r['medals']))
        <p class="empty">{{ $t('none') }}</p>
    @else
        <table>
            <thead><tr><th>{{ $t('col_club') }}</th><th>🥇</th><th>🥈</th><th>🥉</th><th>{{ $t('col_total') }}</th></tr></thead>
            <tbody>
                @foreach($r['medals'] as $club => $m)
                    <tr><td>{{ $w($club) }}</td><td>{{ $m['gold'] }}</td><td>{{ $m['silver'] }}</td><td>{{ $m['bronze'] }}</td><td><strong>{{ $m['gold'] + $m['silver'] + $m['bronze'] }}</strong></td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Ce qui reste --}}
    <h2>{{ $t('remaining') }} ({{ $r['fights_remaining'] }})</h2>
    @if($r['remaining_by_category']->isEmpty())
        <p class="empty">{{ $t('none') }}</p>
    @else
        <table>
            <thead><tr><th>{{ $t('col_category') }}</th><th>{{ $t('col_left') }}</th></tr></thead>
            <tbody>
                @foreach($r['remaining_by_category'] as $c)
                    <tr><td>{{ $w($c['category']) }}</td><td>{{ $c['left'] }} / {{ $c['total'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($r['next_fights']->isNotEmpty())
        <h2>{{ $t('next') }}</h2>
        <table>
            <thead><tr><th>{{ $t('col_order') }}</th>@if($r['aires'] > 1)<th>{{ $t('col_area') }}</th>@endif<th>{{ $t('col_category') }}</th><th>{{ $t('col_round') }}</th><th>{{ $t('col_fighters') }}</th></tr></thead>
            <tbody>
                @foreach($r['next_fights'] as $n)
                    <tr>
                        <td>{{ $n['order'] }}</td>
                        @if($r['aires'] > 1)<td>{{ $n['aire'] }}</td>@endif
                        <td>{{ $w($n['category']) }}</td>
                        <td>{{ $w($n['stage']) }}</td>
                        <td>{{ $name($n['one']) }} — {{ $name($n['two']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Points d'attention --}}
    <h2>{{ $t('attention') }}</h2>
    @php $notes = array_values(array_filter([...$attention, $r['singles'] ? $t('singles', ['count' => $r['singles']]) : null])); @endphp
    @if($notes)
        <ul style="margin: 4px 0; padding-inline-start: 18px;">@foreach($notes as $note)<li>{{ $note }}</li>@endforeach</ul>
    @else
        <p class="empty">{{ $t('nothing') }}</p>
    @endif

    {{-- Observations manuscrites et signatures --}}
    <h2>{{ $t('observations') }}</h2>
    <div class="lines"><div></div><div></div><div></div><div></div></div>

    <h2>{{ $t('signatures') }}</h2>
    <div class="signs">
        <div>{{ $t('sign_organizer') }}</div>
        <div>{{ $t('sign_referee') }}</div>
        <div>{{ $t('sign_delegate') }}</div>
    </div>

    <div class="footer">{{ $t('generated', ['date' => now()->format('d/m/Y'), 'time' => now()->format('H:i')]) }}</div>
</div>
</body>
</html>
