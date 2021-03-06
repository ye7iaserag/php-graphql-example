<?php

namespace App\GraphQL\Queries;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use App\GraphQL\Middleware;

class UsersQuery extends Core\PaginationQuery
{
    protected $attributes = [
        'name' => 'users',
    ];

    protected $middleware = [
        Middleware\ResolvePage::class,
    ];

    public function type(): Type
    {
        //return Type::listOf(GraphQL::type('User'));
        return GraphQL::paginate('User');
    }

    public function wrappedTypeArgs(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $users = User::select($select)->with($with);

        if (isset($args['id'])) {
            $users->where('id', $args['id']);
        }

        if (isset($args['email'])) {
            $users->where('email', $args['email']);
        }
        $args['limit'] ??= 10;
        //  dd($getSelectFields);
        return $users->paginate($args['limit']);
    }
}
