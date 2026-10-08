<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $nuevo = static::filtrarAtributosOcultos($model, $model->getAttributes());
            static::registrar('create', $model, null, $nuevo);
        });

        static::updated(function (Model $model) {
            $cambios = $model->getChanges();
            unset($cambios['updated_at']);

            if (empty($cambios)) {
                return;
            }

            $original = [];
            foreach (array_keys($cambios) as $campo) {
                $original[$campo] = $model->getRawOriginal($campo);
            }

            $antes = static::filtrarAtributosOcultos($model, $original);
            $despues = static::filtrarAtributosOcultos($model, $cambios);

            $accion = (array_key_exists('estado_activa', $cambios) && !$model->estado_activa)
            ? 'anular'
            : 'update';

            static::registrar('update', $model, $antes, $despues);
        });

        static::deleted(function (Model $model) {
            $antes = static::filtrarAtributosOcultos($model, $model->getAttributes());
            static::registrar('delete', $model, $antes, null);
        });
    }

    protected static function registrar(string $accion, Model $model, ?array $antes, ?array $despues): void
    {
        AuditLog::create([
            'user_id'        => Auth::id(),
            'action'         => $accion,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'old_values'     => $antes,
            'new_values'     => $despues,
            'ip_address'     => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    
    protected static function filtrarAtributosOcultos(Model $model, ?array $atributos): ?array
    {
        if (empty($atributos)) {
            return $atributos;
        }

        $ocultos = $model->getHidden();
        return array_diff_key($atributos, array_flip($ocultos));
    }
}