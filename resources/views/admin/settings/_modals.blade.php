{{-- Modals CRUD du hub Paramètres. En-tête = couleur du header (navbar). --}}

{{-- ===== FÉDÉRATION ===== --}}
<div class="modal fade" id="m-fed" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content js-modal-form">
            @csrf <input type="hidden" name="_method" value="POST">
            <div class="modal-header dojo-modal-header">
                <h5 class="modal-title js-modal-title" data-create="{{ __('messages.federations.add') }}" data-edit="{{ __('messages.federations.edit') }}">{{ __('messages.federations.add') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-8"><label class="form-label">{{ __('messages.federations.name') }} <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">{{ __('messages.federations.acronym') }}</label><input type="text" name="sigle" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.phone') }}</label><input type="text" name="telephone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.email') }}</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-12"><label class="form-label">{{ __('messages.address') }}</label><input type="text" name="adresse" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>
</div>

{{-- ===== LIGUE ===== --}}
<div class="modal fade" id="m-ligue" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content js-modal-form">
            @csrf <input type="hidden" name="_method" value="POST">
            <div class="modal-header dojo-modal-header">
                <h5 class="modal-title js-modal-title" data-create="{{ __('messages.ligues.add') }}" data-edit="{{ __('messages.ligues.edit') }}">{{ __('messages.ligues.add') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">{{ __('messages.ligues.name') }} <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.ligues.region') }}</label><input type="text" name="region" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label">{{ __('messages.ligues.federation') }} <span class="text-danger">*</span></label>
                        <select name="federation_id" class="form-select" required><option value="">-</option>@foreach($federations as $f)<option value="{{ $f->id }}">{{ $f->nom }}</option>@endforeach</select></div>
                    <div class="col-12"><div class="form-check form-switch mt-2"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1"><label class="form-check-label">{{ __('messages.active') }}</label></div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>
</div>

{{-- ===== SALLE ===== --}}
<div class="modal fade" id="m-salle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content js-modal-form">
            @csrf <input type="hidden" name="_method" value="POST">
            <div class="modal-header dojo-modal-header">
                <h5 class="modal-title js-modal-title" data-create="{{ __('messages.salles.add') }}" data-edit="{{ __('messages.salles.edit') }}">{{ __('messages.salles.add') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">{{ __('messages.salles.name') }} <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.phone') }}</label><input type="text" name="telephone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.salles.ligue') }} <span class="text-danger">*</span></label>
                        <select name="ligue_id" class="form-select" required><option value="">-</option>@foreach($ligues as $l)<option value="{{ $l->id }}">{{ $l->nom }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.salles.maitre') }}</label>
                        <select name="maitre_id" class="form-select"><option value="">{{ __('messages.salles.no_maitre') }}</option>@foreach($maitres as $m)<option value="{{ $m->id }}">{{ $m->nom_complet }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.salles.monthly_fee') }}</label><input type="number" step="0.01" min="0" name="mensualite" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.address') }}</label><input type="text" name="adresse" class="form-control"></div>
                    <div class="col-12"><div class="form-check form-switch mt-2"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1"><label class="form-check-label">{{ __('messages.active') }}</label></div></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>
</div>

{{-- ===== GRADE ===== --}}
<div class="modal fade" id="m-grade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content js-modal-form">
            @csrf <input type="hidden" name="_method" value="POST">
            <div class="modal-header dojo-modal-header">
                <h5 class="modal-title js-modal-title" data-create="{{ __('messages.grades.add') }}" data-edit="{{ __('messages.grades.edit') }}">{{ __('messages.grades.add') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label">{{ __('messages.grades.level') }} <span class="text-danger">*</span></label><input type="number" min="0" name="niveau" class="form-control" required></div>
                    <div class="col-md-5"><label class="form-label">{{ __('messages.grades.name') }} <span class="text-danger">*</span></label><input type="text" name="nom_grade" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">{{ __('messages.grades.belt') }} <span class="text-danger">*</span></label><input type="text" name="ceinture" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.grades.type') }} <span class="text-danger">*</span></label>
                        <select name="type_grade" class="form-select" required><option value="KEUP">KEUP</option><option value="DAN">DAN</option></select></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.grades.federation') }} <span class="text-danger">*</span></label>
                        <select name="federation_id" class="form-select" required><option value="">-</option>@foreach($federations as $f)<option value="{{ $f->id }}">{{ $f->nom }}</option>@endforeach</select></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>
</div>

{{-- ===== PERMISSION ===== --}}
<div class="modal fade" id="m-permission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content js-modal-form">
            @csrf <input type="hidden" name="_method" value="POST">
            <div class="modal-header dojo-modal-header">
                <h5 class="modal-title js-modal-title" data-create="{{ __('messages.parametres.add_permission') }}" data-edit="{{ __('messages.parametres.edit_permission') }}">{{ __('messages.parametres.add_permission') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12"><label class="form-label">{{ __('messages.parametres.permission_name') }} <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">{{ __('messages.parametres.permission_module') }} <span class="text-danger">*</span></label><input type="text" name="module" class="form-control" required></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>
</div>

{{-- ===== UTILISATEUR ===== --}}
<div class="modal fade" id="m-user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content js-modal-form">
            @csrf <input type="hidden" name="_method" value="POST"> <input type="hidden" name="back_to_settings" value="1">
            <div class="modal-header dojo-modal-header">
                <h5 class="modal-title js-modal-title" data-create="{{ __('messages.parametres.add_user') }}" data-edit="{{ __('messages.parametres.edit_user') }}">{{ __('messages.parametres.add_user') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.phone') }}</label><input type="text" name="phone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.email') }}</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.role') }} <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            @foreach($roleOptions as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                        </select></div>
                    <div class="col-md-6 js-fonction-wrap" style="display:none;"><label class="form-label">{{ __('messages.parametres.function') }}</label>
                        <select name="fonction" class="form-select"><option value="">{{ __('messages.parametres.function_none') }}</option></select></div>
                    <div class="col-md-6 js-scope js-scope-federation" data-role="federation"><label class="form-label">{{ __('messages.federations.title') }} <span class="text-danger">*</span></label>
                        <select name="federation_id" class="form-select"><option value="">-</option>@foreach($federations as $f)<option value="{{ $f->id }}">{{ $f->nom }}</option>@endforeach</select></div>
                    <div class="col-md-6 js-scope js-scope-ligue" data-role="ligue"><label class="form-label">{{ __('messages.ligues.title') }} <span class="text-danger">*</span></label>
                        <select name="ligue_id" class="form-select"><option value="">-</option>@foreach($ligues as $l)<option value="{{ $l->id }}">{{ $l->nom }}</option>@endforeach</select></div>
                    <div class="col-md-6 js-scope js-scope-maitre" data-role="maitre"><label class="form-label">{{ __('messages.salle') }} <span class="text-danger">*</span></label>
                        <select name="salle_id" class="form-select"><option value="">-</option>@foreach($salles as $s)<option value="{{ $s->id }}">{{ $s->nom }}</option>@endforeach</select></div>
                    <div class="col-md-6 js-scope js-scope-grade" data-role="federation,ligue,maitre" data-optional="1"><label class="form-label">{{ __('messages.parametres.dan_grade') }}</label>
                        <select name="grade_id" class="form-select"><option value="">-</option>@foreach($grades->where('type_grade', 'DAN') as $g)<option value="{{ $g->id }}">{{ $g->nom_grade }} ({{ $g->ceinture }})</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.parametres.password_hint') }}</label><input type="password" name="password" class="form-control" autocomplete="new-password"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('messages.parametres.password_confirm') }}</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button><button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button></div>
        </form>
    </div>
</div>
