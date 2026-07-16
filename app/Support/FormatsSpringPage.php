<?php

namespace App\Support;

trait FormatsSpringPage
{
    protected function springPage(array $content, int $page, int $size, int $total, bool $sorted = false): array
    {
        $pages = $total === 0 ? 0 : (int) ceil($total / $size);
        $sort = ['empty' => ! $sorted, 'sorted' => $sorted, 'unsorted' => ! $sorted];

        return [
            'content' => $content,
            'pageable' => ['pageNumber' => $page, 'pageSize' => $size, 'sort' => $sort, 'offset' => $page * $size, 'paged' => true, 'unpaged' => false],
            'last' => $pages === 0 || $page >= $pages - 1,
            'totalPages' => $pages,
            'totalElements' => $total,
            'size' => $size,
            'number' => $page,
            'sort' => $sort,
            'first' => $page === 0,
            'numberOfElements' => count($content),
            'empty' => count($content) === 0,
        ];
    }
}
