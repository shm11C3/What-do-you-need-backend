<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $posts = Post::all();
        $users = User::all();


        foreach($posts as $i => $post) {
            if ($i % 2) {
                Reaction::create([
                    'auth_id' => $users[rand(0, count($users)-1)]->auth_id,
                    'reactable_ulid' => $post->ulid,
                    'reaction_type' => array_rand(Reaction::TYPES),
                    'reactable_type' => 'App\\Models\\Post',
                ]);
            }
        }
    }
}
