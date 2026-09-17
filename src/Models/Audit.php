<?php

namespace VagKaefer\CmsFilament\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit as BaseAudit;

class Audit extends BaseAudit
{
    /**
     * Sobrescreve a relação auditable para evitar erro quando a classe não existe.
     */
    public function auditable(): MorphTo
    {
        // Verifica se a classe existe antes de criar a relação
        if (! class_exists($this->auditable_type ?? '')) {
            // Retorna uma relação "vazia" que não vai quebrar
            return $this->morphTo('auditable', 'auditable_type', 'auditable_id')
                ->withoutGlobalScopes();
        }

        return $this->morphTo('auditable', 'auditable_type', 'auditable_id');
    }

    /**
     * Sobrescreve o carregamento da relação para evitar erro.
     */
    public function getAuditableAttribute(): mixed
    {
        // Se a classe não existe, retorna null sem tentar carregar
        if (! class_exists($this->auditable_type ?? '')) {
            return null;
        }

        return $this->getRelationValue('auditable');
    }

    /**
     * Accessor para auditable_type que retorna nome amigável quando a classe não existe.
     *
     * @return Attribute<string, never>
     */
    protected function auditableTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $type = $this->auditable_type;

                if (! $type) {
                    return 'N/A';
                }

                // Verifica se a classe existe
                if (! class_exists($type)) {
                    // Extrai o nome da classe do namespace
                    $parts = explode('\\', $type);
                    $className = end($parts);

                    return "{$className} [Removido]";
                }

                return class_basename($type);
            }
        );
    }

    /**
     * Tenta obter o modelo auditável, retornando null se a classe não existir.
     */
    public function getAuditableModelSafely(): mixed
    {
        try {
            // Verifica se a classe existe antes de tentar carregar a relação
            if (! class_exists($this->auditable_type ?? '')) {
                return null;
            }

            return $this->auditable;
        } catch (\Throwable $e) {
            // Log do erro para debug
            Log::warning('Erro ao carregar modelo auditável: ' . $e->getMessage(), [
                'audit_id' => $this->id,
                'auditable_type' => $this->auditable_type,
                'auditable_id' => $this->auditable_id,
            ]);

            return null;
        }
    }
}
