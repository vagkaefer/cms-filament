<?php

namespace VagKaefer\CmsFilament\Support;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Contas de manutenção do fornecedor, invisíveis para o projeto consumidor.
 *
 * Os usuários cujo e-mail pertence a um destes domínios só podem ser vistos e
 * administrados entre si: para qualquer outra conta — inclusive quem tem o
 * cargo administrativo do painel — eles simplesmente não existem.
 *
 * A regra não pode ser expressa como permissão: o `Gate::before` do package dá
 * bypass total a quem é admin, então a checagem precisa acontecer antes dele.
 * Por isso ela vive aqui, e não em `HasResourcePermissions`.
 */
final class ProtectedUsers
{
    /**
     * Domínios de e-mail protegidos.
     *
     * Fixos no código de propósito: são os domínios de quem mantém o package,
     * não uma configuração de cada instalação — deixar em config permitiria ao
     * projeto consumidor desligar a própria proteção.
     *
     * @var array<int, string>
     */
    public const DOMAINS = [
        'cloudger.com.br',
        'cloudger.host',
        'kaefer.eng.br',
    ];

    /**
     * Se o e-mail pertence a um dos domínios protegidos.
     *
     * Compara só o trecho depois do último `@`, para que um domínio que apenas
     * contenha o texto protegido (`cloudger.com.br.exemplo.com`) não passe.
     */
    public static function isProtectedEmail(?string $email): bool
    {
        if ($email === null || trim($email) === '') {
            return false;
        }

        if (! Str::contains($email, '@')) {
            return false;
        }

        $domain = Str::lower(trim(Str::afterLast($email, '@')));

        return in_array($domain, self::DOMAINS, true);
    }

    /**
     * Se o registro é uma das contas protegidas.
     */
    public static function isProtected(?Model $user): bool
    {
        if ($user === null) {
            return false;
        }

        $email = $user->getAttribute('email');

        return self::isProtectedEmail(is_string($email) ? $email : null);
    }

    /**
     * Se quem está autenticado é uma das contas protegidas.
     */
    public static function currentUserIsProtected(): bool
    {
        $user = Auth::user();

        return $user instanceof Model && self::isProtected($user);
    }

    /**
     * Se as contas protegidas devem ser escondidas nesta requisição.
     *
     * Sem ninguém autenticado (console, filas) o resultado é `true`: esconder é
     * o lado seguro quando não há como saber quem pergunta.
     */
    public static function hidesFrom(): bool
    {
        return ! self::currentUserIsProtected();
    }

    /**
     * Remove as contas protegidas da query, quando for o caso.
     *
     * Os `not like` vão agrupados num único `where` aninhado para que a
     * exclusão não se misture a um `orWhere` de quem chamou — dentro de um
     * grupo, nenhum `or` externo reabre o filtro.
     *
     * @param  Builder  $query
     */
    public static function scopeVisible(Builder $query): void
    {
        if (! self::hidesFrom()) {
            return;
        }

        $query->where(function (Builder $query): void {
            foreach (self::DOMAINS as $domain) {
                $query->where('email', 'not like', '%@' . $domain);
            }
        });
    }
}
