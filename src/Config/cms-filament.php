<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMS Filament
    |--------------------------------------------------------------------------
    |
    | Configuração do package vagkaefer/cms-filament. Publique este arquivo com
    | `php artisan vendor:publish --tag=cms-filament-config` para sobrescrever
    | qualquer valor no projeto.
    |
    */

    /**
     * Cargo administrativo da instalação.
     *
     * É a chave do bypass de autorização (`Gate::before`): quem tem este cargo
     * passa em todas as checagens de permissão. Também é o cargo protegido
     * contra exclusão e renomeação.
     */
    'admin_role' => env('CMS_ADMIN_ROLE', 'admin'),

    /**
     * E-mails que recebem o cargo administrativo ao rodar o PermissionsSeeder.
     *
     * Lista separada por vírgula no .env:
     * CMS_ADMIN_EMAILS=fulano@exemplo.com,ciclano@exemplo.com
     */
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CMS_ADMIN_EMAILS', ''))
    ))),

    /**
     * Grupo de navegação onde as telas do package aparecem no painel.
     */
    'navigation_group' => env('CMS_NAVIGATION_GROUP', 'Administração'),

    /**
     * Permissões que não vêm de um Resource e por isso não são geradas pelo
     * comando `permissions:generate-resources`. São criadas pelo seeder e
     * concedidas ao cargo administrativo.
     *
     * As três abaixo são consultadas pelo plugin de backups.
     */
    'extra_permissions' => [
        'create-backup',
        'download-backup',
        'delete-backup',
    ],

    /**
     * Tela de Configurações (tabela `configurations`).
     */
    'configurations' => [
        /**
         * Chaves cujo valor é criptografado no banco e nunca exibido na UI —
         * apenas substituível. Use para segredos (senhas, tokens, chaves de API).
         */
        'encrypted_keys' => [
            'EMAIL_PASSWORD',
            'API_SECRET',
        ],
    ],

    /**
     * Padrões dos comandos de limpeza de log.
     */
    'logs' => [
        'clean_hours' => env('CMS_LOGS_CLEAN_HOURS', 6),
        'max_size_mb' => env('CMS_LOGS_MAX_SIZE_MB', 5),
    ],

    /**
     * Versão do package instalado, exibida no rodapé e no widget de versão.
     */
    'version' => cms_filament_version(),

];
