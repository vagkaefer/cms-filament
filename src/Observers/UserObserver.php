<?php

namespace VagKaefer\CmsFilament\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encerra as demais sessões do usuário quando a senha muda.
 *
 * Trocar a senha é o que a pessoa faz quando desconfia que alguém entrou na
 * conta — manter as outras sessões abertas anularia o efeito.
 */
class UserObserver
{
    public function updating(Model $user): void
    {
        if (! $user->isDirty('password')) {
            return;
        }

        $driver = config('session.driver');

        if ($driver === 'database') {
            $currentSessionId = session()->getId();

            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', $currentSessionId)
                ->delete();

            return;
        }

        // Fora do driver de banco não há como enumerar sessões: girar o
        // remember_token invalida ao menos os logins persistentes.
        if (method_exists($user, 'setRememberToken')) {
            $user->setRememberToken(Str::random(60));
        }
    }
}
