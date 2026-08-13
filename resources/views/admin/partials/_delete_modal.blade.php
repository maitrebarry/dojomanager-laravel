{{-- Requires: $question (string). Optional: nothing else. Wired by admin.partials._list_scripts --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> {{ __('messages.confirm_delete') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('messages.cancel') }}"></button>
            </div>
            <div class="modal-body">
                <p>{{ $question ?? __('messages.delete_warning') }}</p>
                <div class="alert alert-light border">
                    <strong id="deleteName" class="d-block"></strong>
                </div>
                <p class="text-danger mb-0"><small>{{ __('messages.delete_warning') }}</small></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('messages.yes_delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
