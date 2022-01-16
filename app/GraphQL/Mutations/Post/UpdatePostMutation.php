<?php

namespace App\GraphQL\Mutations\Post;

use Closure;
use App\Models\Post;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Auth;

class UpdatePostMutation extends Mutation
{

    private ?Post $post;

    protected $attributes = [
        'name' => 'updatePost',
        'description' => 'Update a post'
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('Post'));
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'The id of post',
                'rules' => ['required']
            ],
            'title' => ['
                name' => 'title', 
                'type' => Type::string(),
                'description' => 'The title of post',
                'rules' => ['min:2', 'max:20']
            ],
            'content' => [
                'name' => 'content', 
                'type' => Type::string(),
                'description' => 'The content of post',
                'rules' => ['min:1', 'max:200']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $post = Post::find($args['id']);
        if (!$post) throw new \Exception();

        isset($args['title']) ? $post->title = $args['title'] : null;
        isset($args['content']) ? $post->content = $args['content'] : null;

        $post->save();

        return $post;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        $this->post = Post::find($args['id']);
        if (!$this->post) return false;
        return intval($this->post->user_id) === intval(Auth::user()?->id);
    }
}