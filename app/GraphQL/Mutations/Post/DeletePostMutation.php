<?php

namespace App\GraphQL\Mutations\Post;

use Closure;
use App\Models\Post;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Auth;

class DeletePostMutation extends Mutation
{

    private ?Post $post;

    protected $attributes = [
        'name' => 'deletePost',
        'description' => 'Delete a post'
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::boolean());
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'The id of post to delete',
                'rules' => ['required']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $post = Post::find($args['id']);
        if (!$post) throw new \Exception();

        $post->delete();

        return true;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        $this->post = Post::find($args['id']);
        if (!$this->post) return false;
        return intval($this->post->user_id) === intval(Auth::user()?->id);
    }
}
