<?php

use PHPUnit\Framework\TestCase;
use App\Helpers\Pagination;

require_once __DIR__ . '/../app/helpers/Pagination.php';

class PaginationWindowTest extends TestCase
{
    private function createPagination(int $page, int $perPage = 10, int $totalPages = 20): Pagination
    {
        $_GET['page'] = $page;
        $totalItems = $perPage * $totalPages;
        return Pagination::fromTotal($totalItems, $perPage);
    }

    protected function tearDown(): void
    {
        unset($_GET['page']);
    }

    public function testWindowAtStart(): void
    {
        $pagination = $this->createPagination(1);
        $this->assertSame([1, 2, 3, null, 20], $pagination->window());
    }

    public function testWindowInMiddle(): void
    {
        $pagination = $this->createPagination(10);
        $this->assertSame([1, null, 8, 9, 10, 11, 12, null, 20], $pagination->window());
    }

    public function testWindowAtEnd(): void
    {
        $pagination = $this->createPagination(20);
        $this->assertSame([1, null, 18, 19, 20], $pagination->window());
    }
}
