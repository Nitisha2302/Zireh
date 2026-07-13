<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('admin.terms_conditions') }} - {{ company_name() }}</title>

    @include('layouts.partials.style')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: #f8fafc;
        }

        .hero-section {
            background: linear-gradient(135deg, #696cff 0%, #5f61e6 100%);
            color: white;
            padding: 80px 0;
        }

        .content-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }

        .content-area {
            line-height: 1.9;
            font-size: 15px;
            color: #566a7f;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            margin-top: 60px;
        }

        .terms-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
        <div class="container">

            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/">
                @if (company_logo_url())
                    <img src="{{ company_logo_url() }}" alt="{{ company_name() }}" style="height: 32px; width: auto; object-fit: contain;">
                @endif
                {{ company_name() }}
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMenu">

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('privacy-policy') }}">
                            Privacy Policy
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('terms-conditions') }}">
                            Terms & Conditions
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('delete-account') }}">
                            Delete Account
                        </a>
                    </li>

                </ul>

            </div>

        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-section">

        <div class="container text-center">

            <div class="terms-icon">
                📜
            </div>

            <h1 class="display-5 fw-bold">
                Terms & Conditions
            </h1>

            <p class="lead mb-0 opacity-75">
                Please read these terms carefully before using our services.
            </p>

        </div>

    </section>

    <!-- Content -->
    <section class="pb-5">

        <div class="container">

            <div class="card content-card">

                <div class="card-body p-4 p-md-5">

                    <div class="content-area">

                        {!! nl2br(e($content)) !!}

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- Footer -->
    <footer class="footer bg-white">

        <div class="container py-4">

            <div class="row align-items-center">

                <div class="col-md-6 text-center text-md-start">
                    <strong>{{ company_name() }}</strong>
                </div>

                <div class="col-md-6 text-center text-md-end">

                    <a href="{{ route('privacy-policy') }}"
                        class="text-decoration-none me-3">
                        Privacy Policy
                    </a>

                    <a href="{{ route('terms-conditions') }}"
                        class="text-decoration-none me-3">
                        Terms & Conditions
                    </a>

                    <a href="{{ route('delete-account') }}"
                        class="text-decoration-none">
                        Delete Account
                    </a>

                </div>

            </div>

            <div class="text-center mt-3 text-muted small">
                © {{ date('Y') }} {{ company_name() }}. All rights reserved.
            </div>

        </div>

    </footer>

    @include('layouts.partials.scripts')

</body>

</html>