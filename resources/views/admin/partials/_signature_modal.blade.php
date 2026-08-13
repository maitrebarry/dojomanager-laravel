{{-- Modal de signature électronique (port de Disciples.jsx : canvas → data URL → /signatures).
     La signature est enregistrée par utilisateur et réutilisée sur les cartes de licence. --}}
@php($__signer = Auth::user()->licenceMeta()['signer'])

<div class="modal fade" id="signatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--navbar-bg);">
                <h5 class="modal-title"><i class="fas fa-signature me-2"></i> {{ __('messages.licences.signature_title') }} — {{ $__signer }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">{{ __('messages.licences.signature_hint') }}</p>
                <div class="border rounded bg-white" style="touch-action: none;">
                    <canvas id="sigCanvas" width="500" height="180" style="width: 100%; height: 180px; cursor: crosshair;"></canvas>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="sigClear"><i class="fas fa-eraser me-1"></i> {{ __('messages.licences.clear') }}</button>
                <button type="button" class="btn text-white" style="background-color: var(--navbar-bg);" id="sigSave"><i class="fas fa-save me-1"></i> {{ __('messages.licences.save') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('sigCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var drawing = false;
    var statusEl = document.getElementById('sigStatus');
    var curUrl = "{{ route('admin.licences.signature') }}";
    var saveUrl = "{{ route('admin.licences.signature.save') }}";
    var token = "{{ csrf_token() }}";

    function setStatus(has) {
        if (!statusEl) return;
        statusEl.className = 'badge ' + (has ? 'bg-success' : 'bg-warning text-dark');
        statusEl.innerHTML = has
            ? '✓ {{ __('messages.licences.signature_present') }}'
            : '⚠️ {{ __('messages.licences.signature_missing') }}';
    }

    // Chargement de la signature courante
    fetch(curUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { setStatus(!!(d && d.signature_data)); })
        .catch(function () { setStatus(false); });

    // Dessin (pointer events)
    function point(e) {
        var r = canvas.getBoundingClientRect();
        return { x: (e.clientX - r.left) * (canvas.width / r.width), y: (e.clientY - r.top) * (canvas.height / r.height) };
    }
    canvas.addEventListener('pointerdown', function (e) {
        e.preventDefault(); drawing = true;
        ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#1a237e';
        var p = point(e); ctx.beginPath(); ctx.moveTo(p.x, p.y);
        if (canvas.setPointerCapture) canvas.setPointerCapture(e.pointerId);
    });
    canvas.addEventListener('pointermove', function (e) {
        if (!drawing) return; e.preventDefault();
        var p = point(e); ctx.lineTo(p.x, p.y); ctx.stroke(); ctx.beginPath(); ctx.moveTo(p.x, p.y);
    });
    function stop() { drawing = false; }
    canvas.addEventListener('pointerup', stop);
    canvas.addEventListener('pointerleave', stop);

    document.getElementById('sigClear').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    document.getElementById('sigSave').addEventListener('click', function () {
        var blank = document.createElement('canvas'); blank.width = canvas.width; blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) {
            if (window.dojoToast) { dojoToast('warning', '{{ __('messages.licences.signature_required') }}'); }
            return;
        }
        var data = canvas.toDataURL('image/png');
        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ signature_data: data })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.ok) {
                setStatus(true);
                if (window.dojoToast) { dojoToast('success', '{{ __('messages.licences.signature_saved') }}'); }
                var m = bootstrap.Modal.getInstance(document.getElementById('signatureModal'));
                if (m) m.hide();
            } else if (window.dojoToast) {
                dojoToast('error', '{{ __('messages.licences.signature_error') }}');
            }
        })
        .catch(function () { if (window.dojoToast) { dojoToast('error', '{{ __('messages.licences.signature_error') }}'); } });
    });
});
</script>
