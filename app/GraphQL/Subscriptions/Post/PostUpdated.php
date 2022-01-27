<?php

namespace App\GraphQL\Subscriptions\Post;

use App\Models\Post;

class PostUpdated {
    public function __construct(public Post $post) {}
}