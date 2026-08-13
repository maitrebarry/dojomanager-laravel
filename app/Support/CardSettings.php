<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CardSettings
{
    public static function all(): array
    {
        $defaults = self::defaults();
        $path = self::path();

        if (!File::exists($path)) {
            return $defaults;
        }

        $stored = json_decode((string) File::get($path), true);

        return array_replace_recursive($defaults, is_array($stored) ? $stored : []);
    }

    public static function save(array $settings): void
    {
        File::ensureDirectoryExists(dirname(self::path()));
        File::put(self::path(), json_encode(array_replace_recursive(self::all(), $settings), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function storeFile(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    public static function publicUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }

    public static function defaults(): array
    {
        return [
            'official' => [
                'ministry' => "Ministère de la Jeunesse et des Sports chargé de l'Instruction Civique et de la Construction Citoyenne",
                'federation' => 'Fédération Malienne de Taekwondo',
                'league' => 'Ligue Régionale de Ségou',
                'motto' => 'Courtoisie - Loyauté - Persévérance - Maîtrise de soi - Discipline',
                'president_name' => 'MOHAMED ICHAKA TRAORE',
            ],
            'signature_path' => null,
            'stamp_path' => null,
            'card' => [
                'default_template' => 'classic',
                'primary_color' => '#0f5132',
                'secondary_color' => '#d4af37',
                'background_color' => '#f8fafc',
                'background_image_path' => null,
                'decorative_image_path' => null,
            ],
        ];
    }

    private static function path(): string
    {
        return storage_path('app/card-settings.json');
    }
}
