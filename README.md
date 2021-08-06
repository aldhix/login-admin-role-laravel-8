# Login Admin Role Laravel 8 (Manually Authenticating Users & Authorization) 
Membuat login admin yang memiliki role  menggunakan Auth guard dan Authorization Larvel 8

[![Login Admin Role Laravel 8 (Manually Authenticating Users & Authorization) ](https://img.youtube.com/vi/Y66sNEeCKAo/0.jpg)](https://www.youtube.com/watch?v=Y66sNEeCKAo)

[Video : Login Admin Role Laravel 8 (Manually Authenticating Users & Authorization) - YouTube](https://youtu.be/Y66sNEeCKAo)

## Instalasi
### Create Project New
```php
composer create-project laravel/laravel example-app
```
### Create Controller Login
```php
php artisan make:controller LoginAdminController
```

### Create Model, Migrate, Seeder Admin
```php
php artisan make:mode Admin -ms
```

### Connect to Database
Mengubah koneksi ke database pada file **.env** pada bagian ini sesusai database anda :
```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### Setup Model Admin
Menambahkan **fillable** dan **hidden** field, serta mengubah parent kelas menjadi **Authenticatable** dan menambahkan kelas **Notifiable**.

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

### Setup Migrate Admin
tambah field yang dibutuhkan pada table admin di class **CreateAdminsTable** pada method **up()**.

```php
public function up()
{
    Schema::create('admins', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->enum('role',['admin','editor','operator'])->default('operator');
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```

### Setup Seeder Admin
Buat beberapa admin dengan role berbeda pada method **run()**

```php
use App\Models\Admin
................................
................................
public function run()
{
    Admin::create([
        'name'     => 'Administrator',
        'email'    => 'admin@localhost.com',
        'role'    => 'admin',
        'password' => bcrypt('password'),
    ]);

    Admin::create([
        'name'     => 'Editor',
        'email'    => 'editor@localhost.com',
        'role'    => 'editor',
        'password' => bcrypt('password'),
    ]);

    Admin::create([
        'name'     => 'Operator',
        'email'    => 'operator@localhost.com',
        'role'    => 'operator',
        'password' => bcrypt('password'),
    ]);
}
```

### Setup DatabaseSeeder
Panggil class seeder Admin di method **run()**.
```php
public function run()
{
    $this->call([
        AdminSeeder::class,
    ]);
}
```
###  Run Migrate & Seeder
Jalankan migrate dengan perintah artisan :
```php 
php artisan migrate
```
atau 
```php 
php artisan migrate:fresh
```
Jalan seeder dengan perintah artisan :
```php
php artisan db:seed
```

### Create Config Admin
Buat file configurasi **admin.php** pada path **config/admin** untuk menetapkan **prefix** halaman admin.
```php
return [
    'prefix'=>'admin',
] ;
```
### Setup Config Auth
Menambah perintah **guard admin** pada file **config/auth.php** :
```php
'guards' => [
	.............
	.............

    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
],

'providers' => [
	.............
	.............

    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
]
```

### Setup Provider AuthServiceProvider
Menambah **gate role** pada method **boot()** untuk mengecek role user :

```php
public function boot()
{
	..............
	..............
	
    Gate::define('role', function($user, ...$role){
        return in_array($user->role, $role);
    });
}
```
### Setup Middleware Authenticate
Menambah perintah kondisi apabila pengakses prefix admin akan diredirect ke halaman **route('admin.login')** perintah tersebut ditambahkan pada method **redirectTo()**

```php
protected function redirectTo($request)
{
    if($request->is(config('admin.prefix').'*')){
        return route('admin.login');
    }
    
    .............
    .............
}
```

### Setup Middleware RedirectIfAuthenticated
Menambah perintah kondisi jika guard admin check bernilai benar akan di redirect ke halaman **route("dasboard")** pada method **handle()**.

```php
public function handle(Request $request, Closure $next, ...$guards)
{
    if(Auth::guard('admin')->check()){
        return redirect()->route('dashboard');
    }

    .................
    ................. 
}
```
### Setup Controller LoginAdminController

Tambahkan method **__construct(), formLogin(), login(), logout()**.

```php
use Auth;

class LoginAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin',['except'=>'logout']);
    }

    public function formLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'=>'required|email|exists:admins',
            'password'=>'required'
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->intended(config('admin.prefix'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }
}
```

### Setup Route
Buat beberapa route pada **routes/web.php**.

```php
Route::group([
        'prefix'=>config('admin.prefix'),
        'namespace'=>'App\\Http\\Controllers',
    ],function () {

        Route::get('login','LoginAdminController@formLogin')->name('admin.login');
        Route::post('login','LoginAdminController@login');

        Route::middleware(['auth:admin'])->group(function () {
            Route::post('logout','LoginAdminController@logout')->name('admin.logout');
            Route::view('/','dashboard')->name('dashboard');
            Route::view('/post','data-post')->name('post')->middleware('can:role,"admin","editor"');
            Route::view('/admin','data-admin')->name('admin')->middleware('can:role,"admin"');
        });
});
```

### Create Views

- template.blade.php

```html
<!doctype html>
<html lang="en">
  <head>
    <title>{{ config('app.name') }}</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  </head>
  <body>

        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="#">{{ config('app.name') }}</a>
                <button class="navbar-toggler" data-target="#my-nav" data-toggle="collapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="my-nav" class="collapse navbar-collapse">
                    <ul class="navbar-nav ml-auto">
                        @guest('admin')
                        <li class="nav-item">
                            <a href="{{ route('admin.login') }}" class="nav-link">Login</a>
                        </li>
                        @else
                        @can('role',['admin','editor'])
                            <li class="nav-item">
                                <a href="{{ route('post') }}" class="nav-link">Data Post</a>
                            </li>
                        @endcan
                        @can('role','admin')
                            <li class="nav-item">
                                <a href="{{ route('admin') }}" class="nav-link">Data Admin</a>
                            </li>
                        @endcan
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link" data-toggle="dropdown">{{ Auth::user()->name }}</a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="#" class="dropdown-item">My Profile</a>
                                <a href="{{ route('admin.logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                class="dropdown-item">Logout</a>
                                <form action="{{ route('admin.logout') }}" id="logout-form" method="post">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container">
        @yield('content')
        </div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
```
- login.blade.php

```php
@extends('template')
@section('content')
<div class="row">
    <div class="col-md-4 offset-md-4 mt-5">
        <div class="card">
            <div class="card-header bg-dark text-light">
                Login
            </div>
            <div class="card-body p-2">
                <form action="" method="post">
                    @csrf
                    <div class="form-group">
                      <input type="email"
                        class="form-control{{ $errors->has('email') ? ' is-invalid':'' }}"
                        name="email"
                        placeholder="Email" />
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                      <input type="password"
                        class="form-control{{ $errors->has('password') ? ' is-invalid':'' }}"
                        name="password"
                        placeholder="Password" />
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check form-group">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="remember">
                            Remember Me
                        </label>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-dark btn-block">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
```

- dashboard.blade.php

```html
@extends('template')
@section('content')
<div class="jumbotron mt-3">
    <h1 class="display-4">Dashboard</h1>
    <p>Lorem ipsum....</p>
</div>
@endsection
```

- data-post.blade.php

```html
@extends('template')
@section('content')
<div class="jumbotron mt-3">
    <h1 class="display-4">Post Only Admin or Editor</h1>
    <p>Lorem ipsum....</p>
</div>
@endsection
```

- data-admin.blade.php
```html
@extends('template')
@section('content')
<div class="jumbotron mt-3">
    <h1 class="display-4">Only Admin</h1>
    <p>Lorem ipsum....</p>
</div>
@endsection
```


### Run Server
```php
 php artisan serve
```

### Cara Menggunakan Authorization Role
**Via Middleware**
Contoh menggunakan role dimiddleware :
```php
Route::view('/admin','data-admin')
->name('admin')
->middleware('can:role,"admin"');
```
Contoh role dengan lebih dari satu :
```php
Route::view('/post','data-post')
->name('post')
->middleware('can:role,"admin","editor"');
```

**Via Blade Templates**
Contoh menggunakan role pada blde :
```php
@can('role','admin')
    ...................................
    ...................................
@endcan
```
Contoh role dengan lebih dari satu :
```php
@can('role',['admin','editor'])
    ...................................
    ...................................
@endcan
```

**Via Controller**
Contoh method diberi role :
```php
Gate::authorize('role', 'admin');

// The action is authorized...
```
Contoh role lebih dari satu :
```php
Gate::authorize('role', ['admin','editor']);

// The action is authorized...
```
Contoh dengan kondisi if :
```php
if (! Gate::allows('role', 'admin')) {
  abort(403);
}

// The action is authorized...
```
Contoh dengan kondisi if, role lebih dari satu :
```php
if (! Gate::allows('role', ['admin','editor'])) {
  abort(403);
}

// The action is authorized...
```