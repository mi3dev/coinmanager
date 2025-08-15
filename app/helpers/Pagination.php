<?php
// v1.1
namespace App\Helpers;

final class Pagination {
    public int $page;
    public int $perPage;
    public int $total;
    public int $pages;
    public int $offset;

    private function __construct(int $page, int $perPage, int $total) {
        $this->perPage = max(1, $perPage);
        $this->total   = max(0, $total);
        $this->pages   = max(1, (int)ceil($this->total / $this->perPage));
        $this->page    = min(max(1, $page), $this->pages);
        $this->offset  = ($this->page - 1) * $this->perPage;
    }

    public static function fromTotal(int $total, ?int $perPage = null): self {
        $per = $perPage ?? (int)Config::get('app.users_per_page', 20);
        $page = (int)($_GET['page'] ?? 1);
        return new self($page, $per, $total);
    }

    public function firstPage(): int { return 1; }
    public function lastPage(): int  { return $this->pages; }
    public function hasPrev(): bool  { return $this->page > 1; }
    public function hasNext(): bool  { return $this->page < $this->pages; }
    public function prevPage(): int  { return max(1, $this->page - 1); }
    public function nextPage(): int  { return min($this->pages, $this->page + 1); }

    /**
     * Vrátí kompaktní okno stránek s ellipsami.
     * - $radius = kolik stránek vlevo/vpravo od aktuální
     * - Vždy zobrazí 1 a N, mezi bloky vloží null jako "…"
     * Příklad návratu: [1, null, 4, 5, 6, null, 20]
     */
    public function window(int $radius = 2): array {
        $pages = [];
        $start = max(1, $this->page - $radius);
        $end   = min($this->pages, $this->page + $radius);

        // Začátek
        $pages[] = 1;

        // Levá elipsa
        if ($start > 2) $pages[] = null;

        // Střední okno
        for ($i = $start; $i <= $end; $i++) {
            if ($i !== 1 && $i !== $this->pages) {
                $pages[] = $i;
            }
        }

        // Pravá elipsa
        if ($end < $this->pages - 1) $pages[] = null;

        // Konec
        if ($this->pages > 1) $pages[] = $this->pages;

        // Odstranit duplicitní 1/N a nechat unikátní pořadí
        $out = [];
        $seen = [];
        foreach ($pages as $p) {
            $key = ($p === null) ? '...' : (string)$p;
            if (!isset($seen[$key])) {
                $out[] = $p;
                $seen[$key] = true;
            }
        }
        return $out;
    }
}
