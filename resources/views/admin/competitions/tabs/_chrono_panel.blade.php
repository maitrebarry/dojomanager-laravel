{{--
    Chrono flottant de l'onglet Tirage : il reste à l'écran pendant qu'on fait défiler les
    tableaux et qu'on saisit les scores (contrairement à une fenêtre modale, il ne bloque
    pas la page). Bouton ⏱ d'un combat : charge ses combattants et sa durée de manche.
--}}
@php $chrono = $competition->chronoSettings(); @endphp
<style>
    .chrono-panel { position: fixed; right: 16px; bottom: 16px; width: 330px; z-index: 1050; background: #0b1220; color: #f8fafc; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,.35); padding: 10px 12px; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
    .chrono-panel .cp-top { display: flex; align-items: center; gap: 6px; }
    .chrono-panel .cp-title { font-weight: 700; font-size: 14px; }
    .chrono-panel .cp-phase { flex: 1; font-size: 12px; font-weight: 700; color: #93c5fd; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chrono-panel .cp-phase.rest { color: #fcd34d; } .chrono-panel .cp-phase.done { color: #86efac; }
    .chrono-panel .cp-icon { background: none; border: 1px solid #334155; color: #cbd5e1; border-radius: 6px; padding: 0 7px; font-size: 13px; line-height: 22px; cursor: pointer; text-decoration: none; }
    .chrono-panel .cp-names { display: flex; gap: 6px; margin-top: 8px; }
    .chrono-panel .cp-names div { flex: 1; text-align: center; border-radius: 8px; padding: 4px; font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chrono-panel .cp-time { font-variant-numeric: tabular-nums; font-weight: 800; font-size: 64px; line-height: 1.05; text-align: center; }
    .chrono-panel .cp-time.warning { color: #fca5a5; }
    .chrono-panel .cp-bar { height: 6px; background: #1e293b; border-radius: 4px; overflow: hidden; }
    .chrono-panel .cp-bar div { height: 100%; width: 0; background: #3b82f6; }
    .chrono-panel .cp-bar.rest div { background: #f59e0b; }
    .chrono-panel .cp-buttons { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 8px; }
    .chrono-panel .cp-buttons button { border: 0; border-radius: 8px; padding: 8px 4px; font-weight: 700; color: #fff; font-size: 13px; cursor: pointer; }
    .chrono-panel .cp-start { grid-column: 1 / -1; background: #16a34a; font-size: 18px !important; }
    .chrono-panel .cp-start.pause { background: #d97706; }
    .chrono-panel .cp-silence { grid-column: 1 / -1; background: #dc2626; font-size: 18px !important; animation: cp-ring 0.6s ease-in-out infinite alternate; }
    .chrono-panel .cp-silence[hidden] { display: none; }
    .chrono-panel.chrono-ringing { box-shadow: 0 0 0 4px #dc2626, 0 10px 30px rgba(0,0,0,.35); }
    @keyframes cp-ring { from { box-shadow: 0 0 0 0 rgba(220,38,38,.8); } to { box-shadow: 0 0 0 10px rgba(220,38,38,0); } }
    .chrono-panel .cp-next { background: #2563eb; } .chrono-panel .cp-reset { background: #475569; } .chrono-panel .cp-stop { background: #b91c1c; }
    .chrono-panel details { margin-top: 8px; font-size: 12px; color: #cbd5e1; }
    .chrono-panel details input[type=text], .chrono-panel details input[type=number] { width: 64px; background: #111a2e; color: #fff; border: 1px solid #334155; border-radius: 6px; padding: 2px 4px; }
    .chrono-panel details label { display: inline-flex; align-items: center; gap: 4px; margin: 3px 6px 3px 0; }
    /* Réduit : le temps et Démarrer/Pause seulement. */
    .chrono-panel.minimized .cp-names, .chrono-panel.minimized .cp-bar, .chrono-panel.minimized details, .chrono-panel.minimized .cp-next, .chrono-panel.minimized .cp-reset, .chrono-panel.minimized .cp-stop { display: none; }
    .chrono-panel.minimized .cp-time { font-size: 34px; }
    .chrono-panel.minimized .cp-buttons { margin-top: 4px; }
    .chrono-panel.minimized .cp-start { font-size: 14px !important; padding: 5px; }
    .chrono-score-target { box-shadow: 0 0 0 4px #f59e0b !important; }
    @media (max-width: 576px) { .chrono-panel { left: 8px; right: 8px; bottom: 8px; width: auto; } }
</style>

<div class="chrono-panel minimized d-none" id="chronoPanel">
    <div class="cp-top">
        <span class="cp-title">⏱</span>
        <span class="cp-phase" data-c="phase">Prêt</span>
        <button type="button" class="cp-icon" data-panel="size" title="{{ __('messages.competitions.chrono_toggle') }}">▢</button>
        <a class="cp-icon" target="_blank" href="{{ route('admin.competitions.chrono', $competition) }}" title="{{ __('messages.competitions.chrono_big') }}">⤢</a>
        <button type="button" class="cp-icon" data-panel="close" title="{{ __('messages.competitions.chrono_close') }}">✕</button>
    </div>
    <div class="cp-names">
        <div data-c="nameOne" style="background: {{ $profile['corners'][1]['color'] }};">{{ $profile['corners'][1]['name'] }}</div>
        <div data-c="nameTwo" style="background: {{ $profile['corners'][2]['color'] }};">{{ $profile['corners'][2]['name'] }}</div>
    </div>
    <div class="cp-time" data-c="time">0:00</div>
    <div class="cp-bar" data-c="bar"><div></div></div>
    <div class="cp-buttons">
        <button type="button" class="cp-silence" data-c="silence" hidden>🔕 Arrêter la sonnerie</button>
        <button type="button" class="cp-start" data-c="start">▶ Démarrer</button>
        <button type="button" class="cp-next" data-c="next" title="Manche suivante">⏭ Manche</button>
        <button type="button" class="cp-reset" data-c="reset" title="Remettre à zéro">↺ Zéro</button>
        <button type="button" class="cp-stop" data-c="stop" title="Fin du combat">■ Fin</button>
    </div>
    <details>
        <summary>⚙ {{ __('messages.competitions.chrono_settings') }}</summary>
        <label>Manche <input type="text" data-c="roundTime" inputmode="numeric"></label>
        <label>Repos <input type="text" data-c="restTime" inputmode="numeric"></label>
        <label>Manches <input type="number" data-c="rounds" min="1" max="10"></label><br>
        <label><input type="checkbox" data-c="warn"> Bip 10 s avant la fin</label>
        <label><input type="checkbox" data-c="autoRest"> Repos automatique</label>
        <button type="button" class="cp-icon" data-c="testBeep">🔊 Tester le son</button>
    </details>
</div>

@include('admin.competitions._chrono_engine')
<script>
    (function () {
        var panel = document.getElementById('chronoPanel');
        var OPEN = 'tirage.chrono.' + @json($competition->id);

        window.dojoTirageChrono = DojoChrono(panel, {
            key: 'chrono.competition.' + @json($competition->id),
            defaults: { round: {{ (int) $chrono['round'] }}, rest: {{ (int) $chrono['rest'] }}, rounds: {{ (int) $chrono['rounds'] }} },
            corners: [@js($profile['corners'][1]['name']), @js($profile['corners'][2]['name'])],
            spaceKey: false,
            // Fin du combat : le curseur va dans la case de score du combat chronométré.
            onDone: function (fight) {
                if (!fight) { return; }
                var input = document.querySelector('.js-score[data-combat="' + fight.id + '"]');
                if (!input) { return; }
                var card = input.closest('.js-category-card');
                if (card && card.classList.contains('d-none')) { card.classList.remove('d-none'); }
                input.scrollIntoView({ block: 'center', behavior: 'smooth' });
                input.focus();
                input.classList.add('chrono-score-target');
                setTimeout(function () { input.classList.remove('chrono-score-target'); }, 6000);
                if (window.dojoToast) { window.dojoToast('info', @js(__('messages.competitions.chrono_enter_score'))); }
            }
        });

        function show(state) {
            panel.classList.toggle('d-none', state === 'closed');
            panel.classList.toggle('minimized', state === 'min');
            try { localStorage.setItem(OPEN, state); } catch (e) {}
        }
        try { var saved = localStorage.getItem(OPEN); if (saved && saved !== 'closed') { show(saved); } } catch (e) {}

        panel.querySelector('[data-panel="size"]').addEventListener('click', function () { show(panel.classList.contains('minimized') ? 'open' : 'min'); });
        panel.querySelector('[data-panel="close"]').addEventListener('click', function () {
            var chrono = window.dojoTirageChrono;
            if (chrono.isRunning() && !confirm(@js(__('messages.competitions.chrono_close_running')))) { return; }
            if (chrono.isRunning() || chrono.isRinging()) { chrono.loadFight(chrono.fight()); } // arrête décompte et sonnerie
            show('closed');
        });
        document.querySelectorAll('.js-chrono-open').forEach(function (b) { b.addEventListener('click', function () { show('open'); }); });

        // Bouton ⏱ d'un combat (délégation : les cartes sont remplacées après chaque résultat).
        document.addEventListener('click', function (event) {
            var button = event.target.closest && event.target.closest('.js-chrono-load');
            if (!button) { return; }
            if (window.dojoTirageChrono.isRunning() && !confirm(@js(__('messages.competitions.chrono_replace_running')))) { return; }
            window.dojoTirageChrono.loadFight({ id: button.dataset.combat, one: button.dataset.one, two: button.dataset.two, round: button.dataset.round });
            show('open');
        });
    })();
</script>
