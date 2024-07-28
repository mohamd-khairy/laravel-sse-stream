[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

# Laravel SSE

Laravel package to provide Server Sent Events functionality for your app. You can use this package to show instant notifications to your users without them having to refresh their pages.

## Requirements

 - PHP >= 8
 - Laravel 11

## Installation

Via Composer

``` bash
$ composer require khairy/laravel-sse-stream
```

For Laravel < 5.5:

Add Service Provider to `config/app.php` in `providers` section
```php
Khairy\LaravelSSEStream\SSEServiceProvider::class,
```

Add Facade to `config/app.php` in `aliases` section
```php
'SSE' => Khairy\LaravelSSEStream\Facades\SSEFacade::class,
```


---

Publish package's config, migration and view files by running below command:

```bash
php artisan vendor:publish --provider="Khairy\LaravelSSEStream\SSEServiceProvider"
```
Run `php artisan migrate` to create `sselogs` table.

## Setup SSE

Setup config options in `config/sse.php` file and then add this in your view/layout file:

```php
@include('sse::view')
```

## Usage

Syntax:
```php
/**
 * @param string $message : notification message
 * @param string $type : alert, success, error, warning, info
 * @param string $event : Type of event such as "EmailSent", "UserLoggedIn", etc
 */
SSEFacade::notify($message, $type = 'info', $event = 'message')
```

To show popup notifications on the screen, in your controllers/event classes, you can  do:

```php
use Khairy\LaravelSSEStream\Facades\SSEFacade;

public function myMethod()
{
    SSEFacade::notify('hello world....');
    
    // or via helper
    sse_notify('hi there');
}
```

## Customizing Notification Library

By default, package uses [noty](https://github.com/mohamd-khairy/laravel-sse-stream) for showing notifications. You can customize this by modifying code in `resources/views/vendor/sse/view.blade.php` file.

## Customizing SSE Events

By default, pacakge uses `message` event type for streaming response:


```php
SSEFacade::notify($message, $type = 'notification', $event = 'message')
```

Notice `$event = 'message'`. You can customize this, let's say you want to use `login` as SSE event type:

```php
use Khairy\LaravelSSEStream\Facades\SSEFacade;

public function myMethod()
{
    SSEFacade::notify('hello world....', 'notification', 'login');
    
    // or via helper
    sse_notify('hi there', 'notification', 'login');
}
```

Then you need to handle this in your view yourself like this:

```javascript
<script>
var es = new EventSource("{{route('__sse_stream__')}}");

es.addEventListener("UserLoggedIn", function (e) {
    var data = JSON.parse(e.data);
    alert(data.message);
}, false);

</script>
```

## Credits

- [Mohamed Khairy][link-author]
- [All Contributors][link-contributors]

## License

Please see the [license file](license.md) for more information.

[link-packagist]: https://packagist.org/packages/khairy/laravel-sse-stream
[link-downloads]: https://packagist.org/packages/khairy/laravel-sse-stream
[link-author]: https://github.com/mohamd-khairy
[link-contributors]: https://github.com/mohamd-khairy/laravel-sse-stream/graphs/contributors
"# laravel-sse-stream" 
"# laravel-sse-stream" 
