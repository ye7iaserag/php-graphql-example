<?php

namespace App\GraphQL\Mutations\User;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Auth;

class UpdateUserMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateUser',
        'description' => 'Update a user'
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'The id of user',
                'rules' => ['required']
            ],
            'name' => ['
                name' => 'name', 
                'type' => Type::string(),
                'description' => 'The name of user',
                'rules' => ['min:2', 'max:20']
            ],
            'email' => [
                'name' => 'email', 
                'type' => Type::string(),
                'description' => 'The email of user',
                'rules' => ['email']
            ],
            'secret' => [
                'name' => 'secret', 
                'type' => Type::string(),
                'description' => 'The secret of user',
                'rules' => ['min:1', 'max:200']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = User::find($args['id']);
        if (!$user) throw new \Exception();

        isset($args['name']) ? $user->name = $args['name'] : null;
        isset($args['email']) ? $user->email = $args['email'] : null;
        isset($args['secret']) ? $user->secret = $args['secret'] : null;

        $user->save();

        return $user;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return intval(Auth::user()?->id) === intval($args['id']);
    }
}