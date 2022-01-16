<?php

namespace App\GraphQL\Mutations\Auth;

use Closure;
use App\Models\User;
use GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\Mutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginMutation extends Mutation
{
    protected $attributes = [
        'name' => 'loginUser',
        'description' => 'Login a user'
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::string());
    }

    public function args(): array
    {
        return [
            'email' => [
                'name' => 'email', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The email of user',
                'rules' => ['required', 'email']
            ],
            'password' => [
                'name' => 'password', 
                'type' => Type::nonNull(Type::string()),
                'description' => 'The password of user',
                'rules' => ['required', 'min:8', 'max:16']
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = User::where('email', $args['email'])->first();
        $result = Hash::check($args['password'], $user->password);

        if (!$result) throw new \Exception();

        $token = Auth::login($user);
        return $token;
    }
}