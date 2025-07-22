<?php

namespace Mgx\Router;

/**
 * ControllerResolver sınıfı,
 * "Controller@method" formatındaki handler'ları çalıştırılabilir hale getirir.
 */
class ControllerResolver
{
    /**
     * "Controller@method" şeklindeki bir string'i çözümleyip çalıştırılabilir hale getirir.
     *
     * @param string $controllerHandler Örn: "App\Controllers\UserController@show"
     * @return array [object, method]
     * @throws \Exception
     */
    public function resolve(string $controllerHandler): array
    {
        // "Controller@method" formatında mı?
        if (!str_contains($controllerHandler, '@')) {
            throw new \InvalidArgumentException("Geçersiz controller handler: $controllerHandler");
        }

        [$className, $method] = explode('@', $controllerHandler, 2);

        if (!class_exists($className)) {
            throw new \RuntimeException("Controller sınıfı bulunamadı: $className");
        }

        $instance = new $className();

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Controller metodu tanımlı değil: $className@$method");
        }

        // Geriye [sınıf örneği, method ismi] döner — bu PHP'nin callable formatıdır
        return [$instance, $method];
    }
}
