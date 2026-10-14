<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class GenerateProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $dir = public_path('images/products');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $items = [
            1 => [
                'file' => 'aguila_330.svg',
                'bg' => ['#fef3c7', '#fde68a'],
                'bottle' => '#b45309',
                'label' => '#1d4ed8',
                'text' => 'ÁGUILA',
                'sub' => 'ORIGINAL 330ML',
                'icon' => '🦅',
            ],
            2 => [
                'file' => 'corona_355.svg',
                'bg' => ['#fef9c3', '#fef08a'],
                'bottle' => '#fef08a',
                'label' => '#1e3a8a',
                'text' => 'CORONA',
                'sub' => 'EXTRA 355ML',
                'icon' => '👑',
            ],
            3 => [
                'file' => 'poker_330.svg',
                'bg' => ['#fee2e2', '#fecaca'],
                'bottle' => '#92400e',
                'label' => '#dc2626',
                'text' => 'POKER',
                'sub' => 'CERVEZA 330ML',
                'icon' => '♠️',
            ],
            4 => [
                'file' => 'club_colombia_330.svg',
                'bg' => ['#fef3c7', '#fde68a'],
                'bottle' => '#78350f',
                'label' => '#b45309',
                'text' => 'CLUB COL.',
                'sub' => 'DORADA 330ML',
                'icon' => '🏺',
            ],
            5 => [
                'file' => 'heineken_330.svg',
                'bg' => ['#dcfce7', '#bbf7d0'],
                'bottle' => '#15803d',
                'label' => '#166534',
                'text' => 'HEINEKEN',
                'sub' => 'PREMIUM 330ML',
                'icon' => '⭐',
            ],
            6 => [
                'file' => 'antioqueno_azul_750.svg',
                'bg' => ['#e0f2fe', '#bae6fd'],
                'bottle' => '#0284c7',
                'label' => '#0369a1',
                'text' => 'ANTIOQUEÑO',
                'sub' => 'SIN AZÚCAR 750ML',
                'icon' => '🔷',
            ],
            7 => [
                'file' => 'antioqueno_rojo_750.svg',
                'bg' => ['#fee2e2', '#fecaca'],
                'bottle' => '#ef4444',
                'label' => '#b91c1c',
                'text' => 'ANTIOQUEÑO',
                'sub' => 'TRADICIONAL 750ML',
                'icon' => '🔴',
            ],
            8 => [
                'file' => 'amarillo_manzanares_750.svg',
                'bg' => ['#fef3c7', '#fde047'],
                'bottle' => '#eab308',
                'label' => '#ca8a04',
                'text' => 'AMARILLO',
                'sub' => 'MANZANARES 750ML',
                'icon' => '🟡',
            ],
            9 => [
                'file' => 'ron_medellin_750.svg',
                'bg' => ['#ffedd5', '#fed7aa'],
                'bottle' => '#c2410c',
                'label' => '#9a3412',
                'text' => 'RON MEDELLÍN',
                'sub' => '3 AÑOS 750ML',
                'icon' => '🥃',
            ],
            10 => [
                'file' => 'ron_caldas_750.svg',
                'bg' => ['#fef3c7', '#fed7aa'],
                'bottle' => '#b45309',
                'label' => '#78350f',
                'text' => 'VIEJO DE CALDAS',
                'sub' => '5 AÑOS 750ML',
                'icon' => '🏰',
            ],
            11 => [
                'file' => 'buchanans_750.svg',
                'bg' => ['#dcfce7', '#bbf7d0'],
                'bottle' => '#14532d',
                'label' => '#dc2626',
                'text' => "BUCHANAN'S",
                'sub' => '12 AÑOS 750ML',
                'icon' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
            ],
            12 => [
                'file' => 'old_parr_750.svg',
                'bg' => ['#fed7aa', '#fdba74'],
                'bottle' => '#7c2d12',
                'label' => '#ca8a04',
                'text' => 'OLD PARR',
                'sub' => '12 AÑOS 750ML',
                'icon' => '🥇',
            ],
            13 => [
                'file' => 'johnnie_black_750.svg',
                'bg' => ['#f1f5f9', '#e2e8f0'],
                'bottle' => '#1e293b',
                'label' => '#0f172a',
                'text' => 'JOHNNIE WALKER',
                'sub' => 'BLACK LABEL 750ML',
                'icon' => '🎩',
            ],
            14 => [
                'file' => 'smirnoff_750.svg',
                'bg' => ['#f8fafc', '#f1f5f9'],
                'bottle' => '#e2e8f0',
                'label' => '#dc2626',
                'text' => 'SMIRNOFF',
                'sub' => 'VODKA RED 750ML',
                'icon' => '❄️',
            ],
            15 => [
                'file' => 'don_julio_700.svg',
                'bg' => ['#fef9c3', '#fef08a'],
                'bottle' => '#3b82f6',
                'label' => '#1e3a8a',
                'text' => 'DON JULIO',
                'sub' => 'BLANCO 700ML',
                'icon' => '🌵',
            ],
            16 => [
                'file' => 'bombay_750.svg',
                'bg' => ['#e0f2fe', '#bae6fd'],
                'bottle' => '#0284c7',
                'label' => '#0369a1',
                'text' => 'BOMBAY',
                'sub' => 'SAPPHIRE GIN 750ML',
                'icon' => '💎',
            ],
            17 => [
                'file' => 'cocacola_400.svg',
                'bg' => ['#fee2e2', '#fecaca'],
                'bottle' => '#dc2626',
                'label' => '#991b1b',
                'text' => 'COCA-COLA',
                'sub' => 'ORIGINAL 400ML',
                'icon' => '🥤',
            ],
            18 => [
                'file' => 'ginger_ale_300.svg',
                'bg' => ['#dcfce7', '#bbf7d0'],
                'bottle' => '#16a34a',
                'label' => '#15803d',
                'text' => 'GINGER ALE',
                'sub' => 'CANADA DRY 300ML',
                'icon' => '🌿',
            ],
            19 => [
                'file' => 'agua_cristal_500.svg',
                'bg' => ['#e0f2fe', '#bae6fd'],
                'bottle' => '#38bdf8',
                'label' => '#0284c7',
                'text' => 'AGUA CRISTAL',
                'sub' => 'SIN GAS 500ML',
                'icon' => '💧',
            ],
            20 => [
                'file' => 'red_bull_250.svg',
                'bg' => ['#e0e7ff', '#c7d2fe'],
                'bottle' => '#4338ca',
                'label' => '#ef4444',
                'text' => 'RED BULL',
                'sub' => 'ENERGY DRINK 250ML',
                'icon' => '⚡',
            ],
            21 => [
                'file' => 'papas_margarita_110.svg',
                'bg' => ['#fef3c7', '#fde047'],
                'bottle' => '#eab308',
                'label' => '#ca8a04',
                'text' => 'MARGARITA',
                'sub' => 'PAPAS LIMÓN 110G',
                'icon' => '🥔',
            ],
            22 => [
                'file' => 'mani_la_especial_150.svg',
                'bg' => ['#ffedd5', '#fed7aa'],
                'bottle' => '#ea580c',
                'label' => '#c2410c',
                'text' => 'LA ESPECIAL',
                'sub' => 'MANÍ SALADO 150G',
                'icon' => '🥜',
            ],
        ];

        foreach ($items as $id => $data) {
            $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="240" height="240">
  <defs>
    <linearGradient id="bgGrad{$id}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$data['bg'][0]}" />
      <stop offset="100%" stop-color="{$data['bg'][1]}" />
    </linearGradient>
    <linearGradient id="bottleGrad{$id}" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="{$data['bottle']}" stop-opacity="0.85" />
      <stop offset="50%" stop-color="{$data['bottle']}" />
      <stop offset="100%" stop-color="{$data['bottle']}" stop-opacity="0.9" />
    </linearGradient>
    <filter id="shadow{$id}" x="-10%" y="-10%" width="120%" height="130%">
      <feDropShadow dx="0" dy="8" stdDeviation="6" flood-opacity="0.18" />
    </filter>
  </defs>

  <!-- Background -->
  <rect width="240" height="240" rx="32" fill="url(#bgGrad{$id})" />

  <!-- Bottle Silhouette Graphic with Shadow -->
  <g filter="url(#shadow{$id})" transform="translate(60, 20)">
    <!-- Cap / Neck -->
    <rect x="52" y="10" width="16" height="12" rx="3" fill="#334155" />
    <path d="M 50 22 C 50 22, 44 48, 30 70 L 90 70 C 76 48, 70 22, 70 22 Z" fill="url(#bottleGrad{$id})" />
    <!-- Body -->
    <rect x="25" y="68" width="70" height="120" rx="14" fill="url(#bottleGrad{$id})" />
    <!-- Label -->
    <rect x="27" y="95" width="66" height="55" rx="6" fill="{$data['label']}" />
    <!-- Label text/emblem -->
    <text x="60" y="118" font-family="system-ui, -apple-system, sans-serif" font-weight="900" font-size="20" text-anchor="middle" fill="#ffffff">{$data['icon']}</text>
    <text x="60" y="138" font-family="system-ui, -apple-system, sans-serif" font-weight="800" font-size="9" text-anchor="middle" fill="#ffffff" letter-spacing="1">PREMIUM</text>
  </g>

  <!-- Bottom Brand Ribbon Banner -->
  <rect x="16" y="184" width="208" height="42" rx="14" fill="#0f172a" opacity="0.92" />
  <text x="120" y="202" font-family="system-ui, -apple-system, sans-serif" font-weight="900" font-size="11" text-anchor="middle" fill="#ffffff" letter-spacing="0.5">{$data['text']}</text>
  <text x="120" y="217" font-family="system-ui, -apple-system, sans-serif" font-weight="700" font-size="8.5" text-anchor="middle" fill="#94a3b8" letter-spacing="0.5">{$data['sub']}</text>
</svg>
SVG;

            $filePath = $dir . DIRECTORY_SEPARATOR . $data['file'];
            File::put($filePath, $svg);

            $relativeImagePath = 'images/products/' . $data['file'];
            Product::where('id', $id)->update(['image' => $relativeImagePath]);
        }
    }
}
