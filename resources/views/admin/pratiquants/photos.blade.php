@extends('layouts.admin')

@section('actions')
    <a href="{{ route('admin.pratiquants.index', array_filter(['salle' => $salleId])) }}" class="btn btn-light shadow-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('messages.pratiquants.back') }}</a>
@endsection

@section('content')
{{-- Photos en série : la caméra reste ouverte, on photographie les élèves d'une salle l'un après l'autre. --}}
<style>
    .shoot { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; }
    @media (max-width: 992px) { .shoot { grid-template-columns: 1fr; } }
    .stage { position: relative; background: #0b1220; border-radius: 12px; overflow: hidden; aspect-ratio: 4 / 3; width: min(100%, calc(56vh * 4 / 3)); margin: 0 auto; display: flex; align-items: center; justify-content: center; }
    .stage video { width: 100%; height: 100%; object-fit: cover; }
    .stage .frame { position: absolute; height: 86%; aspect-ratio: 4 / 5; border: 3px solid rgba(255,255,255,.9); border-radius: 10px; box-shadow: 0 0 0 9999px rgba(0,0,0,.45); pointer-events: none; }
    .stage .flash { position: absolute; inset: 0; background: #fff; opacity: 0; pointer-events: none; transition: opacity .25s; }
    .stage .flash.on { opacity: .8; transition: none; }
    .stage .message { position: absolute; inset: auto 12px 12px 12px; color: #fff; background: rgba(0,0,0,.6); border-radius: 8px; padding: 8px 10px; font-size: 14px; }
    .who { font-size: 22px; font-weight: 800; }
    .people { max-height: 62vh; overflow-y: auto; }
    .people .item { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 8px; cursor: pointer; }
    .people .item:hover { background: #f1f5f9; }
    .people .item.current { background: #dbeafe; }
    .people img, .people .empty { width: 34px; height: 42px; object-fit: cover; border-radius: 5px; flex: none; }
    .people .empty { background: #f1f5f9; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 13px; }
    .preview { width: 96px; aspect-ratio: 4 / 5; object-fit: cover; border-radius: 8px; border: 2px solid #16a34a; }
</style>

<form method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <label class="fw-semibold">{{ __('messages.pratiquants.photos_salle') }}</label>
    <select name="salle" class="form-select w-auto" onchange="this.form.submit()">
        <option value="">—</option>
        @foreach($salles as $salle)<option value="{{ $salle->id }}" @selected($salleId === $salle->id)>{{ $salle->nom }}</option>@endforeach
    </select>
    @if($salleId)
        <label class="form-check-label ms-2"><input type="checkbox" class="form-check-input me-1" id="onlyMissing" checked> {{ __('messages.pratiquants.photos_only_missing') }}</label>
        <span class="badge text-bg-light border ms-auto" id="progress"></span>
    @endif
</form>

@if(!$salleId)
    <div class="alert alert-info">{{ __('messages.pratiquants.photos_choose_salle') }}</div>
@elseif($disciples->isEmpty())
    <div class="alert alert-warning">{{ __('messages.pratiquants.none') }}</div>
@else
<div class="shoot">
    <div>
        <div class="stage" id="stage">
            <video id="video" autoplay playsinline muted></video>
            <div class="frame"></div>
            <div class="flash" id="flash"></div>
            <div class="message d-none" id="cameraMessage">{{ __('messages.pratiquants.photos_no_camera') }}</div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
            <div class="flex-fill">
                <div class="who" id="who"></div>
                <div class="small text-muted" id="whoGrade"></div>
            </div>
            <img class="preview d-none" id="lastPreview" alt="">
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" class="btn btn-lg btn-success flex-fill" id="take"><i class="fas fa-camera me-1"></i> {{ __('messages.pratiquants.photos_take') }}</button>
            <button type="button" class="btn btn-lg btn-outline-secondary" id="prev"><i class="fas fa-backward-step"></i> {{ __('messages.pratiquants.photos_prev') }}</button>
            <button type="button" class="btn btn-lg btn-outline-secondary" id="skip">{{ __('messages.pratiquants.photos_skip') }} <i class="fas fa-forward-step"></i></button>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-outline-dark" id="switch"><i class="fas fa-camera-rotate me-1"></i> {{ __('messages.pratiquants.photos_switch') }}</button>
            <label class="btn btn-sm btn-outline-dark mb-0"><i class="fas fa-image me-1"></i> {{ __('messages.pratiquants.photos_file') }}<input type="file" accept="image/*" capture="environment" id="file" hidden></label>
        </div>
        <p class="small text-muted mt-2">{{ __('messages.pratiquants.photos_help') }}</p>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-2 people" id="people"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var people = @json($disciples->values());
    var uploadUrl = @js(route('admin.pratiquants.photo', ['disciple' => '__ID__']));
    var token = @js(csrf_token());
    var video = document.getElementById('video'), list = document.getElementById('people');
    var onlyMissing = document.getElementById('onlyMissing');
    var current = null, facing = 'environment', stream = null, busy = false;

    function toast(type, message) { if (window.dojoToast) { window.dojoToast(type, message); } }
    function queue() { return people.filter(function (p) { return !onlyMissing.checked || !p.photo || p.id === (current && current.id); }); }

    function renderList() {
        list.innerHTML = '';
        queue().forEach(function (p) {
            var row = document.createElement('div');
            row.className = 'item' + (current && current.id === p.id ? ' current' : '');
            row.innerHTML = (p.photo ? '<img src="' + p.photo + '" alt="">' : '<span class="empty"><i class="fas fa-camera"></i></span>')
                + '<div><div class="fw-semibold"></div><div class="small text-muted"></div></div>';
            row.querySelector('.fw-semibold').textContent = p.name;
            row.querySelector('.small').textContent = p.grade || '';
            row.addEventListener('click', function () { select(p); });
            list.appendChild(row);
            if (current && current.id === p.id) { row.scrollIntoView({ block: 'nearest' }); }
        });
        var done = people.filter(function (p) { return p.photo; }).length;
        document.getElementById('progress').textContent = @js(__('messages.pratiquants.photos_count', ['done' => '__D__', 'total' => '__T__'])).replace('__D__', done).replace('__T__', people.length);
    }

    function select(p) {
        current = p || null;
        document.getElementById('who').textContent = current ? current.name : @js(__('messages.pratiquants.photos_done'));
        document.getElementById('whoGrade').textContent = current ? (current.grade || '') : '';
        document.getElementById('take').disabled = !current;
        renderList();
    }

    function move(step) {
        var q = queue(), i = current ? q.findIndex(function (p) { return p.id === current.id; }) : -1;
        var next = q[i + step];
        // Après une photo, en mode « sans photo », la personne sort de la file : on prend la première restante.
        if (!next && step > 0) { next = q.find(function (p) { return !p.photo && p.id !== (current && current.id); }); }
        select(next || (step < 0 ? current : null));
    }

    // ---------- Caméra ----------
    function startCamera() {
        if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { noCamera(); return; }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 960 } }, audio: false })
            .then(function (s) { stream = s; video.srcObject = s; document.getElementById('cameraMessage').classList.add('d-none'); })
            .catch(noCamera);
    }
    function noCamera() { document.getElementById('cameraMessage').classList.remove('d-none'); }

    // Recadre au format carte (4:5, centré) et réduit à 600 × 750.
    function crop(source, width, height) {
        var ratio = 4 / 5, h = height * 0.86, w = h * ratio;
        if (w > width) { w = width; h = w / ratio; }
        var canvas = document.createElement('canvas');
        canvas.width = 600; canvas.height = 750;
        canvas.getContext('2d').drawImage(source, (width - w) / 2, (height - h) / 2, w, h, 0, 0, 600, 750);
        return canvas;
    }

    function upload(canvas) {
        if (!current || busy) { return; }
        busy = true;
        var person = current;
        document.getElementById('lastPreview').src = canvas.toDataURL('image/jpeg', 0.6);
        document.getElementById('lastPreview').classList.remove('d-none');
        canvas.toBlob(function (blob) {
            var data = new FormData();
            data.append('_token', token);
            data.append('photo', blob, 'photo-' + person.id + '.jpg');
            fetch(uploadUrl.replace('__ID__', person.id), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) { throw new Error(res.d.message || ''); }
                    person.photo = res.d.photo;
                    toast('success', @js(__('messages.pratiquants.photos_saved')) + ' : ' + person.name);
                    renderList();
                })
                .catch(function (e) { toast('error', @js(__('messages.pratiquants.photos_error')) + ' : ' + person.name + (e.message ? ' (' + e.message + ')' : '')); })
                .finally(function () { busy = false; });
            move(1); // on passe tout de suite au suivant, l'envoi se termine en arrière-plan
        }, 'image/jpeg', 0.88);
    }

    function take() {
        if (!current || !video.videoWidth) { if (!video.videoWidth) { document.getElementById('file').click(); } return; }
        var flash = document.getElementById('flash');
        flash.classList.add('on'); setTimeout(function () { flash.classList.remove('on'); }, 60);
        upload(crop(video, video.videoWidth, video.videoHeight));
    }

    document.getElementById('take').addEventListener('click', take);
    document.getElementById('skip').addEventListener('click', function () { move(1); });
    document.getElementById('prev').addEventListener('click', function () { move(-1); });
    document.getElementById('switch').addEventListener('click', function () { facing = facing === 'environment' ? 'user' : 'environment'; startCamera(); });
    onlyMissing.addEventListener('change', renderList);
    document.getElementById('file').addEventListener('change', function () {
        var file = this.files[0]; this.value = '';
        if (!file) { return; }
        var img = new Image();
        img.onload = function () { upload(crop(img, img.naturalWidth, img.naturalHeight)); URL.revokeObjectURL(img.src); };
        img.src = URL.createObjectURL(file);
    });
    document.addEventListener('keydown', function (e) {
        if (e.code === 'Space' && !/INPUT|SELECT|TEXTAREA|BUTTON/.test(document.activeElement.tagName)) { e.preventDefault(); take(); }
    });

    startCamera();
    select(queue().find(function (p) { return !p.photo; }) || queue()[0]);
});
</script>
@endif
@endsection
