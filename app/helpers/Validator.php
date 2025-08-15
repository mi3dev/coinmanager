<?php
// v1.0
namespace App\Helpers;

final class Validator
{
    public static function str(string $value, int $max, bool $required = false): ?string {
        $v = trim($value);
        if ($required && $v === '') return 'Pole je povinné.';
        if ($v !== '' && mb_strlen($v) > $max) return "Maximální délka je {$max} znaků.";
        return null;
    }

    public static function nullableInt($value, ?int $min = null, ?int $max = null): ?string {
        if ($value === '' || $value === null) return null;
        if (!preg_match('/^-?\d+$/', (string)$value)) return 'Musí být celé číslo.';
        $i = (int)$value;
        if ($min !== null && $i < $min) return "Hodnota nesmí být menší než {$min}.";
        if ($max !== null && $i > $max) return "Hodnota nesmí být větší než {$max}.";
        return null;
    }
}
