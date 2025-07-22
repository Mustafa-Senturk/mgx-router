<?php

namespace Mgx\Router;

use Mgx\Router\Request;

/**
 * Tüm middleware sınıflarının uyması gereken sözleşmeyi tanımlar.
 */
interface MiddlewareInterface
{
    /**
     * Middleware çalıştırıldığında çağrılacak metot.
     *
     * @param Request $request Gelen HTTP isteği
     * @param callable $next Bir sonraki middleware veya handler fonksiyonu
     * @return mixed Middleware’in sonucu veya $next çağrısı
     */
    public function handle(Request $request, callable $next): mixed;
}
