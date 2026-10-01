<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.alif_return_title') }} — {{ company_name() }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; margin: 0; min-height: 100vh; display: grid; place-items: center; color: #334155; }
        .card { background: #fff; border-radius: 16px; padding: 2rem; max-width: 28rem; text-align: center; box-shadow: 0 4px 24px rgba(15, 23, 42, .08); }
        p { line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('admin.alif_return_title') }}</h1>
        <p>{{ __('admin.alif_return_body') }}</p>
    </div>
</body>
</html>
