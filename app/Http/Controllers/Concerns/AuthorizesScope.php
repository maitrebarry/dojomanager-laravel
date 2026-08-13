<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Garde d'accès : 403 si le modèle est hors du périmètre (fédération/ligue/salle)
 * de l'utilisateur courant. Le modèle doit utiliser le trait ScopedToUser.
 */
trait AuthorizesScope
{
    protected function guardScope(Model $model): void
    {
        $class = $model::class;

        abort_unless(
            $class::query()->visibleTo(request()->user())->whereKey($model->getKey())->exists(),
            403,
            __('messages.out_of_scope')
        );
    }
}
