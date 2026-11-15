{{-- Médailles par club ; rendu aussi seul après la saisie d'un résultat. --}}
@if($medals)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-medal me-1 text-warning"></i> {{ __('messages.competitions.medals') }}</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.competitions.club') }}</th>
                        <th class="text-center">🥇 {{ __('messages.competitions.gold') }}</th>
                        <th class="text-center">🥈 {{ __('messages.competitions.silver') }}</th>
                        <th class="text-center">🥉 {{ __('messages.competitions.bronze') }}</th>
                        <th class="text-center">{{ __('messages.competitions.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($medals as $club => $m)
                        <tr>
                            <td class="fw-semibold">{{ $club }}</td>
                            <td class="text-center">{{ $m['gold'] }}</td>
                            <td class="text-center">{{ $m['silver'] }}</td>
                            <td class="text-center">{{ $m['bronze'] }}</td>
                            <td class="text-center fw-bold">{{ $m['gold'] + $m['silver'] + $m['bronze'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">{{ __('messages.competitions.medals_help') }}</div>
    </div>
@endif
