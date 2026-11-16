<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR Code Service
 *
 * Generates QR codes locally (SVG) so they work offline and inside DomPDF.
 * Falls back to a public QR API when BaconQrCode is unavailable.
 * @author Renatus Bernardo
 */
class QrCodeService
{
    /**
     * Generate raw SVG markup for the given data.
     */
    public static function svg(string $data, int $size = 150): ?string
    {
        if (! class_exists(Writer::class)) {
            return null;
        }

        try {
            $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd());

            return (new Writer($renderer))->writeString($data);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Get an <img>-ready data URI (base64 SVG) for the given data.
     */
    public static function dataUri(string $data, int $size = 150): string
    {
        $svg = static::svg($data, $size);

        if ($svg) {
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
    }
}

