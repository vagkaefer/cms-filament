<div id="cms-filament-footer"
    class="bg-white dark:bg-gray-950/50 border-t border-gray-200 dark:border-white/10 w-full mt-auto"
    style="padding: 10px 24px; font-size: 11px;">
    <div style="display: flex; align-items: center; justify-content: space-between;"
        class="text-gray-600 dark:text-gray-400">
        <div style="flex-shrink: 0;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </div>
        <div style="flex-shrink: 0;">
            Versão {{ config('cms-filament.version') }}
        </div>
    </div>
</div>
