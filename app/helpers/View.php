<?php
// v1.0
namespace App\Helpers;

use App\Core\Session;

final class View {
    public static function csrfField(): string {
        $t = Session::csrf();
        return '<input type="hidden" name="csrf" value="'.htmlspecialchars($t, ENT_QUOTES).'">';
    }

    public static function flashHtml(): string {
        $out = '';
        if ($e = Session::flash('error')) {
            $out .= '<div class="mb-4 rounded-md border border-red-800 bg-red-950/40 px-3 py-2 text-sm text-red-200">' . htmlspecialchars($e) . '</div>';
        }
        if ($s = Session::flash('success')) {
            $out .= '<div class="mb-4 rounded-md border border-emerald-800 bg-emerald-950/40 px-3 py-2 text-sm text-emerald-200">' . htmlspecialchars($s) . '</div>';
        }
        return $out;
    }
}
