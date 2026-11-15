<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Chrono — {{ $competition->nom }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #0b1220; color: #f8fafc; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        header { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #111a2e; }
        header a, header button { color: #cbd5e1; background: none; border: 1px solid #334155; border-radius: 8px; padding: 6px 10px; font-size: 14px; text-decoration: none; cursor: pointer; }
        header .title { flex: 1; font-weight: 600; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        main { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px; gap: 14px; }
        select, input { background: #111a2e; color: #f8fafc; border: 1px solid #334155; border-radius: 8px; padding: 8px; font-size: 15px; }
        .fight { width: 100%; max-width: 900px; }
        .fight select { width: 100%; }
        .names { display: flex; gap: 10px; width: 100%; max-width: 900px; }
        .names div { flex: 1; text-align: center; padding: 10px; border-radius: 10px; font-weight: 700; font-size: clamp(16px, 3.5vw, 30px); min-height: 1.5em; }
        .phase { font-size: clamp(18px, 4vw, 34px); font-weight: 700; letter-spacing: 1px; color: #93c5fd; text-transform: uppercase; }
        .phase.rest { color: #fcd34d; }
        .phase.done { color: #86efac; }
        .time { font-variant-numeric: tabular-nums; font-weight: 800; line-height: 1; font-size: clamp(90px, 26vw, 320px); }
        .time.warning { color: #fca5a5; }
        .bar { width: 100%; max-width: 900px; height: 10px; background: #1e293b; border-radius: 6px; overflow: hidden; }
        .bar div { height: 100%; width: 0; background: #3b82f6; transition: width .2s linear; }
        .bar.rest div { background: #f59e0b; }
        .controls { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; width: 100%; max-width: 900px; }
        .controls button { flex: 1 1 140px; border: 0; border-radius: 12px; padding: 16px 10px; font-size: clamp(16px, 2.6vw, 22px); font-weight: 700; color: #fff; cursor: pointer; }
        .start { background: #16a34a; flex-basis: 100% !important; font-size: clamp(22px, 5vw, 36px) !important; }
        .start.pause { background: #d97706; }
        .next { background: #2563eb; } .reset { background: #475569; } .stop { background: #b91c1c; }
        details { width: 100%; max-width: 900px; background: #111a2e; border-radius: 10px; padding: 10px 14px; }
        summary { cursor: pointer; font-weight: 600; }
        .settings { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px; }
        .settings label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #cbd5e1; }
        .settings .check { flex-direction: row; align-items: center; gap: 8px; }
        .hint { font-size: 12px; color: #94a3b8; text-align: center; max-width: 900px; }
    </style>
</head>
<body>
<header>
    <a href="{{ route('admin.competitions.show', [$competition, 'tab' => 'tirage']) }}">← Retour</a>
    <div class="title">Chrono · {{ $competition->nom }} · {{ $profile['label'] }}</div>
    <button type="button" id="fullscreen">⛶ Plein écran</button>
</header>
<main>
    <div class="fight">
        <select id="fight">
            <option value="">— Combat en cours (facultatif) —</option>
            @foreach($fights as $f)<option value="{{ $f['id'] }}" data-one="{{ $f['one'] }}" data-two="{{ $f['two'] }}" data-round="{{ $f['round'] }}">{{ $f['label'] }} · {{ $f['one'] }} / {{ $f['two'] }}</option>@endforeach
        </select>
    </div>
    <div class="names">
        <div data-c="nameOne" style="background: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</div>
        <div data-c="nameTwo" style="background: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</div>
    </div>

    <div class="phase" data-c="phase">Prêt</div>
    <div class="time" data-c="time">0:00</div>
    <div class="bar" data-c="bar"><div></div></div>

    <div class="controls">
        <button type="button" class="start" data-c="start">▶ Démarrer</button>
        <button type="button" class="next" data-c="next">⏭ Manche suivante</button>
        <button type="button" class="reset" data-c="reset">↺ Remettre à zéro</button>
        <button type="button" class="stop" data-c="stop">■ Fin du combat</button>
    </div>

    <details>
        <summary>⚙ Réglages du chrono</summary>
        <div class="settings">
            <label>Durée d'une manche (min:s)<input data-c="roundTime" inputmode="numeric" placeholder="2:00"></label>
            <label>Repos entre manches (min:s)<input data-c="restTime" inputmode="numeric" placeholder="1:00"></label>
            <label>Nombre de manches<input data-c="rounds" type="number" min="1" max="10"></label>
            <label class="check"><input type="checkbox" data-c="warn"> Bip 10 s avant la fin</label>
            <label class="check"><input type="checkbox" data-c="autoRest"> Repos lancé automatiquement</label>
            <button type="button" data-c="testBeep" style="background:#334155;color:#fff;border:0;border-radius:8px;padding:8px;">🔊 Tester le son</button>
        </div>
    </details>
    <p class="hint">Barre d'espace : démarrer / pause. Le son est autorisé au premier appui sur « Démarrer » (règle des navigateurs) : testez-le avant le premier combat et montez le volume. Gardez cette page au premier plan : l'écran reste allumé pendant le décompte, mais certains téléphones coupent le son d'une page passée en arrière-plan.</p>
</main>

@include('admin.competitions._chrono_engine')
<script>
(function () {
    var chrono = DojoChrono(document.body, {
        key: 'chrono.competition.{{ $competition->id }}',
        defaults: { round: {{ (int) $chrono['round'] }}, rest: {{ (int) $chrono['rest'] }}, rounds: {{ (int) $chrono['rounds'] }} },
        corners: [@js($profile['corners'][1]['name']), @js($profile['corners'][2]['name'])],
        spaceKey: true
    });
    document.getElementById('fight').addEventListener('change', function () {
        var o = this.selectedOptions[0];
        chrono.loadFight(o.value ? { id: o.value, one: o.dataset.one, two: o.dataset.two, round: o.dataset.round } : null);
    });
    document.getElementById('fullscreen').addEventListener('click', function () {
        if (document.fullscreenElement) { document.exitFullscreen(); } else if (document.documentElement.requestFullscreen) { document.documentElement.requestFullscreen(); }
    });
    window.__chrono = chrono;
})();
</script>
</body>
</html>
