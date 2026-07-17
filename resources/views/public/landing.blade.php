<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ZirehCargo helps customers manage cargo, shopping, and shipping from China to Tajikistan.">

    <title>ZirehCargo | China to Tajikistan Cargo Service</title>

    @include('layouts.partials.style')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --zc-primary: #2563eb;
            --zc-primary-dark: #1e40af;
            --zc-accent: #f59e0b;
            --zc-ink: #101828;
            --zc-muted: #667085;
            --zc-soft: #f4f7fb;
            --zc-card: rgba(255, 255, 255, 0.86);
        }

        body {
            background:
                radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.14), transparent 28rem),
                radial-gradient(circle at 90% 0%, rgba(245, 158, 11, 0.18), transparent 24rem),
                linear-gradient(180deg, #ffffff 0%, #f7f9fc 48%, #ffffff 100%);
            color: var(--zc-ink);
            font-family: "Public Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .zc-navbar {
            backdrop-filter: blur(18px);
            background: rgba(255, 255, 255, 0.78);
            border-bottom: 1px solid rgba(16, 24, 40, 0.08);
        }

        .zc-brand-mark {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, var(--zc-primary), var(--zc-primary-dark));
            box-shadow: 0 16px 35px rgba(37, 99, 235, 0.28);
        }

        .zc-hero {
            min-height: 720px;
            display: flex;
            align-items: center;
            padding: 9rem 0 5rem;
        }

        .zc-eyebrow {
            display: inline-flex;
            gap: .5rem;
            align-items: center;
            padding: .55rem .85rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.09);
            color: var(--zc-primary-dark);
            font-weight: 700;
            font-size: .875rem;
        }

        .zc-title {
            font-size: clamp(2.6rem, 6vw, 5.6rem);
            line-height: .98;
            letter-spacing: -0.065em;
            font-weight: 800;
        }

        .zc-title span {
            color: var(--zc-primary);
        }

        .zc-lead {
            color: var(--zc-muted);
            font-size: 1.15rem;
            line-height: 1.75;
            max-width: 42rem;
        }

        .zc-btn-primary {
            background: linear-gradient(135deg, var(--zc-primary), var(--zc-primary-dark));
            border: 0;
            box-shadow: 0 18px 34px rgba(37, 99, 235, 0.28);
        }

        .zc-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 40px rgba(37, 99, 235, 0.34);
        }

        .zc-card {
            background: var(--zc-card);
            border: 1px solid rgba(16, 24, 40, 0.08);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(16, 24, 40, 0.08);
        }

        .zc-preview {
            position: relative;
            overflow: hidden;
            padding: 1rem;
        }

        .zc-preview::before {
            content: "";
            position: absolute;
            inset: -40% -20% auto auto;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.18);
        }

        .zc-stat {
            border-radius: 20px;
            background: rgba(244, 247, 251, 0.92);
            border: 1px solid rgba(16, 24, 40, 0.06);
        }

        .zc-section {
            padding: 5.5rem 0;
        }

        .zc-section-title {
            font-size: clamp(2rem, 4vw, 3.3rem);
            font-weight: 800;
            letter-spacing: -0.045em;
        }

        .zc-icon {
            width: 52px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            color: var(--zc-primary);
            background: rgba(37, 99, 235, 0.1);
        }

        .zc-step-number {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #fff;
            background: var(--zc-primary);
            font-weight: 800;
        }

        .zc-route-line {
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--zc-primary), var(--zc-accent));
        }

        .zc-footer {
            border-top: 1px solid rgba(16, 24, 40, 0.08);
            background: #fff;
        }

        @media (max-width: 991.98px) {
            .zc-hero {
                min-height: auto;
                padding-top: 7rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top zc-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ url('/') }}" aria-label="ZirehCargo home">
                <span class="zc-brand-mark">
                    <i class="icon-base ti tabler-truck-delivery"></i>
                </span>
                <span class="fs-4">ZirehCargo</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="landingNavbar">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <a class="nav-link" href="#how-it-works">How it works</a>
                    <a class="nav-link" href="#features">Benefits</a>
                    <a class="nav-link" href="#tracking">Tracking</a>
                    <a class="btn btn-primary zc-btn-primary ms-lg-2 px-4" href="{{ route('login') }}">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="zc-hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="zc-eyebrow mb-4">
                            <i class="icon-base ti tabler-plane-departure"></i>
                            China to Tajikistan cargo made simple
                        </span>
                        <h1 class="zc-title mb-4">
                            Ship smarter with <span>ZirehCargo</span>.
                        </h1>
                        <p class="zc-lead mb-4">
                            Manage purchases, cargo processing, warehouse movement, and delivery updates in one reliable system built for cross-border logistics.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg zc-btn-primary px-5">
                                Open Dashboard
                                <i class="icon-base ti tabler-arrow-right ms-2"></i>
                            </a>
                            <a href="#how-it-works" class="btn btn-label-secondary btn-lg px-5">See how it works</a>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="zc-card zc-preview">
                            <div class="position-relative">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <small class="text-body-secondary">Live shipment</small>
                                        <h5 class="mb-0">Order #ZC-48291</h5>
                                    </div>
                                    <span class="badge bg-label-success">In Transit</span>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="zc-stat p-3">
                                            <small class="text-body-secondary">Origin</small>
                                            <div class="fw-bold fs-5">Guangzhou</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="zc-stat p-3">
                                            <small class="text-body-secondary">Destination</small>
                                            <div class="fw-bold fs-5">Dushanbe</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="zc-route-line mb-4"></div>

                                <div class="list-group list-group-flush rounded-4 overflow-hidden">
                                    <div class="list-group-item d-flex align-items-center gap-3">
                                        <span class="zc-icon"><i class="icon-base ti tabler-building-warehouse"></i></span>
                                        <div>
                                            <div class="fw-semibold">China warehouse received</div>
                                            <small class="text-body-secondary">Items checked and prepared for cargo</small>
                                        </div>
                                    </div>
                                    <div class="list-group-item d-flex align-items-center gap-3">
                                        <span class="zc-icon"><i class="icon-base ti tabler-package-export"></i></span>
                                        <div>
                                            <div class="fw-semibold">Cargo dispatched</div>
                                            <small class="text-body-secondary">Shipment is moving toward Tajikistan</small>
                                        </div>
                                    </div>
                                    <div class="list-group-item d-flex align-items-center gap-3">
                                        <span class="zc-icon"><i class="icon-base ti tabler-map-pin-check"></i></span>
                                        <div>
                                            <div class="fw-semibold">Delivery tracking</div>
                                            <small class="text-body-secondary">Customer receives clear status updates</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="zc-section">
            <div class="container">
                <div class="text-center mx-auto mb-5" style="max-width: 720px;">
                    <h2 class="zc-section-title mb-3">How it works</h2>
                    <p class="zc-lead mx-auto">A clear flow from marketplace purchase to final delivery.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-step-number mb-4">1</span>
                            <h5>Submit order</h5>
                            <p class="text-body-secondary mb-0">Create or manage cargo orders with product and delivery details.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-step-number mb-4">2</span>
                            <h5>Warehouse check</h5>
                            <p class="text-body-secondary mb-0">China warehouse receives, verifies, and prepares parcels.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-step-number mb-4">3</span>
                            <h5>Ship cargo</h5>
                            <p class="text-body-secondary mb-0">Cargo moves through the configured shipping process.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-step-number mb-4">4</span>
                            <h5>Receive updates</h5>
                            <p class="text-body-secondary mb-0">Customers and operators track each order status clearly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="zc-section bg-white">
            <div class="container">
                <div class="row align-items-end mb-5 g-4">
                    <div class="col-lg-7">
                        <h2 class="zc-section-title mb-3">Why choose ZirehCargo?</h2>
                        <p class="zc-lead mb-0">Built for the teams and customers who need dependable cargo handling between China and Tajikistan.</p>
                    </div>
                    <div class="col-lg-5 text-lg-end">
                        <a href="{{ route('login') }}" class="btn btn-primary zc-btn-primary px-4">Access Panel</a>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-icon mb-4"><i class="icon-base ti tabler-shield-check"></i></span>
                            <h5>Reliable operations</h5>
                            <p class="text-body-secondary mb-0">Structured order, warehouse, shipping, and pickup workflows.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-icon mb-4"><i class="icon-base ti tabler-bell-ringing"></i></span>
                            <h5>Status clarity</h5>
                            <p class="text-body-secondary mb-0">Keep cargo movement transparent from intake to completion.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="zc-card h-100 p-4">
                            <span class="zc-icon mb-4"><i class="icon-base ti tabler-world"></i></span>
                            <h5>Marketplace friendly</h5>
                            <p class="text-body-secondary mb-0">Designed around Chinese marketplace sourcing and logistics.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="tracking" class="zc-section">
            <div class="container">
                <div class="zc-card p-4 p-lg-5">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <h2 class="zc-section-title mb-3">Track and manage orders with confidence.</h2>
                            <p class="zc-lead mb-4">ZirehCargo gives operators a clean control center for customers, orders, warehouses, wallets, shipping rates, and delivery progress.</p>
                            <a href="{{ route('login') }}" class="btn btn-primary zc-btn-primary btn-lg px-5">Login to continue</a>
                        </div>
                        <div class="col-lg-6">
                            <div class="bg-white rounded-4 p-4 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <small class="text-body-secondary">Dashboard preview</small>
                                        <h5 class="mb-0">Cargo overview</h5>
                                    </div>
                                    <span class="badge bg-label-primary">Today</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="zc-stat p-3">
                                            <small class="text-body-secondary">Orders</small>
                                            <div class="fs-3 fw-bold">128</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="zc-stat p-3">
                                            <small class="text-body-secondary">Warehouses</small>
                                            <div class="fs-3 fw-bold">2</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="zc-stat p-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="fw-semibold">China → Tajikistan route</span>
                                                <span class="text-success fw-semibold">72%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" style="width: 72%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="pb-5">
            <div class="container">
                <div class="zc-card text-center p-5">
                    <h2 class="zc-section-title mb-3">Ready to manage your cargo?</h2>
                    <p class="zc-lead mx-auto mb-4">Open the ZirehCargo panel and continue managing logistics with a focused workflow.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary zc-btn-primary btn-lg px-5">Go to Login</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="zc-footer py-4">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="text-body-secondary">
                    © {{ now()->year }} ZirehCargo. All rights reserved.
                </div>
                <div class="d-flex gap-4">
                    <a href="{{ route('privacy-policy') }}" class="text-body-secondary">Privacy Policy</a>
                    <a href="{{ route('terms-conditions') }}" class="text-body-secondary">Terms & Conditions</a>
                </div>
            </div>
        </div>
    </footer>

    @include('layouts.partials.scripts')
</body>

</html>
