<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@cms.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // Create editor user
        User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@cms.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_EDITOR,
        ]);

        // Create writer user
        User::factory()->create([
            'name' => 'Writer User',
            'email' => 'writer@cms.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_WRITER,
        ]);

        // Create default categories
        $categories = [
            ['name' => 'Politics', 'slug' => 'politics', 'description' => 'Political news and analysis'],
            ['name' => 'Economy', 'slug' => 'economy', 'description' => 'Economic news and financial updates'],
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Tech news and innovations'],
            ['name' => 'Society', 'slug' => 'society', 'description' => 'Social issues and community news'],
            ['name' => 'International', 'slug' => 'international', 'description' => 'World news and global affairs'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
