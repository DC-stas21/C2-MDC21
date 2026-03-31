<?php

namespace App\Services;

use App\Models\NicheConfig;

class WebConfigTemplateService
{
    /**
     * Generate a complete site.config.json for a niche, without needing AI.
     */
    public function generateForNiche(NicheConfig $niche): array
    {
        $context = $niche->config ?? [];
        $colors = $niche->colors ?? [];
        $description = $context['description'] ?? "Web sobre {$niche->vertical}";
        $audience = $context['target_audience'] ?? 'público general';
        $tone = $context['tone'] ?? 'profesional y cercano';
        $keywords = $context['keywords'] ?? $niche->vertical;

        $design = $this->generateDesign($niche->vertical, $colors);
        $tool = $this->getToolForVertical($niche->vertical, $niche->domain);
        $pages = $this->generatePages($niche, $description, $tool);
        $navigation = $this->generateNavigation($pages);

        return [
            'meta' => [
                'domain' => $niche->domain,
                'vertical' => $niche->vertical,
                'language' => 'es',
                'title' => $this->generateTitle($niche->vertical, $niche->domain),
                'description' => $description,
            ],
            'design' => $design,
            'navigation' => $navigation,
            'pages' => $pages,
            'footer' => [
                'copyright' => '© '.date('Y').' '.$niche->domain,
                'links' => [
                    ['label' => 'Aviso Legal', 'slug' => '/aviso-legal'],
                    ['label' => 'Privacidad', 'slug' => '/privacidad'],
                    ['label' => 'Cookies', 'slug' => '/cookies'],
                ],
                'disclaimer' => 'Esta información es orientativa y no constituye asesoramiento profesional.',
            ],
            'ads' => [
                'adsense_id' => config('services.adsense.client_id', ''),
                'auto_ads' => true,
            ],
            'blog' => [
                'articles' => [],
            ],
            'tools' => [
                'lead_form' => [
                    'enabled' => false,
                    'endpoint' => '',
                    'fields' => ['name', 'email', 'phone'],
                    'asset_domain' => $niche->domain,
                ],
            ],
            'build' => [
                'generated_at' => now()->toIso8601String(),
                'generator' => 'template_service',
                'config_version' => 1,
            ],
        ];
    }

    private function generateDesign(string $vertical, array $colors): array
    {
        $verticalColors = [
            'Hipotecas' => ['primary' => '#1e40af', 'secondary' => '#3b82f6', 'accent' => '#f59e0b'],
            'Energía' => ['primary' => '#15803d', 'secondary' => '#22c55e', 'accent' => '#eab308'],
            'Seguros' => ['primary' => '#7c3aed', 'secondary' => '#a78bfa', 'accent' => '#06b6d4'],
            'Préstamos' => ['primary' => '#dc2626', 'secondary' => '#f87171', 'accent' => '#2563eb'],
            'Solar' => ['primary' => '#ea580c', 'secondary' => '#f97316', 'accent' => '#16a34a'],
            'Ciberseguridad' => ['primary' => '#0f172a', 'secondary' => '#334155', 'accent' => '#22d3ee'],
            'Contabilidad' => ['primary' => '#1e3a5f', 'secondary' => '#3b82f6', 'accent' => '#10b981'],
        ];

        $defaults = $verticalColors[$vertical] ?? ['primary' => '#4f46e5', 'secondary' => '#0ea5e9', 'accent' => '#f59e0b'];

        return [
            'colors' => [
                'primary' => $colors['primary'] ?? $defaults['primary'],
                'secondary' => $colors['secondary'] ?? $defaults['secondary'],
                'accent' => $defaults['accent'],
                'background' => '#ffffff',
                'surface' => '#f8fafc',
                'text' => '#1e293b',
                'text_muted' => '#64748b',
            ],
            'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
            'border_radius' => '0.5rem',
            'style' => 'modern_clean',
        ];
    }

    private function getToolForVertical(string $vertical, string $domain): array
    {
        return match ($vertical) {
            'Hipotecas' => [
                'page_title' => 'Calculadora de Hipotecas',
                'tool_type' => 'calculator',
                'tool_config' => [
                    'calculator_type' => 'mortgage',
                    'fields' => [
                        ['name' => 'amount', 'label' => 'Importe del préstamo (€)', 'type' => 'currency', 'min' => 20000, 'max' => 800000, 'default' => 150000],
                        ['name' => 'years', 'label' => 'Plazo (años)', 'type' => 'slider', 'min' => 5, 'max' => 40, 'default' => 25],
                        ['name' => 'rate', 'label' => 'Tipo de interés (%)', 'type' => 'number', 'min' => 0.5, 'max' => 10, 'step' => 0.1, 'default' => 3.5],
                    ],
                    'formula' => 'french_amortization',
                    'output_fields' => ['monthly_payment', 'total_interest', 'total_cost'],
                    'disclaimer' => 'Cálculo orientativo basado en el sistema de amortización francés. No constituye oferta vinculante.',
                ],
            ],
            'Energía' => [
                'page_title' => 'Comparador de Tarifas',
                'tool_type' => 'calculator',
                'tool_config' => [
                    'calculator_type' => 'energy',
                    'fields' => [
                        ['name' => 'consumption', 'label' => 'Consumo anual (kWh)', 'type' => 'slider', 'min' => 500, 'max' => 10000, 'default' => 3500],
                        ['name' => 'current_price', 'label' => 'Precio actual (€/kWh)', 'type' => 'number', 'min' => 0.05, 'max' => 0.40, 'step' => 0.01, 'default' => 0.15],
                        ['name' => 'new_price', 'label' => 'Precio nuevo (€/kWh)', 'type' => 'number', 'min' => 0.05, 'max' => 0.40, 'step' => 0.01, 'default' => 0.12],
                    ],
                    'formula' => 'energy_savings',
                    'output_fields' => ['annual_savings', 'monthly_savings'],
                    'disclaimer' => 'Estimación basada en consumo constante. Los precios pueden variar según mercado.',
                ],
            ],
            'Préstamos' => [
                'page_title' => 'Calculadora de Préstamos',
                'tool_type' => 'calculator',
                'tool_config' => [
                    'calculator_type' => 'loan',
                    'fields' => [
                        ['name' => 'amount', 'label' => 'Importe (€)', 'type' => 'currency', 'min' => 1000, 'max' => 60000, 'default' => 10000],
                        ['name' => 'months', 'label' => 'Plazo (meses)', 'type' => 'slider', 'min' => 6, 'max' => 84, 'default' => 36],
                        ['name' => 'tin', 'label' => 'TIN (%)', 'type' => 'number', 'min' => 1, 'max' => 25, 'step' => 0.1, 'default' => 7],
                    ],
                    'formula' => 'loan_tae',
                    'output_fields' => ['monthly_payment', 'total_interest', 'tae'],
                    'disclaimer' => 'El TAE puede variar según comisiones y productos vinculados. Consulta las condiciones con tu entidad.',
                ],
            ],
            'Solar' => [
                'page_title' => 'Calculadora Solar',
                'tool_type' => 'calculator',
                'tool_config' => [
                    'calculator_type' => 'solar',
                    'fields' => [
                        ['name' => 'install_cost', 'label' => 'Coste instalación (€)', 'type' => 'currency', 'min' => 2000, 'max' => 20000, 'default' => 6000],
                        ['name' => 'annual_savings', 'label' => 'Ahorro anual estimado (€)', 'type' => 'currency', 'min' => 200, 'max' => 3000, 'default' => 900],
                        ['name' => 'subsidy_pct', 'label' => 'Subvención (%)', 'type' => 'slider', 'min' => 0, 'max' => 50, 'default' => 30],
                    ],
                    'formula' => 'solar_roi',
                    'output_fields' => ['net_cost', 'roi_years', 'savings_25y'],
                    'disclaimer' => 'Estimación basada en datos medios. El ahorro real depende de tu ubicación, consumo y tarifa.',
                ],
            ],
            'Ciberseguridad' => [
                'page_title' => 'Audit de Ciberseguridad',
                'tool_type' => 'checker',
                'tool_config' => [
                    'checker_type' => 'cybersecurity_audit',
                    'title' => 'Evalúa la ciberseguridad de tu empresa',
                    'categories' => ['Acceso y contraseñas', 'Datos y backups', 'Red y dispositivos'],
                    'checks' => [
                        ['id' => 'mfa', 'label' => 'Autenticación multifactor (MFA)', 'description' => 'Todos los accesos críticos usan 2FA o MFA', 'category' => 'Acceso y contraseñas'],
                        ['id' => 'passwords', 'label' => 'Política de contraseñas', 'description' => 'Contraseñas de mínimo 12 caracteres, únicas por servicio', 'category' => 'Acceso y contraseñas'],
                        ['id' => 'manager', 'label' => 'Gestor de contraseñas', 'description' => 'Usáis un gestor como 1Password, Bitwarden o similar', 'category' => 'Acceso y contraseñas'],
                        ['id' => 'backups', 'label' => 'Backups automáticos', 'description' => 'Backups diarios de datos críticos con verificación', 'category' => 'Datos y backups'],
                        ['id' => 'encryption', 'label' => 'Datos cifrados', 'description' => 'Discos y comunicaciones cifradas (SSL/TLS, BitLocker)', 'category' => 'Datos y backups'],
                        ['id' => 'rgpd', 'label' => 'Cumplimiento RGPD', 'description' => 'Política de privacidad, consentimientos, DPO si aplica', 'category' => 'Datos y backups'],
                        ['id' => 'firewall', 'label' => 'Firewall activo', 'description' => 'Firewall configurado en red y dispositivos', 'category' => 'Red y dispositivos'],
                        ['id' => 'updates', 'label' => 'Actualizaciones al día', 'description' => 'SO, software y firmware actualizados regularmente', 'category' => 'Red y dispositivos'],
                        ['id' => 'antivirus', 'label' => 'Antivirus/EDR', 'description' => 'Protección endpoint en todos los dispositivos', 'category' => 'Red y dispositivos'],
                    ],
                    'disclaimer' => 'Esta evaluación es orientativa y no sustituye una auditoría profesional de ciberseguridad.',
                ],
            ],
            'Contabilidad' => [
                'page_title' => 'Calculadora de Impuestos para Autónomos',
                'tool_type' => 'calculator',
                'tool_config' => [
                    'calculator_type' => 'tax',
                    'fields' => [
                        ['name' => 'income', 'label' => 'Ingresos trimestrales (€)', 'type' => 'currency', 'min' => 0, 'max' => 100000, 'default' => 15000],
                        ['name' => 'expenses', 'label' => 'Gastos deducibles (€)', 'type' => 'currency', 'min' => 0, 'max' => 80000, 'default' => 5000],
                        ['name' => 'irpf_rate', 'label' => 'Retención IRPF (%)', 'type' => 'number', 'min' => 7, 'max' => 21, 'step' => 0.5, 'default' => 15],
                    ],
                    'formula' => 'spanish_autonomo',
                    'output_fields' => ['iva_pagar', 'irpf_pagar', 'beneficio_neto'],
                    'disclaimer' => 'Cálculo orientativo. Consulta con tu asesor fiscal para tu situación particular.',
                ],
            ],
            default => [
                'page_title' => "Herramienta de {$vertical}",
                'tool_type' => 'calculator',
                'tool_config' => [
                    'calculator_type' => 'generic',
                    'fields' => [
                        ['name' => 'amount', 'label' => 'Valor', 'type' => 'slider', 'min' => 0, 'max' => 100000, 'default' => 50000],
                    ],
                    'formula' => 'french_amortization',
                    'output_fields' => ['monthly_payment'],
                    'disclaimer' => 'Cálculo orientativo.',
                ],
            ],
        };
    }

    private function generatePages(NicheConfig $niche, string $description, array $tool): array
    {
        $vertical = $niche->vertical;
        $domain = $niche->domain;

        return [
            // Home
            [
                'slug' => '/',
                'type' => 'home',
                'seo' => [
                    'title' => "{$tool['page_title']} Online | {$domain}",
                    'description' => $description,
                ],
                'sections' => [
                    [
                        'type' => 'hero',
                        'headline' => $this->generateHeadline($vertical),
                        'subtitle' => $description,
                        'cta' => ['text' => 'Calcular ahora', 'link' => '/herramienta'],
                    ],
                    [
                        'type' => 'features',
                        'title' => "¿Por qué usar {$domain}?",
                        'items' => $this->generateFeatures($vertical),
                    ],
                    [
                        'type' => 'content',
                        'title' => 'Cómo funciona',
                        'body' => $this->generateHowItWorks($vertical),
                    ],
                    [
                        'type' => 'faq',
                        'title' => 'Preguntas frecuentes',
                        'items' => $this->generateFaqs($vertical),
                    ],
                    [
                        'type' => 'cta',
                        'title' => '¿Listo para empezar?',
                        'button' => ['text' => "Usar {$tool['page_title']}", 'link' => '/herramienta'],
                    ],
                ],
            ],
            // Tool page
            [
                'slug' => '/herramienta',
                'type' => 'tool',
                'seo' => [
                    'title' => "{$tool['page_title']} | {$domain}",
                    'description' => "Usa nuestra {$tool['page_title']} online gratuita.",
                ],
                'sections' => [
                    [
                        'type' => 'hero',
                        'headline' => $tool['page_title'],
                        'subtitle' => 'Herramienta gratuita y sin registro',
                    ],
                    [
                        'type' => 'tool',
                        'tool_type' => $tool['tool_type'],
                        'tool_config' => $tool['tool_config'],
                    ],
                ],
            ],
            // Contact
            [
                'slug' => '/contacto',
                'type' => 'contact',
                'seo' => [
                    'title' => "Contacto | {$domain}",
                    'description' => 'Ponte en contacto con nosotros.',
                ],
                'sections' => [
                    [
                        'type' => 'hero',
                        'headline' => 'Contacto',
                        'subtitle' => '¿Tienes alguna pregunta? Escríbenos.',
                    ],
                    ['type' => 'lead_form'],
                ],
            ],
            // Legal
            [
                'slug' => '/aviso-legal',
                'type' => 'legal',
                'seo' => ['title' => "Aviso Legal | {$domain}", 'description' => 'Aviso legal y condiciones.'],
                'sections' => [['type' => 'content', 'title' => 'Aviso Legal', 'body' => $this->generateLegalText($domain, 'aviso')]],
            ],
            [
                'slug' => '/privacidad',
                'type' => 'legal',
                'seo' => ['title' => "Política de Privacidad | {$domain}", 'description' => 'Política de privacidad.'],
                'sections' => [['type' => 'content', 'title' => 'Política de Privacidad', 'body' => $this->generateLegalText($domain, 'privacidad')]],
            ],
            [
                'slug' => '/cookies',
                'type' => 'legal',
                'seo' => ['title' => "Política de Cookies | {$domain}", 'description' => 'Política de cookies.'],
                'sections' => [['type' => 'content', 'title' => 'Política de Cookies', 'body' => $this->generateLegalText($domain, 'cookies')]],
            ],
        ];
    }

    private function generateNavigation(array $pages): array
    {
        $nav = [];
        foreach ($pages as $page) {
            if (in_array($page['type'], ['home', 'tool', 'contact'])) {
                $label = match ($page['type']) {
                    'home' => 'Inicio',
                    'tool' => 'Herramienta',
                    'contact' => 'Contacto',
                    default => $page['slug'],
                };
                $nav[] = ['label' => $label, 'slug' => $page['slug']];
            }
        }
        $nav[] = ['label' => 'Blog', 'slug' => '/blog'];

        return $nav;
    }

    private function generateTitle(string $vertical, string $domain): string
    {
        return match ($vertical) {
            'Hipotecas' => "Calculadora de Hipotecas | {$domain}",
            'Energía' => "Comparador de Tarifas Eléctricas | {$domain}",
            'Seguros' => "Comparador de Seguros | {$domain}",
            'Préstamos' => "Calculadora de Préstamos | {$domain}",
            'Solar' => "Calculadora de Energía Solar | {$domain}",
            'Ciberseguridad' => "Audit de Ciberseguridad PYME | {$domain}",
            'Contabilidad' => "Calculadora de Impuestos Autónomos | {$domain}",
            default => "{$vertical} | {$domain}",
        };
    }

    private function generateHeadline(string $vertical): string
    {
        return match ($vertical) {
            'Hipotecas' => 'Calcula tu hipoteca en 30 segundos',
            'Energía' => 'Compara tarifas y empieza a ahorrar',
            'Seguros' => 'Encuentra el seguro que necesitas',
            'Préstamos' => 'Calcula tu préstamo al instante',
            'Solar' => 'Descubre cuánto puedes ahorrar con solar',
            'Ciberseguridad' => 'Evalúa la ciberseguridad de tu empresa',
            'Contabilidad' => 'Calcula tus impuestos de autónomo',
            default => "Tu herramienta de {$vertical}",
        };
    }

    private function generateFeatures(string $vertical): array
    {
        return [
            ['icon' => 'calculator', 'title' => 'Cálculo instantáneo', 'description' => 'Resultados al momento, sin esperas ni registros.'],
            ['icon' => 'shield', 'title' => 'Datos fiables', 'description' => 'Basado en fuentes oficiales del sector en España.'],
            ['icon' => 'check', 'title' => 'Gratuito', 'description' => 'Sin costes ocultos ni compromisos.'],
        ];
    }

    private function generateHowItWorks(string $vertical): string
    {
        return match ($vertical) {
            'Hipotecas' => '<p>Introduce el importe que necesitas, el plazo en años y el tipo de interés. Nuestra calculadora utiliza el sistema de amortización francés para calcular tu cuota mensual, los intereses totales y el coste total de tu hipoteca.</p>',
            'Energía' => '<p>Indica tu consumo anual en kWh y compara tu tarifa actual con las mejores ofertas del mercado. Verás al instante cuánto puedes ahorrar al año cambiando de compañía.</p>',
            'Préstamos' => '<p>Introduce el importe, el plazo y el TIN que te ofrecen. Calculamos tu cuota mensual, los intereses totales y la TAE real para que puedas comparar ofertas con transparencia.</p>',
            'Solar' => '<p>Indica el coste de instalación, el ahorro anual estimado y las subvenciones disponibles. Calculamos en cuántos años amortizas la inversión y cuánto ahorrarás en 25 años.</p>',
            default => '<p>Utiliza nuestra herramienta para obtener resultados personalizados al instante.</p>',
        };
    }

    private function generateFaqs(string $vertical): array
    {
        return match ($vertical) {
            'Hipotecas' => [
                ['question' => '¿Cómo se calcula la cuota mensual?', 'answer' => 'Utilizamos el sistema de amortización francés, el más común en España. La cuota se calcula con la fórmula: C = P × [r(1+r)^n] / [(1+r)^n - 1].'],
                ['question' => '¿Qué tipo de interés debo usar?', 'answer' => 'Usa el tipo que te ofrezca tu banco. Para hipotecas variables, puedes usar el Euríbor actual más el diferencial.'],
                ['question' => '¿El resultado es vinculante?', 'answer' => 'No. Es una estimación orientativa. Las condiciones finales dependen de tu entidad bancaria.'],
            ],
            'Energía' => [
                ['question' => '¿Cuánto consume un hogar medio?', 'answer' => 'En España, el consumo medio es de unos 3.500 kWh al año, según datos de la CNMC.'],
                ['question' => '¿Puedo cambiar de compañía sin cortes?', 'answer' => 'Sí. El cambio es gratuito y no hay corte de suministro. La nueva compañía gestiona todo.'],
            ],
            'Préstamos' => [
                ['question' => '¿Qué diferencia hay entre TIN y TAE?', 'answer' => 'El TIN es el tipo de interés nominal. La TAE incluye comisiones y gastos, reflejando el coste real.'],
                ['question' => '¿Puedo amortizar anticipadamente?', 'answer' => 'Sí, aunque puede haber una comisión de amortización anticipada. Consulta las condiciones de tu contrato.'],
            ],
            'Ciberseguridad' => [
                ['question' => '¿Qué es un audit de ciberseguridad?', 'answer' => 'Es una evaluación sistemática de las medidas de seguridad de tu empresa para identificar vulnerabilidades y áreas de mejora.'],
                ['question' => '¿Es obligatorio para PYMEs?', 'answer' => 'No es obligatorio por ley, pero sí altamente recomendable. El RGPD exige medidas de seguridad adecuadas para proteger datos personales.'],
                ['question' => '¿Cada cuánto debo hacer un audit?', 'answer' => 'Se recomienda al menos una vez al año, o tras cambios significativos en la infraestructura o personal.'],
            ],
            'Contabilidad' => [
                ['question' => '¿Cuánto IRPF paga un autónomo?', 'answer' => 'La retención estándar es del 15%. Los nuevos autónomos pueden aplicar el 7% durante los primeros 3 años.'],
                ['question' => '¿Qué gastos puedo deducir?', 'answer' => 'Alquiler de oficina, suministros, material, seguros, cuota de autónomo, formación, y gastos de vehículo (parcialmente).'],
                ['question' => '¿Cuándo se presenta el IVA?', 'answer' => 'Trimestralmente: antes del 20 de abril, julio y octubre, y antes del 30 de enero del año siguiente.'],
            ],
            default => [
                ['question' => '¿Es gratuito?', 'answer' => 'Sí, totalmente gratuito y sin registro.'],
                ['question' => '¿Los datos son fiables?', 'answer' => 'Basados en fuentes oficiales y datos públicos del sector.'],
            ],
        };
    }

    private function generateLegalText(string $domain, string $type): string
    {
        return match ($type) {
            'aviso' => "<p><strong>{$domain}</strong> es un sitio web de carácter informativo. La información proporcionada no constituye asesoramiento profesional de ningún tipo.</p><p>El titular de este sitio web no se hace responsable de las decisiones tomadas en base a la información proporcionada.</p>",
            'privacidad' => "<p>En <strong>{$domain}</strong> nos tomamos en serio tu privacidad. Cumplimos con el Reglamento General de Protección de Datos (RGPD).</p><p>Los datos personales que nos proporciones a través del formulario de contacto serán utilizados exclusivamente para atender tu consulta y no serán cedidos a terceros sin tu consentimiento.</p>",
            'cookies' => "<p><strong>{$domain}</strong> utiliza cookies propias y de terceros para mejorar la experiencia de navegación y ofrecer contenidos de interés.</p><p>Puedes configurar tus preferencias de cookies en cualquier momento a través de tu navegador.</p>",
            default => '<p>Texto legal pendiente de generación.</p>',
        };
    }
}
