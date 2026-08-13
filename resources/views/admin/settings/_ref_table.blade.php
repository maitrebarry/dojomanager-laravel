{{-- Table réutilisable pour un onglet de référentiel dans Paramètres.
     Params: $title, $createRoute (?string), $columns (array), $rows (collection de ['edit','delete','cells']). --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">{{ $title }}</h6>
    @if($createRoute)
        <a href="{{ $createRoute }}" class="btn btn-sm text-white" style="background-color: var(--navbar-bg);"><i class="fas fa-plus me-1"></i> {{ __('messages.add') }}</a>
    @endif
</div>
<div class="table-responsive">
    <table class="table table-hover table-sm align-middle mb-0">
        <thead class="table-light">
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
                <th class="text-end">{{ __('messages.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row['cells'] as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
                <td class="text-end">
                    @if($row['edit'])
                        <a href="{{ $row['edit'] }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                    @endif
                    @if($row['delete'])
                        <form action="{{ $row['delete'] }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.delete_warning') }}');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ count($columns) + 1 }}" class="text-center text-muted py-4">{{ __('messages.no_results') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
