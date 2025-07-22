<?php

require_once __DIR__ . '/vendor/autoload.php';

use Mgx\Router\Router;
use Mgx\Router\Request;
use Mgx\Router\MiddlewareInterface;

// -----------------------------
// ÖRNEK CONTROLLER SINIFLARI
// -----------------------------

namespace App\Controllers;

class UserController
{
    public function show($request)
    {
        $id = $request->param('id');
        return "Kullanıcı Detayı: $id";
    }

    public function list($request)
    {
        return "Kullanıcı Listesi";
    }
}

class AdminController
{
    public function dashboard($request)
    {
        return "Admin Dashboard";
    }
}

class ProfileController
{
    public function show($request)
    {
        $id = $request->param('id');
        return "Profil: $id";
    }
}

// -----------------------------
// ÖRNEK MIDDLEWARE SINIFLARI
// -----------------------------

namespace App\Middleware;

use Mgx\Router\Request;
use Mgx\Router\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!$request->header('authorization')) {
            http_response_code(401);
            return 'Yetkisiz!';
        }
        // Kullanıcıyı attribute olarak ekle
        $request->set('user', ['id' => 1, 'name' => 'Test User']);
        return $next($request);
    }
}

class LogMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        error_log("İstek: " . $request->method() . " " . $request->uri());
        return $next($request);
    }
}

// -----------------------------
// ROUTER TANIMLARI
// -----------------------------

namespace {

    use Mgx\Router\Router;
    use App\Controllers\UserController;
    use App\Controllers\AdminController;
    use App\Controllers\ProfileController;
    use App\Middleware\AuthMiddleware;
    use App\Middleware\LogMiddleware;

    $router = new Router();

    // Global middleware örneği
    $router->middleware(LogMiddleware::class);

    // Basit GET route (Closure)
    $router->get('/hello', function($request) {
        return 'Merhaba Dünya!';
    });

    // POST örneği
    $router->post('/api/data', function($request) {
        $data = $request->input();
        return 'Gelen veri: ' . json_encode($data);
    });

    // Controller@method örneği
    $router->get('/users/{id}', 'App\Controllers\UserController@show');

    // Controller array handler örneği
    $router->get('/users', [UserController::class, 'list']);

    // Route bazlı middleware
    $router->get('/secret', function($request) {
        return 'Gizli Alan!';
    })->middleware([AuthMiddleware::class]);

    // Route grubu (prefix, middleware, namespace)
    $router->group([
        'prefix' => '/admin',
        'middleware' => [AuthMiddleware::class],
        'namespace' => 'App\Controllers'
    ], function($router) {
        $router->get('/dashboard', 'AdminController@dashboard');
        $router->get('/users', 'UserController@list');
    });

    // Nested group örneği
    $router->group(['prefix' => '/api'], function($router) {
        $router->group(['prefix' => '/v1'], function($router) {
            $router->get('/ping', function() { return 'pong'; });
        });
    });

    // Named route ve URL oluşturma
    $router->get('/profile/{id}', 'App\Controllers\ProfileController@show')->name('profile.show');

    // Route URL oluşturma örneği
    $profileUrl = $router->route('profile.show', ['id' => 42]);
    // echo $profileUrl; // /profile/42

    // Fallback (404) örneği
    $router->fallback(function() {
        return 'Sayfa bulunamadı!';
    });

    // Tüm HTTP metodları için örnekler
    $router->put('/put-example', function() { return 'PUT isteği'; });
    $router->patch('/patch-example', function() { return 'PATCH isteği'; });
    $router->delete('/delete-example', function() { return 'DELETE isteği'; });

    // Route parametreleri ve query örneği
    $router->get('/search/{term}', function($request) {
        $term = $request->param('term');
        $page = $request->query('page', 1);
        return "Arama: $term, Sayfa: $page";
    });

    // JSON body örneği
    $router->post('/json', function($request) {
        $data = $request->input();
        return 'JSON: ' . json_encode($data);
    });

    // Custom attribute örneği (middleware ile taşınan veri)
    $router->get('/me', function($request) {
        $user = $request->user();
        return 'Giriş yapan: ' . ($user['name'] ?? 'Anonim');
    })->middleware([AuthMiddleware::class]);

    // Route parametrelerini topluca alma
    $router->get('/multi/{foo}/{bar}', function($request) {
        $params = $request->allParams();
        return 'Parametreler: ' . json_encode($params);
    });

    // Dispatch işlemi (gerçek kullanımda genellikle index.php'de)
    $router->dispatch();
}