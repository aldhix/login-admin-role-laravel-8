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
