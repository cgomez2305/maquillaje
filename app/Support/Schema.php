<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Datos estructurados JSON-LD (§12.3). Nunca se marcan reseñas que no estén visibles.
 */
final class Schema
{
    public static function businessId(): string
    {
        return url('/') . '#negocio';
    }

    /** BeautySalon (subtipo de LocalBusiness) para todo el sitio. */
    public static function business(): array
    {
        $b = config('app.business');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BeautySalon',
            '@id' => self::businessId(),
            'name' => 'Make Up by Fletcher',
            'alternateName' => 'Fletcher · Make up artist',
            'url' => url('/'),
            'logo' => url('/assets/img/marca/logo-salmon-700.png'),
            'image' => [
                url('/assets/img/og-default.jpg'),
                url('/assets/img/fotos/novia-tiara-ramo-1200.jpg'),
                url('/assets/img/fotos/social-cumple-pastel-1200.jpg'),
                url('/assets/img/fotos/proceso-rubor-novia-1200.jpg'),
            ],
            'hasMap' => $b['maps_url'],
            'description' => 'Maquillaje profesional con acabado natural y duradero para novias, quinceañeras, grados y eventos en Bucaramanga y Floridablanca.',
            'telephone' => $b['phone_e164'],
            'email' => $b['email'],
            'founder' => ['@type' => 'Person', 'name' => $b['owner']],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $b['address'],
                'addressLocality' => $b['city'],
                'addressRegion' => $b['region'],
                'postalCode' => $b['postal_code'],
                'addressCountry' => $b['country'],
            ],
            'areaServed' => array_map(static fn(string $c): array => ['@type' => 'City', 'name' => $c], $b['area_served']),
            'priceRange' => $b['price_range'],
            'currenciesAccepted' => 'COP',
            'sameAs' => [$b['instagram'], $b['tiktok'], $b['facebook']],
        ];
        if (is_array($b['geo']) && count($b['geo']) === 2) {
            $data['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $b['geo'][0], 'longitude' => $b['geo'][1]];
        }
        if (!empty($b['opening_hours'])) {
            $data['openingHoursSpecification'] = array_map(static fn(array $h): array => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $h['days'],
                'opens' => $h['opens'],
                'closes' => $h['closes'],
            ], $b['opening_hours']);
        }
        return $data;
    }

    /** @param list<array{0:string,1:?string}> $crumbs */
    public static function breadcrumbs(array $crumbs, ?string $currentPath = null): array
    {
        $items = [];
        foreach ($crumbs as $i => [$label, $href]) {
            $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label];
            if ($href !== null) {
                $item['item'] = url($href);
            }
            $items[] = $item;
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /** @param list<array{q:string,a:string}> $items */
    public static function faq(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn(array $i): array => [
                '@type' => 'Question',
                'name' => $i['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => str_replace("\n\n", ' ', $i['a'])],
            ], $items),
        ];
    }

    public static function service(string $name, string $description, int $price, string $path, string $serviceType = 'Maquillaje profesional'): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $name,
            'serviceType' => $serviceType,
            'description' => $description,
            'provider' => ['@id' => self::businessId()],
            'areaServed' => array_map(static fn(string $c): array => ['@type' => 'City', 'name' => $c], config('app.business.area_served')),
            'url' => url($path),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) $price,
                'priceCurrency' => 'COP',
                'availability' => 'https://schema.org/InStock',
                'url' => url($path),
            ],
        ];
    }

    public static function course(string $name, string $description, int $price, string $path): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $name,
            'description' => $description,
            'inLanguage' => 'es',
            'provider' => ['@type' => 'Organization', 'name' => 'Make Up by Fletcher', 'sameAs' => url('/')],
            'offers' => [
                '@type' => 'Offer',
                'category' => 'Paid',
                'price' => (string) $price,
                'priceCurrency' => 'COP',
                'url' => url($path),
                'availability' => 'https://schema.org/InStock',
            ],
            'hasCourseInstance' => [
                '@type' => 'CourseInstance',
                'courseMode' => 'Onsite',
                'location' => [
                    '@type' => 'Place',
                    'name' => 'Estudio Make Up by Fletcher',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => config('app.business.address'),
                        'addressLocality' => config('app.business.city'),
                        'addressRegion' => config('app.business.region'),
                        'addressCountry' => 'CO',
                    ],
                ],
            ],
        ];
    }

    public static function product(string $name, string $description, int $price, string $path, ?string $image = null, bool $inStock = true): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $description,
            'brand' => ['@type' => 'Brand', 'name' => 'Make Up by Fletcher'],
            'url' => url($path),
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) $price,
                'priceCurrency' => 'COP',
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => url($path),
                'seller' => ['@id' => self::businessId()],
            ],
        ];
        if ($image !== null) {
            $data['image'] = url($image);
        }
        return $data;
    }

    public static function blogPosting(array $post, string $path, ?string $image): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => $post['meta_description'],
            'inLanguage' => 'es-CO',
            'mainEntityOfPage' => url($path),
            'author' => ['@type' => 'Person', 'name' => 'María Paula Fletcher', 'url' => url('/estudio-maquillaje-floridablanca')],
            'publisher' => ['@id' => self::businessId()],
            'datePublished' => $post['published_at'] ? gmdate('c', strtotime($post['published_at'] . ' UTC')) : null,
            'dateModified' => gmdate('c', strtotime(($post['updated_at'] ?? $post['published_at']) . ' UTC')),
            'keywords' => $post['target_keyword'],
            'articleSection' => $post['category'],
        ];
        if ($image !== null) {
            $data['image'] = url($image);
        }
        return $data;
    }

    /** <link rel=preload> de la imagen LCP en AVIF con srcset. */
    public static function preloadImage(string $key, string $sizes): string
    {
        $file = BASE_PATH . '/public/assets/img/manifest.json';
        $m = is_file($file) ? (json_decode((string) file_get_contents($file), true)[$key] ?? null) : null;
        if ($m === null) {
            return '';
        }
        $type = $m['ext'] === 'jpg' ? 'avif' : 'webp';
        $set = implode(', ', array_map(static fn(int $w): string => '/assets/img/' . $key . '-' . $w . '.' . $type . ' ' . $w . 'w', $m['widths']));
        return '<link rel="preload" as="image" type="image/' . $type . '" imagesrcset="' . e($set) . '" imagesizes="' . e($sizes) . '" fetchpriority="high">';
    }
}
