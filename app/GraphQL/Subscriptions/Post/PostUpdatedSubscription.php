<?php

namespace App\GraphQL\Subscriptions\Post;


use Closure;
use App\Models\Post;
use Carbon\Carbon;
use GraphQL\Type\Definition\ObjectType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Illuminate\Support\Facades\Auth;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\Field;
use Rebing\GraphQL\Support\SubscriptionType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostUpdatedSubscription extends Field
{
    protected $attributes = [
        'name' => 'onPostUpdated',
    ];

    public function type(): Type
    {
        return GraphQL::type('PostUpdated');
    }

    public function args(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
            ]
        ];
    }

    private static ?\Carbon\Carbon $postUpdatedAt = null;

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        $fields = $getSelectFields();
        $select = $info->lookAhead()->subFields('Post');
        $with = $fields->getRelations();
        $query = Post::select(array_merge($select, ['updated_at']))->with($with);
        
        $post = $query->where('id', $args['id'])->first();

        if (PostUpdatedSubscription::$postUpdatedAt === null) PostUpdatedSubscription::$postUpdatedAt = $post->updated_at;

        if (new \Carbon\Carbon($post->updated_at) > new \Carbon\Carbon(PostUpdatedSubscription::$postUpdatedAt)) {
            PostUpdatedSubscription::$postUpdatedAt = $post->updated_at;
            return new PostUpdated($post);
        }

        return null;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return true; //Auth::check();
    }
}
