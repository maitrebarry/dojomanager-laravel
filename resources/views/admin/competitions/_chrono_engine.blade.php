{{--
    Moteur du chrono d'arbitrage, partagé par la page Chrono et le panneau flottant de
    l'onglet Tirage. Il pilote les éléments [data-c="…"] d'un bloc : time, phase, bar,
    start, next, reset, stop, roundTime, restTime, rounds, warn, autoRest, testBeep,
    nameOne, nameTwo (tous facultatifs sauf time). Bips synthétisés et programmés sur
    l'horloge audio : ils sonnent à l'heure même si la page est ralentie.

    DojoChrono(root, { key, defaults: {round, rest, rounds}, corners: [nom1, nom2], spaceKey, onDone })
    renvoie { loadFight({id, one, two, round}), isRunning(), state() }.
--}}
<script>
window.DojoChrono = window.DojoChrono || function (root, config) {
    var el = function (name) { return root.querySelector('[data-c="' + name + '"]'); };
    var on = function (name, event, fn) { var e = el(name); if (e) { e.addEventListener(event, fn); } };
    var fmt = function (s) { s = Math.max(0, Math.ceil(s)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
    var parse = function (v, fallback) {
        v = String(v || '').trim();
        if (/^\d+[:.,]\d{1,2}$/.test(v)) { var p = v.split(/[:.,]/); return +p[0] * 60 + +p[1]; }
        if (/^\d+$/.test(v)) { return +v * (+v <= 10 ? 60 : 1); } // « 2 » = 2 min, « 90 » = 90 s
        return fallback;
    };

    // Durées de la compétition, puis réglages faits sur cet appareil, oubliés si les durées
    // de la compétition ont changé depuis (« base »).
    var defaults = Object.assign({ warn: true, autoRest: true }, config.defaults);
    var base = [defaults.round, defaults.rest, defaults.rounds].join('/');
    var settings = Object.assign({}, defaults);
    try {
        var saved = JSON.parse(localStorage.getItem(config.key)) || {};
        if (saved.base === base) { Object.assign(settings, saved); }
        else { settings.warn = saved.warn !== undefined ? saved.warn : true; settings.autoRest = saved.autoRest !== undefined ? saved.autoRest : true; }
    } catch (e) {}
    var competitionRound = settings.round;
    var fight = null; // combat chargé : {id, one, two, round}

    // ---------- Son ----------
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
        endRound: function (at) { tone(at, 880, 1.6); },                              // long bip : fin de manche
        endRest: function (at) { tone(at, 1046, 0.35); tone(at + 0.5, 1046, 0.35); },  // double bip : fin du repos
        warning: function (at) { tone(at, 660, 0.25); }                                // bip court : 10 s
    };

    // ---------- Décompte ----------
    var phase = 'ready', round = 1, running = false, remaining = settings.round, endAt = 0, wakeLock = null;

    function render() {
        var t = running ? (endAt - performance.now()) / 1000 : remaining;
        var time = el('time');
        time.textContent = fmt(t);
        time.classList.toggle('warning', phase === 'round' && t <= 10 && t > 0 && running);
        var label = { ready: 'Prêt — manche ' + round + ' / ' + settings.rounds, round: 'Manche ' + round + ' / ' + settings.rounds, rest: 'Repos', between: 'Manche ' + round + ' / ' + settings.rounds + ' — prêt', done: 'Combat terminé' }[phase];
        if (el('phase')) {
            el('phase').textContent = label + (!running && (phase === 'round' || phase === 'rest') ? ' — pause' : '');
            el('phase').classList.toggle('rest', phase === 'rest');
            el('phase').classList.toggle('done', phase === 'done');
        }
        if (el('bar')) {
            var length = phase === 'rest' ? settings.rest : settings.round;
            el('bar').classList.toggle('rest', phase === 'rest');
            el('bar').firstElementChild.style.width = (phase === 'round' || phase === 'rest') && length ? (100 - Math.max(0, t) / length * 100) + '%' : (phase === 'done' ? '100%' : '0');
        }
        if (el('start')) {
            el('start').textContent = running ? '⏸ Pause' : (phase === 'done' ? '▶ Nouveau combat' : '▶ Démarrer');
            el('start').classList.toggle('pause', running);
        }
        root.classList.toggle('chrono-running', running);
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
        running = false; cancelSounds(); keepAwake(false);
    }

    function finish() {
        cancelSounds(); running = false; keepAwake(false);
        phase = 'done'; remaining = 0;
        render();
        if (config.onDone) { config.onDone(fight); }
    }

    function startPause() {
        audioCtx();
        if (running) { pause(); }
        else if (phase === 'done') { reset(); }
        else {
            if (phase === 'ready' || phase === 'between') { phase = 'round'; remaining = settings.round; }
            run();
        }
        render();
    }

    // Fin du temps : on enchaîne selon la phase (le son a déjà été joué par l'horloge audio).
    function phaseEnded() {
        running = false; cancelSounds(); remaining = 0;
        if (phase === 'round') {
            if (round >= settings.rounds) { finish(); return; }
            if (settings.rest > 0) {
                phase = 'rest'; remaining = settings.rest;          // repos : tout de suite ou sur « Démarrer »
                if (settings.autoRest) { run(); } else { keepAwake(false); }
            } else { round++; phase = 'between'; remaining = settings.round; keepAwake(false); }
        } else if (phase === 'rest') {
            round++; phase = 'between'; remaining = settings.round; keepAwake(false); // l'arbitre relance la manche
        }
        render();
    }

    // « Manche suivante » : saute le temps restant (et le repos) jusqu'à la manche suivante.
    function goNext() {
        cancelSounds(); running = false; keepAwake(false);
        if (round >= settings.rounds && phase !== 'ready') { finish(); return; }
        round = Math.min(settings.rounds, round + 1); phase = 'between'; remaining = settings.round;
        render();
    }

    function reset() {
        cancelSounds(); running = false; keepAwake(false);
        phase = 'ready'; round = 1; remaining = settings.round;
        render();
    }

    async function keepAwake(wanted) {
        try {
            if (wanted && 'wakeLock' in navigator && !wakeLock) { wakeLock = await navigator.wakeLock.request('screen'); }
            if (!wanted && wakeLock) { await wakeLock.release(); wakeLock = null; }
        } catch (e) {}
    }
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && running) { wakeLock = null; keepAwake(true); }
    });

    function tick() { if (running && endAt - performance.now() <= 0) { phaseEnded(); } }
    (function loop() { tick(); render(); requestAnimationFrame(loop); })();
    setInterval(tick, 250); // onglet en arrière-plan

    // ---------- Réglages ----------
    function showSettings() {
        if (el('roundTime')) { el('roundTime').value = fmt(settings.round); }
        if (el('restTime')) { el('restTime').value = fmt(settings.rest); }
        if (el('rounds')) { el('rounds').value = settings.rounds; }
        if (el('warn')) { el('warn').checked = !!settings.warn; }
        if (el('autoRest')) { el('autoRest').checked = !!settings.autoRest; }
    }
    function saveSettings() {
        if (el('roundTime')) { settings.round = Math.max(5, parse(el('roundTime').value, settings.round)); }
        if (el('restTime')) { settings.rest = Math.max(0, parse(el('restTime').value, settings.rest)); }
        if (el('rounds')) { settings.rounds = Math.min(10, Math.max(1, parseInt(el('rounds').value, 10) || settings.rounds)); }
        if (el('warn')) { settings.warn = el('warn').checked; }
        if (el('autoRest')) { settings.autoRest = el('autoRest').checked; }
        competitionRound = settings.round;
        try { localStorage.setItem(config.key, JSON.stringify(Object.assign({ base: base }, settings))); } catch (e) {}
        if (!running && (phase === 'ready' || phase === 'between')) { remaining = settings.round; }
        render();
    }
    ['roundTime', 'restTime', 'rounds'].forEach(function (n) { on(n, 'change', function () { saveSettings(); showSettings(); }); });
    ['warn', 'autoRest'].forEach(function (n) { on(n, 'change', saveSettings); });

    // ---------- Boutons ----------
    on('start', 'click', startPause);
    on('next', 'click', goNext);
    on('reset', 'click', reset);
    on('stop', 'click', finish);
    on('testBeep', 'click', function () { sounds.endRound(audioCtx().currentTime + 0.05); });
    if (config.spaceKey) {
        document.addEventListener('keydown', function (e) {
            if (e.code === 'Space' && !/INPUT|SELECT|TEXTAREA|BUTTON/.test(document.activeElement.tagName)) { e.preventDefault(); startPause(); }
        });
    }

    showSettings();
    render();

    return {
        // Combat chargé : noms des combattants et durée de manche de sa catégorie (sans l'enregistrer).
        loadFight: function (f) {
            fight = f && f.id ? f : null;
            if (el('nameOne')) { el('nameOne').textContent = fight ? f.one : config.corners[0]; }
            if (el('nameTwo')) { el('nameTwo').textContent = fight ? f.two : config.corners[1]; }
            settings.round = fight && f.round ? parseInt(f.round, 10) : competitionRound;
            showSettings();
            reset();
        },
        fight: function () { return fight; },
        isRunning: function () { return running; },
        state: function () { return { phase: phase, round: round, running: running, remaining: running ? (endAt - performance.now()) / 1000 : remaining, settings: settings, scheduled: scheduled.length, fight: fight }; }
    };
};
</script>
