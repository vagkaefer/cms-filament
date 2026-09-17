<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <b>{{ config('app.name') }}</b>
                <p>Versão do painel: {{ config('cms-filament.version') }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
