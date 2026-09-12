<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffSectionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $username, string $role, array $permissions = []): Admin
    {
        return Admin::create(array_merge([
            'username' => $username,
            'password_hash' => bcrypt('test-password'),
            'role' => $role,
        ], $permissions));
    }

    public function test_staff_cannot_see_or_open_restricted_sections_without_permission(): void
    {
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('admin.courses.index').'"', false)
            ->assertDontSee('href="'.route('admin.media.index').'"', false)
            ->assertDontSee('Add New Course')
            ->assertDontSee('Upload Media');

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.courses.index'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.media.index'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_owner_can_grant_staff_access_to_each_section(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN);
        $staff = $this->account('staff', Admin::ROLE_STAFF);

        $this->withSession(['admin_id' => $owner->id])
            ->put(route('admin.users.permissions', $staff), [
                'can_manage_courses' => '1',
                'can_manage_media' => '1',
            ])
            ->assertSessionHas('success');

        $staff->refresh();
        $this->assertTrue($staff->can_manage_courses);
        $this->assertTrue($staff->can_manage_media);

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.courses.index'))
            ->assertOk();

        $this->withSession(['admin_id' => $staff->id])
            ->get(route('admin.media.index'))
            ->assertOk();
    }

    public function test_administrator_accounts_always_have_full_section_access(): void
    {
        $owner = $this->account('owner', Admin::ROLE_ADMIN, [
            'can_manage_courses' => false,
            'can_manage_media' => false,
        ]);

        $this->assertTrue($owner->canAccess('courses'));
        $this->assertTrue($owner->canAccess('media'));

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.courses.index'))
            ->assertOk();

        $this->withSession(['admin_id' => $owner->id])
            ->get(route('admin.media.index'))
            ->assertOk();
    }
}
