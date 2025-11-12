<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array{

//        return [
//            ...parent::share($request),
//            'auth' => [
//                'user' => $request->user(),
//            ],
//        ];

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'full_name' => $request->user()->full_name,
                    // Agrega roles y permisos aquí
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],

            // ⚠️ Errores de validación (global)
            'errors' => fn () => $this->resolveValidationErrors($request),

            // ✉️ Mensajes flash (usados para alertas)
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'info'    => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
                'danger'  => fn () => $request->session()->get('danger'),
            ],

            // 🌐 Datos globales de la app
            'app' => [
                'name' => config('app.name'),
                'url'  => config('app.url'),
            ],

        ]);

    }
}
