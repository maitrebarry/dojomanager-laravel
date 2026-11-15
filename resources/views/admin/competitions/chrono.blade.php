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
            @foreach($fights as $f)<option value="{{ $f['id'] }}" data-one="{{ $f['one'] }}" data-two="{{ $f['two'] }}">{{ $f['label'] }} · {{ $f['one'] }} / {{ $f['two'] }}</option>@endforeach
        </select>
    </div>
    <div class="names">
        <div id="nameOne" style="background: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</div>
        <div id="nameTwo" style="background: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</div>
    </div>

    <div class="phase" id="phase">Prêt</div>
    <div class="time" id="time">0:00</div>
    <div class="bar" id="bar"><div></div></div>

    <div class="controls">
        <button type="button" class="start" id="start">▶ Démarrer</button>
        <button type="button" class="next" id="next">⏭ Manche suivante</button>
        <button type="button" class="reset" id="reset">↺ Remettre à zéro</button>
        <button type="button" class="stop" id="stop">■ Fin du combat</button>
    </div>

    <details>
        <summary>⚙ Réglages du chrono</summary>
        <div class="settings">
            <label>Durée d'une manche (min:s)<input id="roundTime" inputmode="numeric" placeholder="2:00"></label>
            <label>Repos entre manches (min:s)<input id="restTime" inputmode="numeric" placeholder="1:00"></label>
            <label>Nombre de manches<input id="rounds" type="number" min="1" max="10"></label>
            <label class="check"><input type="checkbox" id="warn"> Bip 10 s avant la fin</label>
            <label class="check"><input type="checkbox" id="autoRest"> Repos lancé automatiquement</label>
            <button type="button" id="testBeep" style="background:#334155;color:#fff;border:0;border-radius:8px;padding:8px;">🔊 Tester le son</button>
        </div>
    </details>
    <p class="hint">Barre d'espace : démarrer / pause. Le son est autorisé au premier appui sur « Démarrer » (règle des navigateurs) : testez-le avant le premier combat et montez le volume. Gardez cette page au premier plan : l'écran reste allumé pendant le décompte, mais certains téléphones coupent le son d'une page passée en arrière-plan.</p>
</main>

<script>
(function () {
    // Réglages par défaut de la discipline, puis ceux enregistrés sur cet appareil.
    var KEY = 'chrono.{{ $profile['key'] }}';
    var defaults = { round: {{ (int) $profile['chrono']['round'] }}, rest: {{ (int) $profile['chrono']['rest'] }}, rounds: {{ (int) $profile['chrono']['rounds'] }}, warn: true, autoRest: true };
    var settings = Object.assign({}, defaults);
    try { Object.assign(settings, JSON.parse(localStorage.getItem(KEY)) || {}); } catch (e) {}

    var $ = function (id) { return document.getElementById(id); };
    var fmt = function (s) { s = Math.max(0, Math.ceil(s)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
    var parse = function (v, fallback) {
        v = String(v || '').trim();
        if (/^\d+:\d{1,2}$/.test(v)) { var p = v.split(':'); return +p[0] * 60 + +p[1]; }
        if (/^\d+$/.test(v)) { return +v * (+v <= 10 ? 60 : 1); } // « 2 » = 2 min, « 90 » = 90 s
        return fallback;
    };

    // ---------- Son : bips synthétisés (aucun fichier), programmés sur l'horloge audio ----------
    var audio = null, scheduled = [];
    function audioCtx() {
        if (!audio) { audio = new (window.AudioContext || window.webkitAudioContext)(); }
        if (audio.state === 'suspended') { audio.resume(); }
        return audio;
    }
    function tone(at, freq, duration) {
        var ctx = audioCtx(), osc = ctx.createOscillator(), gain = ctx.createGain();
        osc.type = 'square'; osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.0001, at);
        gain.gain.exponentialRampToValueAtTime(0.5, at + 0.02);
        gain.gain.setValueAtTime(0.5, at + duration - 0.05);
        gain.gain.exponentialRampToValueAtTime(0.0001, at + duration);
        osc.connect(gain).connect(ctx.destination);
        osc.start(at); osc.stop(at + duration + 0.05);
        scheduled.push(osc);
    }
    function cancelSounds() { scheduled.forEach(function (o) { try { o.stop(); } catch (e) {} }); scheduled = []; }
    var sounds = {
        endRound: function (at) { tone(at, 880, 1.6); },                                    // long bip : fin de manche
        endRest: function (at) { tone(at, 1046, 0.35); tone(at + 0.5, 1046, 0.35); },        // double bip : fin du repos
        warning: function (at) { tone(at, 660, 0.25); }                                      // bip court : 10 s
    };

    // ---------- Chrono ----------
    var phase = 'ready', round = 1, running = false, remaining = settings.round, endAt = 0, wakeLock = null;

    function phaseLength() { return phase === 'rest' ? settings.rest : settings.round; }

    function render() {
        var t = running ? (endAt - performance.now()) / 1000 : remaining;
        $('time').textContent = fmt(t);
        $('time').className = 'time' + (phase === 'round' && t <= 10 && t > 0 && running ? ' warning' : '');
        var label = { ready: 'Prêt — manche ' + round + ' / ' + settings.rounds, round: 'Manche ' + round + ' / ' + settings.rounds, rest: 'Repos', between: 'Manche ' + round + ' / ' + settings.rounds + ' — prêt', done: 'Combat terminé' }[phase];
        $('phase').textContent = label + (!running && (phase === 'round' || phase === 'rest') ? ' — pause' : '');
        $('phase').className = 'phase' + (phase === 'rest' ? ' rest' : '') + (phase === 'done' ? ' done' : '');
        $('bar').className = 'bar' + (phase === 'rest' ? ' rest' : '');
        var length = phaseLength();
        $('bar').firstElementChild.style.width = (phase === 'round' || phase === 'rest') && length ? (100 - Math.max(0, t) / length * 100) + '%' : (phase === 'done' ? '100%' : '0');
        $('start').textContent = running ? '⏸ Pause' : (phase === 'done' ? '▶ Nouveau combat' : '▶ Démarrer');
        $('start').className = 'start' + (running ? ' pause' : '');
    }

    function run() {
        var ctx = audioCtx(), now = ctx.currentTime;
        endAt = performance.now() + remaining * 1000;
        running = true;
        if (phase === 'round') {
            if (settings.warn && remaining > 10.5) { sounds.warning(now + remaining - 10); }
            sounds.endRound(now + remaining);
        } else if (phase === 'rest') {
            sounds.endRest(now + remaining);
        }
        keepAwake(true);
    }

    function pause() {
        remaining = Math.max(0, (endAt - performance.now()) / 1000);
        running = false;
        cancelSounds();
        keepAwake(false);
    }

    function startPause() {
        audioCtx();
        if (running) { pause(); }
        else if (phase === 'done') { reset(); }
        else {
            if (phase === 'ready' || phase === 'between') { phase = 'round'; remaining = settings.round; }
            run(); // reprise d'une manche ou d'un repos en pause
        }
        render();
    }

    // Fin du temps : on enchaîne selon la phase (le son a déjà été joué par l'horloge audio).
    function phaseEnded() {
        running = false; cancelSounds(); remaining = 0;
        if (phase === 'round') {
            if (round >= settings.rounds) { phase = 'done'; keepAwake(false); }
            else if (settings.rest > 0) {
                // Repos : lancé tout de suite, ou en attente de « Démarrer ».
                phase = 'rest'; remaining = settings.rest;
                if (settings.autoRest) { run(); } else { keepAwake(false); }
            } else { round++; phase = 'between'; remaining = settings.round; keepAwake(false); }
        } else if (phase === 'rest') {
            // Fin du repos : la manche suivante attend que l'arbitre la relance.
            round++; phase = 'between'; remaining = settings.round; keepAwake(false);
        }
        render();
    }

    // « Manche suivante » : saute le temps restant (et le repos) jusqu'à la manche suivante.
    function goNext() {
        cancelSounds(); running = false; keepAwake(false);
        if (round >= settings.rounds && phase !== 'ready') { phase = 'done'; remaining = 0; }
        else { round = Math.min(settings.rounds, round + 1); phase = 'between'; remaining = settings.round; }
        render();
    }

    function reset() {
        cancelSounds(); running = false; keepAwake(false);
        phase = 'ready'; round = 1; remaining = settings.round;
        render();
    }

    function stopFight() {
        cancelSounds(); running = false; keepAwake(false);
        phase = 'done'; remaining = 0;
        render();
    }

    // Écran allumé pendant le décompte (téléphones).
    async function keepAwake(on) {
        try {
            if (on && 'wakeLock' in navigator && !wakeLock) { wakeLock = await navigator.wakeLock.request('screen'); }
            if (!on && wakeLock) { await wakeLock.release(); wakeLock = null; }
        } catch (e) {}
    }
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && running) { wakeLock = null; keepAwake(true); }
    });

    function loop() {
        if (running && endAt - performance.now() <= 0) { phaseEnded(); }
        render();
        requestAnimationFrame(loop);
    }
    setInterval(function () { if (running && endAt - performance.now() <= 0) { phaseEnded(); } }, 250); // onglet en arrière-plan

    // ---------- Réglages ----------
    function showSettings() {
        $('roundTime').value = fmt(settings.round); $('restTime').value = fmt(settings.rest);
        $('rounds').value = settings.rounds; $('warn').checked = !!settings.warn; $('autoRest').checked = !!settings.autoRest;
    }
    function saveSettings() {
        settings.round = Math.max(5, parse($('roundTime').value, settings.round));
        settings.rest = Math.max(0, parse($('restTime').value, settings.rest));
        settings.rounds = Math.min(10, Math.max(1, parseInt($('rounds').value, 10) || settings.rounds));
        settings.warn = $('warn').checked; settings.autoRest = $('autoRest').checked;
        try { localStorage.setItem(KEY, JSON.stringify(settings)); } catch (e) {}
        if (!running && (phase === 'ready' || phase === 'between')) { remaining = settings.round; }
        render();
    }
    ['roundTime', 'restTime', 'rounds'].forEach(function (id) { $(id).addEventListener('change', function () { saveSettings(); showSettings(); }); });
    ['warn', 'autoRest'].forEach(function (id) { $(id).addEventListener('change', saveSettings); });

    // ---------- Boutons ----------
    $('start').addEventListener('click', startPause);
    $('next').addEventListener('click', goNext);
    $('reset').addEventListener('click', reset);
    $('stop').addEventListener('click', stopFight);
    $('testBeep').addEventListener('click', function () { sounds.endRound(audioCtx().currentTime + 0.05); });
    $('fullscreen').addEventListener('click', function () {
        if (document.fullscreenElement) { document.exitFullscreen(); } else if (document.documentElement.requestFullscreen) { document.documentElement.requestFullscreen(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.code === 'Space' && !/INPUT|SELECT|TEXTAREA/.test(document.activeElement.tagName)) { e.preventDefault(); startPause(); }
    });
    $('fight').addEventListener('change', function () {
        var o = this.selectedOptions[0];
        $('nameOne').textContent = o.dataset.one || @js($profile['corners'][1]['name']);
        $('nameTwo').textContent = o.dataset.two || @js($profile['corners'][2]['name']);
        reset();
    });

    window.__chrono = { state: function () { return { phase: phase, round: round, running: running, remaining: running ? (endAt - performance.now()) / 1000 : remaining, settings: settings, scheduled: scheduled.length }; } };
    showSettings();
    render();
    requestAnimationFrame(loop);
})();
</script>
</body>
</html>
