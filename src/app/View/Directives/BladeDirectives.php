<?php

namespace App\View\Directives;
use Illuminate\Support\Facades\Blade;

class BladeDirectives
{
    public static function register(): void
    {
        Blade::directive('money', function ($expression) {
            return "<?php echo 'R$ ' . number_format($expression / 100, 2, ',', '.'); ?>";
        });
    }
}
