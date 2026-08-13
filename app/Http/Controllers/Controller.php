<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Contrôleur de base avec fonctionnalités communes
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Réponse de succès JSON
     */
    protected function success($message = null, $data = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Réponse d'erreur JSON
     */
    protected function error($message = null, $code = 400, $data = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Redirection avec message flash
     */
    protected function redirectWithMessage($path, $message, $type = 'success')
    {
        return redirect($path)->with($type, $message);
    }

    /**
     * Retourne le layout admin
     */
    protected function adminLayout()
    {
        return 'layouts.admin';
    }
}
