<?php

namespace App\GraphQL\Types;

use App\GraphQL\Subscriptions\Post\PostUpdated;
use App\Models\Post;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\WrappingType;

class PostUpdatedType extends ObjectType
{
    public function __construct()
    {
        parent::__construct(array_merge($this->attributes, ['fields' => $this->fields() ]));
    }

    protected $attributes = [
        'name'          => 'PostUpdated',
        'description'   => 'On Post Updated type',
    ];

    public function toType(): Type
    {
        return new static();
    }

    public function fields(): array
    {
        return [
            'post' => [
                'type' => Type::nonNull(GraphQL::type('Post')),
                'description' => 'The the post updated',
            ],
        ];
    }
}