<?php

namespace App\GraphQL\Mutations\Post;

use Closure;
use App\Models\Post;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Auth;

class CreatePostMutation extends Mutation
{
    protected $attributes = [
        'name' => 'createPost',
        'description' => 'Create a post'
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('Post'));
    }

    public function args(): array
    {
        return [
            'title' => ['
                name' => 'title', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of post',
                'rules' => ['required', 'min:2', 'max:20']
            ],
            'content' => [
                'name' => 'content', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The secret of content',
                'rules' => ['required', 'min:1', 'max:200']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $post = new Post();
        $post->title = $args['title'];
        $post->content = $args['content'];
        
        $post->user_id = 1;

        $post->save();

        return $post;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return Auth::check();
    }
}