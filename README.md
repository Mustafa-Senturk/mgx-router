=======
# mgx-router

---

## Kurulum

```bash
composer require mgx/router
```

`composer.json` dosyanızda PSR-4 autoload tanımı otomatik olarak yapılır.

---

## Temel Kullanım

```php
use Mgx\Router\Router;

$router = new Router();
```

---

## Route Tanımlama

### Basit GET Route (Closure)

```php
$router->get('/hello', function($request) {
    return 'Merhaba Dünya!';
});
```
- `/hello` adresine gelen GET isteğinde çalışır.
- `$request` parametresi ile [`Request`](mgx-router/src/Request.php) nesnesine erişebilirsiniz.

---

### POST Route

```php
$router->post('/api/data', function($request) {
    $data = $request->input();
    return 'Gelen veri: ' . json_encode($data);
});
```
- POST body veya form verisine `$request->input()` ile ulaşılır.

---

### Controller@method Handler

```php
$router->get('/users/{id}', 'App\Controllers\UserController@show');
```
- `App\Controllers\UserController` sınıfındaki `show` metodunu çağırır.
- `{id}` parametresi otomatik olarak `$request->param('id')` ile alınır.

---

### Controller Array Handler

```php
$router->get('/users', [UserController::class, 'list']);
```
- PHP array syntax ile controller ve method belirtilebilir.

---

## Route Parametreleri

```php
$router->get('/search/{term}', function($request) {
    $term = $request->param('term');
    $page = $request->query('page', 1);
    return "Arama: $term, Sayfa: $page";
});
```
- Path parametreleri: `$request->param('term')`
- Query string: `$request->query('page', 1)`

---

## Route Parametrelerini Toplu Alma

```php
$router->get('/multi/{foo}/{bar}', function($request) {
    $params = $request->allParams();
    return 'Parametreler: ' . json_encode($params);
});
```
- Tüm path parametreleri dizi olarak alınabilir.

---

## Route Gruplama

### Prefix, Namespace ve Middleware ile Grup

```php
$router->group([
    'prefix' => '/admin',
    'middleware' => [AuthMiddleware::class],
    'namespace' => 'App\Controllers'
], function($router) {
    $router->get('/dashboard', 'AdminController@dashboard');
    $router->get('/users', 'UserController@list');
});
```
- `/admin/dashboard` ve `/admin/users` adresleri oluşur.
- Grup içindeki tüm route’lara `AuthMiddleware` uygulanır.
- Handler’lar için namespace otomatik eklenir.

---

### İç İçe (Nested) Grup

```php
$router->group(['prefix' => '/api'], function($router) {
    $router->group(['prefix' => '/v1'], function($router) {
        $router->get('/ping', function() { return 'pong'; });
    });
});
```
- `/api/v1/ping` adresi oluşur.

---

## Middleware Kullanımı

### Global Middleware

```php
$router->middleware(LogMiddleware::class);
```
- Tüm route’lara uygulanır.

### Route Bazlı Middleware

```php
$router->get('/secret', function($request) {
    return 'Gizli Alan!';
})->middleware([AuthMiddleware::class]);
```
- Sadece ilgili route’a uygulanır.

### Middleware Sözleşmesi

Tüm middleware’ler [`MiddlewareInterface`](mgx-router/src/MiddlewareInterface.php) arayüzünü uygulamalıdır.

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!$request->header('authorization')) {
            http_response_code(401);
            return 'Yetkisiz!';
        }
        $request->set('user', ['id' => 1, 'name' => 'Test User']);
        return $next($request);
    }
}
```

---

## Controller Kullanımı

Controller’lar, route handler olarak kullanılabilir. Örnek:

```php
class UserController
{
    public function show($request)
    {
        $id = $request->param('id');
        return "Kullanıcı Detayı: $id";
    }
}
```
- Handler olarak `'App\Controllers\UserController@show'` veya `[UserController::class, 'show']` kullanılabilir.

---

## Named Route ve URL Oluşturma

```php
$router->get('/profile/{id}', 'App\Controllers\ProfileController@show')->name('profile.show');

$profileUrl = $router->route('profile.show', ['id' => 42]);
// $profileUrl: "/profile/42"
```
- Route’a isim verilir ve parametrelerle URL oluşturulabilir.

---

## Fallback (404) Handler

```php
$router->fallback(function() {
    return 'Sayfa bulunamadı!';
});
```
- Hiçbir route eşleşmezse çalışır.

---

## Tüm HTTP Metodları

```php
$router->put('/put-example', function() { return 'PUT isteği'; });
$router->patch('/patch-example', function() { return 'PATCH isteği'; });
$router->delete('/delete-example', function() { return 'DELETE isteği'; });
```
- PUT, PATCH, DELETE gibi metodlar desteklenir.

---

## JSON Body ile Çalışma

```php
$router->post('/json', function($request) {
    $data = $request->input();
    return 'JSON: ' . json_encode($data);
});
```
- Content-Type `application/json` ise body otomatik parse edilir.

---

## Custom Attribute Kullanımı

```php
$router->get('/me', function($request) {
    $user = $request->user();
    return 'Giriş yapan: ' . ($user['name'] ?? 'Anonim');
})->middleware([AuthMiddleware::class]);
```
- Middleware ile eklenen attribute’lara `$request->user()` ile erişilebilir.

---

## Dispatch İşlemi

```php
$router->dispatch();
```
- Tüm route’lar ve middleware’ler işlenir, uygun handler çalıştırılır.

---

## Sınıf ve Dosya Yapısı

- [`Router`](mgx-router/src/Router.php): Route ve middleware yönetimi, dispatch işlemi
- [`Route`](mgx-router/src/Route.php): Tekil route nesnesi, handler ve middleware zinciri
- [`Request`](mgx-router/src/Request.php): HTTP istek nesnesi, parametre ve body erişimi
- [`MiddlewareInterface`](mgx-router/src/MiddlewareInterface.php): Middleware sözleşmesi
- [`ControllerResolver`](mgx-router/src/ControllerResolver.php): Controller@method çözümleyici

---

---

## Lisans

MIT

---

Her türlü katkı ve öneri için iletişime geçebilirsiniz.
