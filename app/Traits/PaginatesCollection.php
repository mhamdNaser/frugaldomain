<?php

namespace App\Traits;

trait PaginatesCollection
{
    protected function paginate($items, $perPage, $page)
    {
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $items->slice($offset, $perPage)->values();
        $total = $items->count();
        $lastPage = (int) ceil($total / $perPage);

        return [
            'data' => $paginatedItems,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $offset + 1,
                'to' => $offset + $paginatedItems->count(),
            ],
            'links' => [
                'first' => url()->current() . '?page=1',
                'last' => url()->current() . '?page=' . $lastPage,
                'prev' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'next' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
            ],
        ];
    }
}
