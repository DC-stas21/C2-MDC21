<?php

namespace Database\Seeders;

use App\Models\Policy;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            // Global policies (apply to all assets)
            [
                'scope' => 'global',
                'type' => 'tone',
                'content' => 'El tono debe ser profesional, cercano y educativo. Nunca agresivo comercialmente. Evitar superlativos ("el mejor", "único", "garantizado"). Siempre usar "tú" informal, nunca "usted".',
                'metadata' => ['priority' => 'high'],
            ],
            [
                'scope' => 'global',
                'type' => 'legal',
                'content' => 'Nunca dar asesoramiento financiero directo. Siempre incluir disclaimer: "Esta información es orientativa y no constituye asesoramiento financiero profesional." No prometer resultados ni rentabilidades.',
                'metadata' => ['priority' => 'critical'],
            ],
            [
                'scope' => 'global',
                'type' => 'seo',
                'content' => 'Todos los artículos deben incluir: título H1 con keyword principal, al menos 3 subtítulos H2, fuentes verificables, fecha de actualización, nombre de autor. Extensión mínima 800 palabras.',
                'metadata' => ['priority' => 'medium'],
            ],
            [
                'scope' => 'global',
                'type' => 'brand',
                'content' => 'No mencionar competidores por nombre. No usar claims no verificables. No incluir testimonios falsos o inventados. Toda estadística debe citar su fuente.',
                'metadata' => ['priority' => 'high'],
            ],
            [
                'scope' => 'global',
                'type' => 'privacy',
                'content' => 'Nunca exponer datos personales de leads. Los ejemplos deben usar datos ficticios. Cumplir RGPD en todo contenido que implique datos personales.',
                'metadata' => ['priority' => 'critical'],
            ],

            // Asset-specific policies
            [
                'scope' => 'calculahipoteca.es',
                'type' => 'content',
                'content' => 'Contenido enfocado en hipotecas españolas. Siempre referenciar datos del Banco de España, INE o BCE. Incluir simulaciones con tipos reales del mercado. Advertir sobre la variabilidad del Euríbor.',
                'metadata' => ['priority' => 'high'],
            ],
            [
                'scope' => 'comparaenergia.es',
                'type' => 'content',
                'content' => 'Comparativas basadas en datos CNMC. Incluir simulaciones de ahorro con consumo medio español (3.500 kWh/año). No favorecer ninguna compañía eléctrica específica.',
                'metadata' => ['priority' => 'high'],
            ],
            [
                'scope' => 'segurobarato.net',
                'type' => 'content',
                'content' => 'No prometer precios. Usar rangos y franjas. Siempre recomendar que comparen varias opciones. Explicar coberturas y exclusiones con claridad.',
                'metadata' => ['priority' => 'high'],
            ],
            [
                'scope' => 'prestamoya.com',
                'type' => 'content',
                'content' => 'Obligatorio explicar TAE, no solo TIN. Advertir sobre endeudamiento excesivo. Incluir calculadora TAE en artículos de préstamos. Nunca promover préstamos rápidos de alto interés.',
                'metadata' => ['priority' => 'critical'],
            ],
        ];

        foreach ($policies as $policy) {
            Policy::create(array_merge($policy, ['is_active' => true]));
        }
    }
}
