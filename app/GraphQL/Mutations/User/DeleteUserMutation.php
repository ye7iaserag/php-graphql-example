<?php

namespace App\GraphQL\Mutations\User;

use Closure;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Auth;

class DeleteUserMutation extends Mutation
{
    protected $attributes = [
        'name' => 'deleteUser',
        'description' => 'Delete a user'
    ];

    public function type(): Type
    {
        //return Type::nonNull(GraphQL::type('User'));
        return Type::nonNull(Type::boolean());
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'The id of user to delete',
                'rules' => ['required']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = User::find($args['id']);
        if (!$user) throw new \Exception();

        $user->delete();

        return true;
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return intval(Auth::user()?->id) === intval($args['id']);
    }
}