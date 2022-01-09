<?php

namespace App\GraphQL\Queries;

use Closure;
use App\Models\Post;
use GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Rebing\GraphQL\Support\Query;
use App\GraphQL\Middleware;

class PostsQuery extends Core\PaginationQuery
{
    protected $attributes = [
        'name' => 'posts',
    ];

    protected $middleware = [
        Middleware\ResolvePage::class,
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Post');
    }

    public function wrappedTypeArgs(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::id(),
            ],
            'userId' => [
                'name' => 'userId', 
                'type' => Type::id(),
            ],
            'title' => [
                'name' => 'title', 
                'type' => Type::string(),
            ]
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $users = Post::select($select)->with($with);

        if (isset($args['id'])) {
            $users->where('id' , $args['id']);
        }

        if (isset($args['userId'])) {
            $users->where('user_id' , $args['userId']);
        }

        if (isset($args['title'])) {
            $users->where('title', 'LIKE', $args['title']);
        }
        $args['limit'] ??= 10;

        return $users->paginate($args['limit']);
    }
}