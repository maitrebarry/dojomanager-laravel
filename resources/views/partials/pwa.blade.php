<div id="dojoInstall" class="dojo-install" role="dialog" aria-label="Installer l'application">
    <img src="{{ asset('images/icons/dojo-192.png') }}" alt="" class="dojo-install__ico">
    <div class="dojo-install__txt">
        <b>Installer DojoManager</b>
        <span>Accès rapide, plein écran, hors-ligne</span>
    </div>
    <button type="button" id="dojoInstallBtn" class="dojo-install__go">
        <i class="fas fa-download"></i>
        <span>Installer</span>
    </button>
    <button type="button" id="dojoInstallX" class="dojo-install__x" aria-label="Fermer">&times;</button>
</div>

<style>
    .dojo-install {
        position: fixed;
        left: 12px;
        right: 12px;
        bottom: 82px;
        z-index: 1300;
        display: none;
        align-items: center;
        gap: 12px;
        max-width: 460px;
        margin: 0 auto;
        padding: 11px 13px;
        background: var(--card-bg, #fff);
        color: var(--body-text, #1f2937);
        border: 1px solid var(--card-border, rgba(15,15,26,.12));
        border-radius: 16px;
        box-shadow: 0 18px 44px -14px rgba(0,0,0,.34);
    }
    .dojo-install.is-shown { display: flex; }
    .dojo-install__ico { width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; }
    .dojo-install__txt { flex: 1; min-width: 0; line-height: 1.3; }
    .dojo-install__txt b { display: block; font-size: .92rem; font-weight: 800; }
    .dojo-install__txt span { color: #6b7280; font-size: .78rem; }
    .dojo-install__go {
        display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
        padding: 9px 14px; border: 0; border-radius: 999px;
        background: var(--primary-color, #4060a0); color: #fff; font-size: .84rem; font-weight: 800; cursor: pointer;
    }
    .dojo-install__go:hover { filter: brightness(1.1); }
    .dojo-install__x {
        flex-shrink: 0; padding: 0 4px; border: 0; background: transparent;
        color: #9aa3b2; font-size: 1.5rem; line-height: 1; cursor: pointer;
    }
    @media (min-width: 992px) {
        .dojo-install { left: auto; right: 20px; bottom: 20px; width: 390px; }
    }
</style>

<script>
    (function () {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
            });
        }

        var banner = document.getElementById('dojoInstall');
        if (!banner) { return; }

        var goBtn = document.getElementById('dojoInstallBtn');
        var xBtn = document.getElementById('dojoInstallX');
        var titleEl = banner.querySelector('.dojo-install__txt b');
        var subEl = banner.querySelector('.dojo-install__txt span');
        var goLabel = goBtn ? goBtn.querySelector('span') : null;
        var deferred = null;
        var KEY = 'dojo-install-dismissed';
        var isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
        var ua = navigator.userAgent || '';
        var isiOS = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/.test(ua) && (navigator.maxTouchPoints || 0) > 1);

        function isDismissed() {
            try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
        }
        function show() {
            if (!isStandalone) { banner.classList.add('is-shown'); }
        }
        function hide() {
            banner.classList.remove('is-shown');
        }
        function remember() {
            try { localStorage.setItem(KEY, '1'); } catch (e) {}
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferred = e;
            if (!isDismissed()) { show(); }
        });

        window.addEventListener('appinstalled', function () {
            hide();
            remember();
            deferred = null;
        });

        if (isiOS && !isStandalone) {
            if (titleEl) { titleEl.textContent = "Ajouter DojoManager à l'accueil"; }
            if (subEl) { subEl.textContent = "Safari : Partager puis Sur l'écran d'accueil"; }
            if (goLabel) { goLabel.textContent = "Comment faire"; }
        }

        if (!isStandalone && !isDismissed()) {
            window.setTimeout(show, 2500);
        }

        function iosHelp() {
            alert("Installer DojoManager sur iPhone / iPad :\n\n1) Ouvrez le site dans Safari\n2) Touchez l'icône Partager\n3) Faites défiler puis choisissez Sur l'écran d'accueil\n4) Touchez Ajouter");
        }

        function androidHelp() {
            alert("Installer DojoManager :\n\n1) Ouvrez le menu de votre navigateur\n2) Touchez Installer l'application\n\nSi l'option n'apparaît pas, naviguez quelques pages puis rouvrez le menu.");
        }

        function doInstall() {
            if (deferred) {
                deferred.prompt();
                deferred.userChoice.then(function (choice) {
                    if (choice && choice.outcome === 'accepted') { remember(); }
                    deferred = null;
                    hide();
                });
            } else if (isiOS) {
                iosHelp();
            } else {
                androidHelp();
            }
        }

        if (goBtn) { goBtn.addEventListener('click', doInstall); }
        if (xBtn) {
            xBtn.addEventListener('click', function () {
                hide();
                remember();
            });
        }

        window.dojoInstall = function () {
            if (isStandalone) {
                alert("DojoManager est déjà installée sur cet appareil.");
                return;
            }
            doInstall();
        };
    })();
</script>
