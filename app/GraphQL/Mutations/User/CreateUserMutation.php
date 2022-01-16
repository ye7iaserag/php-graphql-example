<?php

namespace App\GraphQL\Mutations\User;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Hash;

class CreateUserMutation extends Mutation
{
    protected $attributes = [
        'name' => 'createUser',
        'description' => 'Create a user'
    ];

    public function type(): Type
    {
        return Type::nonNull(GraphQL::type('User'));
    }

    public function args(): array
    {
        return [
            'name' => ['
                name' => 'name', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of user',
                'rules' => ['required', 'min:2', 'max:20']
            ],
            'email' => [
                'name' => 'email', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The email of user',
                'rules' => ['required', 'email', 'unique:users']
            ],
            'password' => [
                'name' => 'password', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The password of user',
                'rules' => ['required', 'min:8', 'max:16']
            ],
            'secret' => [
                'name' => 'secret', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The secret of user',
                'rules' => ['required', 'min:1', 'max:200']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = new User();
        $user->name = $args['name'];
        $user->email = $args['email'];
        $user->password = Hash::make($args['password'], ['rounds' => 12]);
        $user->secret = $args['secret'];

        $user->save();

        return $user;
    }
}