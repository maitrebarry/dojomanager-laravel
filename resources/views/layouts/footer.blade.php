<footer class="text-center py-3 border-top" style="font-size: 13px;">
    <div class="container-fluid">
        <p class="text-muted mb-1">
            &copy; {{ date('Y') }} {{ __('messages.app_name') }}.
        </p>
        <small class="text-muted">
            <a href="#" class="text-decoration-none">Conditions</a> | 
            <a href="#" class="text-decoration-none">Confidentialité</a> | 
            v{{ config('app.version', '1.0.0') }}
        </small>
    </div>
</footer>

<style>
    html.dark-mode footer {
        background-color: #1a1a1a;
        border-top-color: #333;
    }

    html.dark-mode footer a {
        color: var(--primary-color);
    }

    html.dark-mode footer a:hover {
        color: var(--secondary-color);
    }
</style>
