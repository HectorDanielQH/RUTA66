<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Menu publico de Ruta 66: hamburguesas, pedidos para recoger y delivery.">
    <title>Ruta 66 | Hamburguesas y Delivery</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-shell">
    @php
        $productImage = fn ($path) => $path ? asset('storage/' . $path) : null;
        $logo = asset('images/logo-web.png');
    @endphp

    <header class="site-header site-header--club">
        <a class="brand-mark" href="#inicio" aria-label="Ir al inicio">
            <img class="brand-mark__logo" src="{{ $logo }}" alt="Logo Ruta 66">
            <span class="brand-mark__text">
                <strong>Ruta 66</strong>
                <small>neon burger club</small>
            </span>
        </a>

        <nav class="site-nav" aria-label="Navegacion principal">
            <a href="#menu"><span>01</span> Menu</a>
            <a href="#delivery"><span>02</span> Delivery</a>
            <a href="#whatsapp"><span>03</span> WhatsApp</a>
            <a href="{{ url('/admin') }}"><span>Admin</span> Panel</a>
        </nav>
    </header>

    <main id="inicio">
        <section class="hero-section hero-section--club">
            <div class="hero-orb hero-orb--red" aria-hidden="true"></div>
            <div class="hero-orb hero-orb--cyan" aria-hidden="true"></div>

            <div class="hero-section__copy">
                <p class="eyebrow">Ruta 66 neon burger club</p>
                <h1>
                    <span>Hamburguesas</span>
                    <span>que brillan</span>
                    <span>hasta tarde.</span>
                </h1>
                <p class="hero-section__lead">
                    Una carta visual, sabrosa y directa para pedir desde el celular sin perder tiempo.
                </p>
                <div class="hero-section__actions">
                    <a class="button button--primary" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Pedir ahora</a>
                    <a class="button button--ghost" href="#menu">Explorar menu</a>
                </div>
                <div class="hero-stats" aria-label="Beneficios de Ruta 66">
                    <span><strong>Fresh</strong> Preparado al momento</span>
                    <span><strong>Fast</strong> Local y delivery</span>
                    <span><strong>66</strong> Sabor urbano</span>
                </div>
            </div>

            <div class="hero-visual" aria-label="Ruta 66 Burger Club">
                <div class="hero-logo-stage">
                    <img src="{{ $logo }}" alt="Logo Ruta 66">
                    <span class="orbit-badge orbit-badge--one">Delivery</span>
                    <span class="orbit-badge orbit-badge--two">Pickup</span>
                    <span class="orbit-badge orbit-badge--three">Hot burgers</span>
                </div>
                <div class="hero-mini-card">
                    <span>Nuevo pedido</span>
                    <strong>Listo para confirmar</strong>
                </div>
            </div>

            <div class="hero-marquee" aria-hidden="true">
                <div>
                    <span>Delivery</span>
                    <span>Hamburguesas</span>
                    <span>Ruta 66</span>
                    <span>WhatsApp</span>
                    <span>Para recoger</span>
                    <span>Delivery</span>
                    <span>Hamburguesas</span>
                    <span>Ruta 66</span>
                </div>
            </div>
        </section>

        @if ($featuredProducts->isNotEmpty())
            <section class="featured-section" aria-labelledby="destacados-title">
                <div class="section-heading">
                    <p class="eyebrow">Favoritos</p>
                    <h2 id="destacados-title">Los destacados de la ruta</h2>
                </div>

                <div class="featured-grid">
                    @foreach ($featuredProducts as $product)
                        <article class="featured-card">
                            @if ($productImage($product->image))
                                <img src="{{ $productImage($product->image) }}" alt="{{ $product->name }}">
                            @else
                                <div class="image-fallback">Ruta 66</div>
                            @endif
                            <div>
                                <h3>{{ $product->name }}</h3>
                                <p>{{ $product->description ?: 'Producto destacado del menu.' }}</p>
                            </div>
                            <strong>Bs {{ \Illuminate\Support\Number::format((float) $product->price, 2) }}</strong>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="menu-section" id="menu" aria-labelledby="menu-title">
            <div class="section-heading section-heading--split">
                <div>
                    <p class="eyebrow">Menu</p>
                    <h2 id="menu-title">Elige tu parada</h2>
                </div>
                <p>Los productos se muestran desde el inventario activo del panel.</p>
            </div>

            @forelse ($categories as $category)
                <div class="category-block">
                    <div class="category-block__title">
                        @if ($productImage($category->image))
                            <img src="{{ $productImage($category->image) }}" alt="{{ $category->name }}">
                        @endif
                        <div>
                            <h3>{{ $category->name }}</h3>
                            @if ($category->description)
                                <p>{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="product-grid">
                        @foreach ($category->products as $product)
                            <article class="product-card">
                                @if ($productImage($product->image))
                                    <img src="{{ $productImage($product->image) }}" alt="{{ $product->name }}">
                                @else
                                    <div class="image-fallback image-fallback--small">66</div>
                                @endif
                                <div class="product-card__body">
                                    <div>
                                        <h4>{{ $product->name }}</h4>
                                        <p>{{ $product->description ?: 'Disponible para pedido.' }}</p>
                                    </div>
                                    <div class="product-card__footer">
                                        <strong>Bs {{ \Illuminate\Support\Number::format((float) $product->price, 2) }}</strong>
                                        <span class="{{ $product->stock > 0 ? 'stock-pill' : 'stock-pill stock-pill--empty' }}">
                                            {{ $product->stock > 0 ? 'Disponible' : 'Agotado' }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <strong>Menu en preparacion</strong>
                    <p>Cuando actives productos y categorias en el panel, apareceran aqui automaticamente.</p>
                </div>
            @endforelse
        </section>

        <section class="delivery-section" id="delivery" aria-labelledby="delivery-title">
            <div class="delivery-section__copy">
                <p class="eyebrow">Delivery</p>
                <h2 id="delivery-title">Tu pedido sale con zona, referencia y costo de envio.</h2>
                <p>
                    Para pedir, el cliente envia su nombre, telefono, direccion, referencia y productos. El cajero lo registra en el panel y genera el ticket.
                </p>
            </div>

            <div class="zone-list">
                @forelse ($deliveryZones as $zone)
                    <article class="zone-card">
                        <span>{{ $zone->estimated_time_minutes ? $zone->estimated_time_minutes . ' min' : 'Consultar' }}</span>
                        <h3>{{ $zone->name }}</h3>
                        <p>{{ $zone->description ?: 'Zona habilitada para delivery.' }}</p>
                        <strong>Envio Bs {{ \Illuminate\Support\Number::format((float) $zone->fee, 2) }}</strong>
                    </article>
                @empty
                    <article class="zone-card">
                        <span>Pronto</span>
                        <h3>Zonas por confirmar</h3>
                        <p>Las zonas activas del panel apareceran en esta seccion.</p>
                        <strong>Consulta en caja</strong>
                    </article>
                @endforelse
            </div>
        </section>

        <section class="whatsapp-section" id="whatsapp" aria-labelledby="whatsapp-title">
            <div>
                <p class="eyebrow">Haz tu pedido</p>
                <h2 id="whatsapp-title">Contactanos por WhatsApp</h2>
                <p>Envia tu nombre, productos, direccion y referencia. Te confirmamos el total y el tiempo estimado.</p>
            </div>
            <a class="whatsapp-number" href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Contactar por WhatsApp">
                {{ $whatsappDisplay }}
            </a>
        </section>
    </main>

    <footer class="site-footer">
        <strong>Ruta 66</strong>
        <span>Menu publico conectado al panel administrativo.</span>
    </footer>
</body>
</html>
