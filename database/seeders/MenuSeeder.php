<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleAccess;
use App\Models\RoleAccesse;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        Schema::disableForeignKeyConstraints();
        DB::table("menus")->truncate();
        DB::table("admin_roles")->truncate();
        DB::table("roles")->truncate();
        DB::table("role_accesses")->truncate();
        Schema::enableForeignKeyConstraints();

        Menu::create(["menu" => "Dashboard", "code" => "dashboard", "menu_for" => "ADMIN", "can_create" => "0", "can_read" => "0", "can_update" => "0", "can_delete" => "0", "can_print" => "0", "parent_menu_id" => "0", "status" => "ACTIVE", "ordering" => 1]);
        Menu::create(["menu" => "Manage Hotel", "code" => "manage_hotel", "menu_for" => "ADMIN","can_create" => "0", "can_read" => "0", "can_update" => "0", "can_delete" => "0", "can_print" => "0", "parent_menu_id" => "0", "status" => "ACTIVE", "ordering" => 3]);
        Menu::create(["menu" => "Manage Booking", "code" => "manage_booking","menu_for" => "ADMIN", "can_create" => "0", "can_read" => "0", "can_update" => "0", "can_delete" => "0", "can_print" => "0", "parent_menu_id" => "0", "status" => "ACTIVE", "ordering" => 4]);
        Menu::create(["menu" => "Manage Role", "code" => "manage_role", "menu_for" => "ADMIN","can_create" => "0", "can_read" => "0", "can_update" => "0", "can_delete" => "0", "can_print" => "0", "parent_menu_id" => "0", "status" => "ACTIVE", "ordering" => 5]);
        Menu::create(["menu" => "Manage Transaction", "code" => "manage_transaction", "menu_for" => "ADMIN" ,"can_create" => "0", "can_read" => "0", "can_update" => "0", "can_delete" => "0", "can_print" => "0", "parent_menu_id" => "0", "status" => "ACTIVE", "ordering" => 11]);
        Menu::create(["menu" => "Manage User", "code" => "manage_user", "menu_for" => "ADMIN" ,"can_create" => "0", "can_read" => "0", "can_update" => "0", "can_delete" => "0", "can_print" => "0", "parent_menu_id" => "0", "status" => "ACTIVE", "ordering" => 11]);


        Role::create(['role_name' => "Default Admin Role", "is_editable" => "N", "role_type"=> "ADMIN","status" => "ACTIVE"]);

        RoleAccess::create(["role_id" => 1, "menu_id" => 1, "can_create" => "1", "can_read" => "1", "can_update" => "1", "can_delete" => "1", "can_print" => "1"]);
        RoleAccess::create(["role_id" => 1, "menu_id" => 2, "can_create" => "1", "can_read" => "1", "can_update" => "1", "can_delete" => "1", "can_print" => "1"]);
        RoleAccess::create(["role_id" => 1, "menu_id" => 3, "can_create" => "1", "can_read" => "1", "can_update" => "1", "can_delete" => "1", "can_print" => "1"]);
        RoleAccess::create(["role_id" => 1, "menu_id" => 4, "can_create" => "1", "can_read" => "1", "can_update" => "1", "can_delete" => "1", "can_print" => "1"]);
        RoleAccess::create(["role_id" => 1, "menu_id" => 5, "can_create" => "1", "can_read" => "1", "can_update" => "1", "can_delete" => "1", "can_print" => "1"]);
        RoleAccess::create(["role_id" => 1, "menu_id" => 6, "can_create" => "1", "can_read" => "1", "can_update" => "1", "can_delete" => "1", "can_print" => "1"]);
        

        AdminRole::insert(["admin_id" => "1","role_id" => "1",'created_at' => Carbon::now(),'updated_at' => Carbon::now()]);
    }
}
