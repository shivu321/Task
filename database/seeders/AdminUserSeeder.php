<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Schema::disableForeignKeyConstraints();
        DB::table("admin_users")->truncate();
        Schema::enableForeignKeyConstraints();
        $data = [
            "uuid" => Uuid::uuid4(),
            "name" => " Hotel Management",
            "email" => "admin@hotel-management.com",
            "password" => Hash::make("Pass@123"),
            "is_admin" => 1,
            "status" => "ACTIVE",
            'remember_token' => Str::random(10),
        ];

        $setInfo = new AdminUser();
        $setInfo->uuid = data_get($data,'uuid');
        $setInfo->name = data_get($data,'name');
        $setInfo->email = data_get($data,'email');
        $setInfo->password =  data_get($data,'password');
        $setInfo->is_admin =  data_get($data,'is_admin');
        $setInfo->status = data_get($data,'status'); 
        $setInfo->remember_token = data_get($data,'remember_token');
        $setInfo->save();
    }
}
