<?php

namespace VagKaefer\CmsFilament\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Par chave/valor editável pelo painel.
 *
 * Serve para o que precisa mudar sem deploy (remetente de e-mail, credenciais
 * de integração). Chaves listadas em `cms-filament.configurations.encrypted_keys`
 * são gravadas criptografadas e nunca exibidas — só substituídas.
 *
 * @property string $name
 * @property ?string $value
 */
class Configuration extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * Chaves cujo valor é criptografado no banco.
     *
     * Vem da config para que cada projeto defina o que é sensível sem editar
     * o package.
     *
     * @return array<int, string>
     */
    public static function encryptedKeys(): array
    {
        /** @var array<int, string> $keys */
        $keys = config('cms-filament.configurations.encrypted_keys', []);

        return $keys;
    }

    /**
     * Se o valor deste registro é armazenado criptografado.
     */
    public function isEncrypted(): bool
    {
        return in_array($this->name, static::encryptedKeys(), true);
    }

    public function setValueAttribute(?string $value): void
    {
        // Determina o nome atual de forma segura
        $name = $this->attributes['name']
            ?? $this->getOriginal('name')
            ?? $this->name;

        // Sem name (registro novo em construção) não há como saber se a chave
        // é sensível — grava como veio; o mutator roda de novo quando o name
        // estiver definido.
        if (! $name) {
            $this->attributes['value'] = $value;

            return;
        }

        if (in_array($name, static::encryptedKeys(), true)) {
            if (filled($value)) {
                $this->attributes['value'] = Crypt::encryptString($value);
            }
            // Valor vazio mantém o conteúdo criptografado anterior: é assim que
            // o formulário "não mexe" num segredo que o usuário não digitou.
            return;
        }

        $this->attributes['value'] = $value;
    }

    public function getValueAttribute(mixed $encryptedValue): mixed
    {
        if (! $this->name) {
            return null;
        }

        if (! $this->isEncrypted()) {
            return $encryptedValue;
        }

        if (blank($encryptedValue)) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            return '*** ERRO DE DESCRIPTOGRAFIA ***';
        }
    }

    public function getValueDisplayAttribute(): ?string
    {
        return $this->isEncrypted() ? '••••••••' : $this->value;
    }
}
