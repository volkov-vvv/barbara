<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the authenticated user's preferred UI locale.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale->value
            ?? $request->cookie('locale')
            ?? config('app.locale');

        if (! in_array($locale, Locale::values(), true)) {
            $locale = Locale::English->value;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
