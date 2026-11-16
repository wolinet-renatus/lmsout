<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the 6 pre-built certificate designs (v2.2.0).
 * Safe to run multiple times — existing templates (by name) are skipped.
 * @author Renatus Bernardo
 */
class CertificateTemplateSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     */
    public function run(): void
    {
        $first = CertificateTemplate::count() === 0;

        foreach (CertificateTemplate::getPredefinedDesigns() as $design) {
            if (CertificateTemplate::where('name', $design['name'])->exists()) {
                continue;
            }

            CertificateTemplate::create([
                'name' => $design['name'],
                'orientation' => $design['orientation'],
                'canvas_width' => $design['orientation'] === 'landscape' ? 842 : 595,
                'canvas_height' => $design['orientation'] === 'landscape' ? 595 : 842,
                'elements' => $design['elements'],
                'is_default' => $first,
                'is_active' => true,
            ]);

            $first = false;
        }
    }
}

