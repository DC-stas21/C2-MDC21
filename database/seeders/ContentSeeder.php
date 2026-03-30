<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            ['title' => 'Cómo calcular tu hipoteca en 2026: guía completa', 'asset' => 'calculahipoteca.es', 'author' => 'MDC21 Editorial', 'status' => 'published', 'methodology' => 'Análisis de datos del Banco de España y simulaciones propias'],
            ['title' => 'Hipoteca fija vs variable: qué elegir según tu perfil', 'asset' => 'calculahipoteca.es', 'author' => 'MDC21 Editorial', 'status' => 'published', 'methodology' => 'Comparativa basada en tipos de interés históricos BCE'],
            ['title' => 'Los 5 errores más comunes al pedir una hipoteca', 'asset' => 'calculahipoteca.es', 'author' => 'MDC21 Editorial', 'status' => 'draft', 'methodology' => null],
            ['title' => 'Comparativa tarifas eléctricas 2026: mercado regulado vs libre', 'asset' => 'comparaenergia.es', 'author' => 'MDC21 Editorial', 'status' => 'published', 'methodology' => 'Datos CNMC y simulaciones de consumo medio español'],
            ['title' => 'Energía solar para hogares: rentabilidad real en España', 'asset' => 'comparaenergia.es', 'author' => 'MDC21 Editorial', 'status' => 'published', 'methodology' => 'ROI calculado con datos IDAE y precios de instalación'],
            ['title' => 'Cómo cambiar de compañía eléctrica sin sorpresas', 'asset' => 'comparaenergia.es', 'author' => 'MDC21 Editorial', 'status' => 'pending_review', 'methodology' => 'Guía paso a paso verificada con CNMC'],
            ['title' => 'Seguro de hogar 2026: qué cubrir y qué no', 'asset' => 'segurobarato.net', 'author' => 'MDC21 Editorial', 'status' => 'published', 'methodology' => 'Análisis de 50 pólizas del mercado español'],
            ['title' => 'Seguros de vida: ¿merece la pena contratar uno joven?', 'asset' => 'segurobarato.net', 'author' => 'MDC21 Editorial', 'status' => 'draft', 'methodology' => null],
            ['title' => 'Guía de préstamos personales: tipos, requisitos y alternativas', 'asset' => 'prestamoya.com', 'author' => 'MDC21 Editorial', 'status' => 'published', 'methodology' => 'Datos de Banco de España sobre crédito al consumo'],
            ['title' => 'Reunificación de deudas: cuándo tiene sentido y cuándo no', 'asset' => 'prestamoya.com', 'author' => 'MDC21 Editorial', 'status' => 'pending_review', 'methodology' => 'Análisis de casos reales anonimizados'],
            ['title' => 'TAE vs TIN: la diferencia que nadie te explica bien', 'asset' => 'prestamoya.com', 'author' => 'MDC21 Editorial', 'status' => 'draft', 'methodology' => null],
        ];

        foreach ($posts as $i => $post) {
            BlogPost::create([
                'title' => $post['title'],
                'slug' => Str::slug($post['title']),
                'author' => $post['author'],
                'body' => 'Contenido del artículo pendiente de generación por el agente SEO.',
                'sources' => ['Banco de España', 'CNMC', 'INE'],
                'methodology' => $post['methodology'],
                'status' => $post['status'],
                'asset' => $post['asset'],
                'published_at' => $post['status'] === 'published' ? now()->subDays(rand(1, 30)) : null,
                'created_at' => now()->subDays(rand(1, 45)),
            ]);
        }
    }
}
