<?php

namespace App\GraphQL\Queries;

use Closure;
use App\Models\User;
use Rebing\GraphQL\Support\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Rebing\GraphQL\Support\SelectFields;
use Illuminate\Support\Facades\Auth;

class MeQuery extends Core\PaginationQuery
{
    protected $attributes = [
        'name' => 'me',
    ];

    public function type(): Type
    {
        //return Type::listOf(GraphQL::type('User'));
        return GraphQL::type('User');
    }

    public function wrappedTypeArgs(): array
    {
        return [
            'id' => [
                'name' => 'id', 
                'type' => Type::id(),
            ],
            'name' => [
                'name' => 'name', 
                'type' => Type::string(),
            ],
            'email' => [
                'name' => 'email', 
                'type' => Type::string(),
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $info, Closure $getSelectFields)
    {
        /** @var SelectFields $fields */
        $fields = $getSelectFields();
        $select = $fields->getSelect();
        $with = $fields->getRelations();

        $users = User::select($select)->with($with);

        $users->where('id' , Auth::user()->id);

        return $users->first();
    }

    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return Auth::check();
    }
}