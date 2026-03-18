<?php
namespace App\Services\Restaurant;

use App\Models\RestaurantTable;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;


class QrCodeService
{
    public function generate(RestaurantTable $table): string
    {
        $restaurant = $table->restaurant;

        // 👇 Yahan check hoga ki Table Branch ki hai ya Main Restaurant ki
        if ($table->branch_id) {
            $folder = "restaurants/{$restaurant->slug}/branches/branch-{$table->branch_id}/TablesQR";
        } else {
            $folder = "restaurants/{$restaurant->slug}/TablesQR";
        }

        $filename = "table-{$table->table_number}.svg";

        Storage::disk('public')->makeDirectory($folder);

        //$url = "http://192.168.1.32:8081/menu/{$restaurant->id}/{$table->id}/{$table->qr_token}";
        //$url = "https://rest-menu-smoky.vercel.app/?r={$restaurant->id}&t={$table->id}&token={$table->qr_token}";
        $url = "http://192.168.1.30:8081/?r={$restaurant->id}&t={$table->id}&token={$table->qr_token}";

        /**
         * 1️⃣ Generate QR SVG
         */
        $qrSvg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->generate($url);

        $qrSvg = preg_replace('/<\?xml.*?\?>/', '', $qrSvg);

        /**
         * 2️⃣ Embed Logo as Base64 (CRITICAL FIX)
         */
        $logoSvg = '';
        if ($restaurant->logo_path && Storage::disk('public')->exists($restaurant->logo_path)) {

            $logoBinary = Storage::disk('public')->get($restaurant->logo_path);
            $extension = pathinfo($restaurant->logo_path, PATHINFO_EXTENSION);
            $mime = match ($extension) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            };

            $logoBase64 = base64_encode($logoBinary);

            $logoSvg = <<<SVG
    <image href="data:{$mime};base64,{$logoBase64}"
        x="20"
        y="20"
        width="90"
        height="60"
        preserveAspectRatio="xMidYMid meet"/>
    SVG;
        }

        /**
         * 3️⃣ Final Horizontal SVG
         */
        $finalSvg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="420" height="320" align="center" >
        <rect width="100%" height="100%" fill="#ffffff"/>

        {$logoSvg}

        <text x="130"
            y="40"
            font-size="18"
            font-weight="bold"
            fill="#000">
            {$restaurant->name}
        </text>

        <text x="130"
            y="65"
            font-size="14"
            fill="#444">
            Table {$table->table_number}
        </text>

        <g transform="translate(100,90)">
            {$qrSvg}
        </g>

        
    </svg>
    SVG;

        Storage::disk('public')->put("{$folder}/{$filename}", $finalSvg);

        $table->updateQuietly([
            'qr_path' => "{$folder}/{$filename}",
        ]);

        return "{$folder}/{$filename}";
    }

}