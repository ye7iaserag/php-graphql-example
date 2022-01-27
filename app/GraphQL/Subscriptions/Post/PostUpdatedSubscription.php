<?php

namespace App\GraphQL\Subscriptions\Post;


use Closure;
use App\Models\Post;
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
        //return GraphQL::type(GraphQL::wrapType('Post', 'PostUpdated', \App\GraphQL\Types\PostUpdatedType::class));
        return Type::nonNull(GraphQL::type('PostUpdated'));
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

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        // dd($info->returnType->getField('post')->config['type']);
        // dd($info->returnType);
        //dd($info->lookAhead()->subFields('Post'));
        // dd($info->lookAhead()->queryPlan());
        $fields = $getSelectFields();
        $select = $info->lookAhead()->subFields('Post');
        $with = $fields->getRelations();
        $query = Post::select($select)->with($with);
        
        $post = $query->where('id', $args['id'])->first();
        $updatedAt = $post->updated_at;
        $post->refresh();
        $postUpdated = new PostUpdated($post);
        return $postUpdated;
        if (new \Carbon\Carbon($post->updated_at) > new \Carbon\Carbon($updatedAt)) {
            return $post;
        }

        return null;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return true; //Auth::check();
    }
}
