<?php

if (! function_exists('app_version')) {
    /**
     * Obtém a versão da aplicação a partir das tags Git ou composer.json
     */
    function app_version(): string
    {
        // Tenta obter da última tag Git
        try {
            $gitTag = shell_exec('git describe --tags --abbrev=0 2>/dev/null');
            if ($gitTag) {
                return trim($gitTag);
            }
        } catch (Throwable $e) {
            // Ignora erros ao tentar obter tag do git
        }

        // Tenta obter do composer.json
        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composerData = json_decode(file_get_contents($composerPath), true);
            if (isset($composerData['version'])) {
                return $composerData['version'];
            }
        }

        // Fallback para versão desconhecida
        return '0.0.0';
    }
}

if (! function_exists('cms_filament_version')) {
    /**
     * Obtém a versão instalada do package vagkaefer/cms-filament.
     *
     * A fonte canônica é o runtime do Composer (`InstalledVersions`), que reflete
     * o que está de fato instalado no `vendor/` do projeto consumidor. Em um
     * checkout de desenvolvimento do próprio package (onde ele não aparece como
     * dependência instalada) cai para a última tag Git do repositório.
     */
    function cms_filament_version(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion('vagkaefer/cms-filament');
                if ($version !== null && $version !== '') {
                    return $version;
                }
            } catch (Throwable $e) {
                // Package não instalado via Composer — segue para o fallback
            }
        }

        // Fallback: checkout de desenvolvimento do próprio package
        try {
            $packageRoot = dirname(__DIR__);
            $gitTag = shell_exec(sprintf(
                'git -C %s describe --tags --abbrev=0 2>/dev/null',
                escapeshellarg($packageRoot)
            ));
            if ($gitTag) {
                return trim($gitTag);
            }
        } catch (Throwable $e) {
            // Ignora erros ao tentar obter tag do git
        }

        return 'dev';
    }
}

if (! function_exists('cms_filament_user_model')) {
    /**
     * Classe do model de usuário do projeto consumidor.
     *
     * O package não entrega model de usuário: ele usa o do projeto, lido do
     * guard padrão. Assim o mesmo código serve a qualquer app, com id inteiro
     * ou uuid, e respeita um `AUTH_MODEL` customizado.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    function cms_filament_user_model(): string
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = config('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);

        return $model;
    }
}

if (! function_exists('cms_filament_is_admin')) {
    /**
     * Se o usuário autenticado tem o cargo administrativo da instalação.
     *
     * Mesmo critério do bypass de autorização registrado no ServiceProvider
     * (`Gate::before`). O `method_exists` protege instalações com mais de um
     * guard, onde o usuário logado pode não usar a trait de cargos.
     */
    function cms_filament_is_admin(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user === null || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole(config('cms-filament.admin_role', 'admin')) === true;
    }
}
