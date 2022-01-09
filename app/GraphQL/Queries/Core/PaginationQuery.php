<?php

namespace App\GraphQL\Queries\Core;

use Rebing\GraphQL\Support\Query;
use GraphQL\Type\Definition\Type;

abstract class PaginationQuery extends Query
{

    public function wrappedTypeArgs(): array {
        return [];
    }
    private function paginationTypeArgs(): array {
        return [
            'page' => [
                'name' => 'page', 
                'type' => Type::int(),
            ]
        ];
    }

    final public function args(): array {
        return array_merge($this->wrappedTypeArgs(), $this->paginationTypeArgs());
    }


}