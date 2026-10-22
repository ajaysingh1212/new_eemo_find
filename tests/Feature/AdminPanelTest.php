<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\Device;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BrandingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_command_creates_super_admin_with_hashed_password(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Full name', 'Setup Admin')
            ->expectsQuestion('Email address', 'SETUP@EXAMPLE.COM')
            ->expectsQuestion('Password (12+ characters, uppercase, lowercase, number, symbol)', 'SetupPassword!123')
            ->expectsQuestion('Confirm password', 'SetupPassword!123')
            ->expectsOutput('Super Admin created.')
            ->assertExitCode(0);
        $admin = Admin::where('email', 'setup@example.com')->firstOrFail();
        $this->assertTrue($admin->is_super_admin);
        $this->assertTrue(Hash::check('SetupPassword!123', $admin->password));
    }

    public function test_setup_command_rejects_weak_password_without_creating_account(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Full name', 'Setup Admin')
            ->expectsQuestion('Email address', 'setup@example.com')
            ->expectsQuestion('Password (12+ characters, uppercase, lowercase, number, symbol)', 'weak')
            ->expectsQuestion('Confirm password', 'weak')
            ->assertExitCode(1);
        $this->assertDatabaseCount('admins', 0);
    }

    public function test_admin_only_schema_supports_user_registry(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumn('users', 'user_code'));
        $this->assertTrue(Schema::hasColumn('users', 'status'));
        $this->assertTrue(Schema::hasColumn('users', 'last_login_at'));
        $this->assertTrue(Schema::hasColumn('users', 'token'));
        $this->assertTrue(Schema::hasColumn('users', 'ip_address'));
        $this->assertTrue(Schema::hasColumn('users', 'login_device'));
        foreach (['/register', '/admin/register', '/login', '/dashboard'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_user_ip_address_and_login_device_are_optional_model_fields(): void
    {
        $user = User::factory()->create([
            'ip_address' => '192.0.2.1',
            'login_device' => 'Windows 11 Chrome',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'ip_address' => '192.0.2.1',
            'login_device' => 'Windows 11 Chrome',
        ]);

        $optionalUser = User::factory()->create([
            'ip_address' => null,
            'login_device' => null,
        ]);

        $this->assertNull($optionalUser->ip_address);
        $this->assertNull($optionalUser->login_device);
    }

    public function test_devices_have_manufacturer_model_uuid_firmware_and_user_relationship(): void
    {
        $this->assertTrue(Schema::hasTable('devices'));
        $this->assertTrue(Schema::hasColumns('devices', [
            'user_id',
            'manufacturer',
            'device_model',
            'device_uuid',
            'firmware_version',
        ]));

        $user = User::factory()->create();
        $device = Device::factory()->for($user)->create([
            'manufacturer' => 'Eemo',
            'device_model' => 'GT06',
            'device_uuid' => 'eemo-device-uuid-001',
            'firmware_version' => '1.2.3',
        ]);

        $this->assertSame($user->id, $device->user->id);
        $this->assertTrue($user->devices->contains('id', $device->id));
        $this->assertSame('Eemo', $device->manufacturer);
        $this->assertSame('GT06', $device->device_model);
        $this->assertSame('eemo-device-uuid-001', $device->device_uuid);
        $this->assertSame('1.2.3', $device->firmware_version);
    }

    public function test_admin_can_create_user_with_unassigned_device(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $response = $this->post('/admin/user-management/users', [
            'name' => 'Test User',
            'email' => 'test.user@example.com',
            'phone' => '9876543210',
            'address' => 'Delhi, India',
            'password' => 'StrongPassword!123',
        ]);

        $response->assertRedirect(route('admin.user-management.devices'));
        $this->assertDatabaseHas('users', ['email' => 'test.user@example.com']);
        $user = User::firstOrFail();
        $device = $user->devices()->firstOrFail();
        $this->assertSame('Unknown', $device->manufacturer);
        $this->assertSame('Unknown', $device->device_model);
        $this->assertStringStartsWith('Pending-', $device->device_uuid);
        $this->assertNull($device->firmware_version);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', $user->user_code);
        $this->assertNotSame('StrongPassword!123', $user->password);
        $this->assertTrue(Hash::check('StrongPassword!123', $user->password));
        $this->assertNotNull($user->token);
        $this->assertNull($user->last_login_at);
        $this->assertSame('active', $user->status);
    }

    public function test_admin_user_page_routes_view_action_to_full_details_page(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create([
            'name' => 'Popup User',
            'email' => 'popup.user@example.com',
            'phone' => '9876543210',
            'address' => 'Delhi, India',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.user-management.users'))
            ->assertOk()
            ->assertSee('data-open-user-modal', false)
            ->assertSee('class="action-button edit-button"', false)
            ->assertSee('class="action-button view-button" href="'.route('admin.user-management.users.show', $user).'"', false)
            ->assertSee('id="user-modal-backdrop"', false)
            ->assertDontSee('id="user-view-details"', false)
            ->assertDontSee('id="view-edit-user"', false)
            ->assertSee('id="user-image-preview"', false)
            ->assertSee('id="user-modal-subtitle"', false)
            ->assertSee('name="status"', false)
            ->assertSee('Inactive</span>', false)
            ->assertSee('data-user-status="inactive"', false)
            ->assertSee(asset('assets/app.js').'?v='.hash_file('sha256', public_path('assets/app.js')), false)
            ->assertDontSee('const openUserModal', false);

        $this->assertStringContainsString(
            "document.querySelectorAll('[data-open-user-modal]')",
            file_get_contents(public_path('assets/app.js'))
        );
        $this->assertStringContainsString("document.querySelectorAll('.edit-button')", file_get_contents(public_path('assets/app.js')));
        $this->assertStringNotContainsString("document.querySelectorAll('.view-button')", file_get_contents(public_path('assets/app.js')));
        $this->assertStringContainsString("fields.image.addEventListener('change'", file_get_contents(public_path('assets/app.js')));
        $this->assertStringContainsString("fields.status.value = isCreate ? 'active' : (user.status || 'active')", file_get_contents(public_path('assets/app.js')));
    }

    public function test_user_details_page_shows_full_account_and_linked_device_preview(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create([
            'name' => 'Details User',
            'email' => 'details.user@example.com',
            'phone' => '9876543210',
            'address' => 'Delhi, India',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);
        Device::factory()->for($user)->create([
            'manufacturer' => 'Eemo',
            'device_model' => 'GT06',
            'device_uuid' => 'details-device-uuid-001',
            'firmware_version' => '1.2.3',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.user-management.users.show', $user))
            ->assertOk()
            ->assertSee('Details User')
            ->assertSee($user->user_code)
            ->assertSee('details.user@example.com')
            ->assertSee('9876543210')
            ->assertSee('Delhi, India')
            ->assertSee($user->user_code)
            ->assertSee('Device details')
            ->assertSee('Device name')
            ->assertSee('Manufacturer / Brand')
            ->assertSee('Device ID / UUID')
            ->assertSee('Network')
            ->assertSee('GPS status')
            ->assertSee('Battery')
            ->assertSee('Last update')
            ->assertSee('CONNECTED DEVICE')
            ->assertSee('class="user-detail-phone"', false)
            ->assertSee('Eemo')
            ->assertSee('GT06')
            ->assertSee('details-device-uuid-001')
            ->assertSee('Firmware version')
            ->assertSee('1.2.3')
            ->assertDontSee('Personal details')
            ->assertDontSee('Email address')
            ->assertDontSee('user-detail-device-meta', false)
            ->assertDontSee('user-view-details', false);

        $responseContent = $this->get(route('admin.user-management.users.show', $user))->getContent();
        $deviceDetailsPosition = strpos($responseContent, 'Device details');
        $devicePreviewPosition = strpos($responseContent, 'user-detail-device-panel');
        $this->assertNotFalse($deviceDetailsPosition);
        $this->assertNotFalse($devicePreviewPosition);
        $this->assertLessThan($devicePreviewPosition, $deviceDetailsPosition);
    }

    public function test_admin_user_page_counts_active_and_inactive_users_from_database(): void
    {
        $admin = Admin::factory()->create();
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.user-management.users'))->assertOk();
        preg_match('/<article class="user-stat active-stat">\s*<span>Active users<\/span>\s*<strong>(\d+)<\/strong>/s', $response->getContent(), $activeCount);
        preg_match('/<article class="user-stat inactive-stat">\s*<span>Inactive users<\/span>\s*<strong>(\d+)<\/strong>/s', $response->getContent(), $inactiveCount);

        $this->assertSame('1', $activeCount[1] ?? null);
        $this->assertSame('1', $inactiveCount[1] ?? null);
    }

    public function test_admin_device_page_shows_user_counts_and_actions(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create([
            'name' => 'Demo Device Owner',
            'email' => 'device.owner@example.com',
            'phone' => '9876543210',
            'address' => '123 Demo Device Street',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.user-management.devices'))
            ->assertOk()
            ->assertSee('Device Management')
            ->assertSee('Total users')
            ->assertSee('Active users')
            ->assertSee('Inactive users')
            ->assertDontSee('Add device')
            ->assertSee('data-open-user-modal', false)
            ->assertSee('Search devices...')
            ->assertSee('Demo Device Owner')
            ->assertSee('class="registered-user-card device-card"', false)
            ->assertSee('class="registered-user-top device-owner-header"', false)
            ->assertSee('class="device-phone"', false)
            ->assertSee('class="device-card-content"', false)
            ->assertSee('class="registered-user-status is-active"', false)
            ->assertSee('Unknown')
            ->assertSee('class="device-specs"', false)
            ->assertSee('Manufacturer')
            ->assertSee('Unknown')
            ->assertSee('GPS Status')
            ->assertSee('Battery')
            ->assertSee('Last Update')
            ->assertDontSee('device.owner@example.com</span>', false)
            ->assertDontSee('9876543210</span>', false)
            ->assertDontSee('123 Demo Device Street</span>', false)
            ->assertSee('data-entity="user"', false)
            ->assertSee('class="action-button edit-button"', false)
            ->assertSee('data-user-id="'.$user->id.'"', false)
            ->assertSee('>Edit</button>', false)
            ->assertSee('>Track</a>', false)
            ->assertSee('>Report</a>', false)
            ->assertSee(route('admin.tracking.live-location'), false)
            ->assertSee(route('admin.user-management.users.show', $user), false)
            ->assertDontSee('view-button', false)
            ->assertSee('data-create-action="'.route('admin.user-management.users.store').'"', false)
            ->assertSee('data-update-action-template="'.route('admin.user-management.users.update', ['user' => '__USER__']).'"', false)
            ->assertSee(route('admin.user-management.devices'), false);

        $response->assertSee('Total users')->assertSee('Add user');

        $this->post(route('admin.user-management.devices'), [
            'name' => 'Should Not Be Saved',
            'email' => 'not.saved@example.com',
            'phone' => '9876543211',
            'password' => 'StrongPassword!123',
        ])->assertMethodNotAllowed();
        $this->assertDatabaseCount('users', 1);

        $this->post(route('admin.user-management.users.store'), [
            'name' => 'Created From Device Page',
            'email' => 'created.from.devices@example.com',
            'phone' => '9876543211',
            'password' => 'StrongPassword!123',
        ])->assertRedirect(route('admin.user-management.devices'));
        $this->assertDatabaseHas('users', ['email' => 'created.from.devices@example.com']);
        $this->assertDatabaseHas('devices', [
            'user_id' => User::where('email', 'created.from.devices@example.com')->value('id'),
            'manufacturer' => 'Unknown',
            'device_model' => 'Unknown',
        ]);

        $this->get(route('admin.user-management.users'))
            ->assertOk()
            ->assertSee('class="registered-user-card"', false)
            ->assertDontSee('device-phone', false);
    }

    public function test_admin_can_edit_a_registered_user_from_the_edit_popup(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.user-management.users.update', $user), [
                'name' => 'Updated Popup User',
                'email' => 'updated.popup@example.com',
                'phone' => '9876543211',
                'address' => 'Mumbai, India',
                'password' => '',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.user-management.users'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Popup User',
            'email' => 'updated.popup@example.com',
            'phone' => '9876543211',
            'address' => 'Mumbai, India',
            'status' => 'active',
        ]);
    }

    public function test_all_private_pages_redirect_guests(): void
    {
        foreach (['dashboard', 'notifications', 'user-management/users', 'user-management/devices', 'user-management/reports', 'tracking/live-location', 'tracking/geo-fence', 'tracking/task', 'groups', 'licence', 'licence/subscription', 'licence/payments', 'support', 'profile', 'developer/activity-logs', 'developer/activity-logs/1', 'security/active-sessions', 'developer/error-log', 'security/developer-mode'] as $path) {
            $this->get('/admin/'.$path)->assertRedirect(route('admin.login'));
        }
    }

    public function test_all_mutations_require_authentication(): void
    {
        foreach (['patch' => ['profile'], 'put' => ['profile/photo', 'profile/logo', 'profile/password'], 'delete' => ['profile/photo', 'profile/logo', 'security/active-sessions'], 'post' => ['logout']] as $method => $paths) {
            foreach ($paths as $path) {
                $this->$method('/admin/'.$path)->assertRedirect(route('admin.login'));
            }
        }
    }

    public function test_login_success_regenerates_session_and_logs_event(): void
    {
        $admin = Admin::factory()->create();
        $this->get('/admin/login');
        $before = session()->getId();
        $this->post('/admin/login', [
            'email' => strtoupper($admin->email),
            'password' => 'TestPassword!123',
            'latitude' => '28.6139',
            'longitude' => '77.2090',
            'location_status' => 'granted',
        ])->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('admin_location.latitude', 28.6139)
            ->assertSessionHas('admin_location.longitude', 77.209)
            ->assertSessionHas('admin_location.permission_status', 'granted');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotSame($before, session()->getId());
        $this->assertDatabaseHas('admin_activity_logs', ['admin_id' => $admin->id, 'action' => 'login.success']);
        $this->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_login_continues_when_location_permission_is_denied(): void
    {
        $admin = Admin::factory()->create();

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'TestPassword!123',
            'location_status' => 'denied',
        ])->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('admin_location.permission_status', 'denied');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNull(session('admin_location.latitude'));
        $this->assertNull(session('admin_location.longitude'));
    }

    public function test_admin_weather_location_is_saved_to_its_own_session(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.session.location'), [
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'permission_status' => 'granted',
            ]);

        $response->assertOk()->assertExactJson(['saved' => true]);
        $this->assertSame(40.7128, session('admin_location.latitude'));
        $this->assertSame(-74.006, session('admin_location.longitude'));
        $this->get('/admin/dashboard')
            ->assertSee('data-latitude="40.7128"', false)
            ->assertSee('data-longitude="-74.006"', false);

        $this->postJson(route('admin.session.location'), ['permission_status' => 'denied'])
            ->assertOk()
            ->assertExactJson(['saved' => true]);
        $this->assertSame(40.7128, session('admin_location.latitude'));
        $this->assertSame('denied', session('admin_location.permission_status'));
    }

    public function test_standalone_asset_urls_are_fingerprinted_and_admin_theme_is_scoped(): void
    {
        if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('The standalone asset fallback is not active.');
        }

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('data-login-location', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee(asset('assets/app.css').'?v='.hash_file('sha256', public_path('assets/app.css')), false)
            ->assertSee(asset('assets/app.js').'?v='.hash_file('sha256', public_path('assets/app.js')), false)
            ->assertDontSee(asset('assets/admin-theme.css'), false);

        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->get('/admin/dashboard')
            ->assertOk()
            ->assertSee(asset('assets/admin-theme.css').'?v='.hash_file('sha256', public_path('assets/admin-theme.css')), false);
    }

    public function test_login_failure_is_generic_and_logged_without_credentials(): void
    {
        $admin = Admin::factory()->create();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secret-wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $log = AdminActivityLog::firstOrFail();
        $this->assertSame('login.failed', $log->action);
        $this->assertNull($log->admin_id);
        $this->assertStringNotContainsString('secret-wrong-password', $log->toJson());
        $this->assertStringNotContainsString($admin->email, $log->toJson());
    }

    public function test_login_limiter_blocks_after_five_failures(): void
    {
        $admin = Admin::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $response = $this->post('/admin/login', ['email' => $admin->email, 'password' => 'TestPassword!123']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('email'));
        $this->assertGuest('admin');
    }

    public function test_login_ip_limiter_cannot_be_bypassed_by_changing_email(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/admin/login', ['email' => 'missing'.$i.'@example.com', 'password' => 'wrong']);
        }
        $this->post('/admin/login', ['email' => 'missing21@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_private_pages_render_with_exact_menu_and_real_empty_states(): void
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin, 'admin');
        $this->assertNotEmpty(config('services.google_maps.api_key'));
        $response = $this->get('/admin/dashboard');
        $response->assertSee('id="admin-weather"', false)
            ->assertSee('id="admin-weather-feels-like"', false)
            ->assertSee('id="admin-weather-humidity"', false)
            ->assertSee('id="admin-weather-wind"', false)
            ->assertSee('id="admin-weather-coordinates"', false)
            ->assertSee('aria-label="Refresh location and weather"', false)
            ->assertSee('<button type="button" class="icon-button admin-location-button" id="admin-location-button" aria-label="Refresh location and weather" title="Refresh location and weather"><svg class="icon" viewBox="0 0 24 24"', false)
            ->assertSee('IP 127.0.0.1')
            ->assertSee(route('admin.session.location'), false)
            ->assertDontSee('workspace-search', false);
        $response->assertOk()->assertSee('class="auth-tech-bg"', false)->assertSee('class="auth-circuit-flow"', false)->assertSee('aria-label="GPS tracking map of India"', false)->assertSee('id="admin-traffic-map"', false)->assertDontSee('Live Now')->assertDontSee('SAMPLE DATA')->assertSee('Total devices')->assertSee('1,284')->assertSee('1,126 active')->assertSee('158 inactive')->assertSee('User licenses')->assertSee('620')->assertSee('572 active')->assertSee('48 expired')->assertSee('Users online')->assertSee('184')->assertSee('Users offline')->assertSee('936')->assertDontSee('Ferry arrival')->assertDontSee('Train speed')->assertDontSee('Recent activity')->assertDontSee('Your account')->assertSee($admin->name)->assertSee('127.0.0.1')->assertSee('sidebar-admin-ip')->assertSee('brand-live-clock')->assertDontSee('sidebar-admin-clock')->assertSee('data-clock', false)->assertSee('data-timezone', false)->assertDontSee('Realtime')->assertDontSee('Admin access');
        $this->assertStringContainsString('https://*.googleapis.com', $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('https://api.open-meteo.com', $response->headers->get('Content-Security-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=(self)', $response->headers->get('Permissions-Policy'));
        foreach (['profile', 'developer/activity-logs', 'security/active-sessions', 'developer/error-log', 'developer/mobile-app', 'developer/settings', 'security/developer-mode', 'notifications', 'user-management/users', 'user-management/devices', 'user-management/reports', 'tracking/live-location', 'tracking/geo-fence', 'tracking/task', 'tracking/sos', 'groups', 'licence', 'licence/subscription', 'licence/payments', 'support'] as $path) {
            $this->get('/admin/'.$path)->assertOk()->assertSee('Eemo')->assertSee('Notification</span>', false)->assertSee('<span>SOS &amp; Alert</span>', false)->assertDontSee('user-management-submenu', false)->assertDontSee('<span>User</span>', false)->assertSee('Device')->assertDontSee('<span>Reports</span>', false)->assertSee('Tracking')->assertSee('Live &amp; History', false)->assertSee('<span>Geofence</span>', false)->assertDontSee('Geofence &amp; SOS', false)->assertDontSee('<span>SOS</span>', false)->assertDontSee('<span>Alert</span>', false)->assertSee('Task')->assertSee('Group')->assertSee('Licence')->assertSee('Subscription')->assertSee('Payments')->assertSee('Support')->assertSee('Admin Profile')->assertDontSee('<span>My Profile</span>', false)->assertSee('Activity Log')->assertSee('Active Sessions')->assertSee('Error Log')->assertSee('<span>Developer</span>', false)->assertDontSee('<span>Developer Mode</span>', false);
        }
        $this->get('/admin/notifications')->assertSee('Notification</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/user-management/users')->assertDontSee('<span>User</span>', false)->assertSee('<span>User &amp; Device</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/user-management/devices')->assertSee('User &amp; Device</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/user-management/reports')->assertSee('Reports</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/tracking/live-location')
            ->assertSee('Live Location</span>', false)
            ->assertSee('Current location')
            ->assertSee('Route history')
            ->assertSee('class="location-map location-map-current"', false)
            ->assertSee('class="location-map location-map-route"', false)
            ->assertSee('28.6129° N, 77.2295° E')
            ->assertSee('18.6 km')
            ->assertSee('sample demo data')
            ->assertDontSee('TRACKING / GPS')
            ->assertDontSee('View a device’s current position and its recent route history.')
            ->assertDontSee('class="demo-data-badge"', false)
            ->assertDontSee('<h1 id="live-location-title">Live Location</h1>', false);
        $this->get('/admin/tracking/history')->assertNotFound();
        $this->get('/admin/tracking/geo-fence')->assertSee('Geofence</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/tracking/task')->assertSee('Task</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/tracking/sos')->assertSee('SOS</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/groups')->assertSee('Group</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/licence')->assertSee('Licence</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/licence/subscription')->assertSee('Subscription</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/licence/payments')->assertSee('Payments</span>', false)->assertDontSee('<h1', false);
        $this->get('/admin/support')->assertSee('Support</span>', false)->assertDontSee('<h1', false);
        $activityLogs = $this->get('/admin/developer/activity-logs')
            ->assertSee('Activity Log</span>', false)
            ->assertSee('>Developer</span>', false)
            ->assertSee('<h1>Activity Log', false);
        $developerMode = $this->get('/admin/security/developer-mode')
            ->assertSee('Developer</span>', false)
            ->assertDontSee('<h1', false);
        $content = $developerMode->getContent();
        $this->assertStringContainsString('id="developer-submenu"', $content);
        $securitySubmenuEnd = strpos($content, '</div>', strpos($content, 'id="security-submenu"'));
        $developerMenuPosition = strpos($content, 'id="developer-toggle"');
        $this->assertGreaterThan($securitySubmenuEnd, $developerMenuPosition);
        $developerSubmenuStart = strpos($content, 'id="developer-submenu"');
        $developerSubmenuEnd = strpos($content, '</div>', $developerSubmenuStart);
        $developerSubmenu = substr($content, $developerSubmenuStart, $developerSubmenuEnd - $developerSubmenuStart);
        $this->assertStringContainsString('href="'.route('admin.developer.activity-logs.index').'"', $developerSubmenu);
        $this->assertStringContainsString('href="'.route('admin.developer.error-log').'"', $developerSubmenu);
        $this->assertStringContainsString('href="'.route('admin.developer.mobile-app').'"', $developerSubmenu);
        $this->assertStringContainsString('href="'.route('admin.developer.settings').'"', $developerSubmenu);
        $this->assertLessThan(
            strpos($developerSubmenu, 'href="'.route('admin.developer.mobile-app').'"'),
            strpos($developerSubmenu, 'href="'.route('admin.developer.settings').'"')
        );
        $this->assertLessThan(
            strpos($developerSubmenu, 'href="'.route('admin.developer.activity-logs.index').'"'),
            strpos($developerSubmenu, 'href="'.route('admin.developer.mobile-app').'"')
        );
        $this->assertLessThan(
            strpos($developerSubmenu, 'href="'.route('admin.developer.error-log').'"'),
            strpos($developerSubmenu, 'href="'.route('admin.developer.activity-logs.index').'"')
        );
        $this->assertStringNotContainsString('href="'.route('admin.security.developer-mode').'"', $developerSubmenu);
        $this->assertStringNotContainsString('href="'.route('admin.developer.activity-logs.index').'"', substr($content, strpos($content, 'id="security-submenu"'), strpos($content, 'id="developer-toggle"') - strpos($content, 'id="security-submenu"')));
        $this->get('/admin/developer/mobile-app')->assertSee('Mobile App</span>', false)->assertSee('Mobile App')->assertDontSee('<h1', false);
        $this->get('/admin/developer/settings')->assertSee('Settings</span>', false)->assertSee('Settings')->assertDontSee('<h1', false);
    }

    public function test_profile_update_normalizes_email_and_excludes_privilege_input(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->patch('/admin/profile', ['name' => 'Updated Admin', 'email' => 'UPDATED@EXAMPLE.COM', 'is_super_admin' => true])->assertSessionHasNoErrors();
        $admin->refresh();
        $this->assertSame('updated@example.com', $admin->email);
        $this->assertSame('Updated Admin', $admin->name);
        $this->assertFalse($admin->is_super_admin);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'profile.updated', 'admin_id' => $admin->id]);
    }

    public function test_profile_validation_rejects_missing_name_invalid_and_duplicate_email(): void
    {
        $admin = Admin::factory()->create();
        $other = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->patch('/admin/profile', ['name' => '', 'email' => 'invalid'])->assertSessionHasErrors(['name', 'email']);
        $this->patch('/admin/profile', ['name' => $admin->name, 'email' => $other->email])->assertSessionHasErrors('email');
        $this->patch('/admin/profile', ['name' => $admin->name, 'email' => $admin->email])->assertSessionHasNoErrors();
    }

    public function test_email_change_invalidates_previously_issued_recovery_link(): void
    {
        $admin = Admin::factory()->create();
        $previousEmail = $admin->email;
        Password::broker('admins')->createToken($admin);
        $this->assertDatabaseHas('admin_password_reset_tokens', ['email' => $previousEmail]);
        $this->actingAs($admin, 'admin')->patch('/admin/profile', ['name' => $admin->name, 'email' => 'changed@example.com'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('admin_password_reset_tokens', ['email' => $previousEmail]);
    }

    public function test_expired_recovery_link_does_not_change_password(): void
    {
        $admin = Admin::factory()->create();
        $token = Password::broker('admins')->createToken($admin);
        DB::table('admin_password_reset_tokens')->where('email', $admin->email)->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/admin/reset-password', ['email' => $admin->email, 'token' => $token, 'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('TestPassword!123', $admin->fresh()->password));
    }

    public function test_authenticated_mutations_require_csrf_tokens(): void
    {
        $this->actingAs(Admin::factory()->create(['is_super_admin' => true]), 'admin');
        $this->app['env'] = 'local';
        foreach (['patch' => ['profile'], 'put' => ['profile/photo', 'profile/logo', 'profile/password'], 'delete' => ['profile/photo', 'profile/logo', 'security/active-sessions'], 'post' => ['logout']] as $method => $paths) {
            foreach ($paths as $path) {
                $this->$method('/admin/'.$path)->assertStatus(419);
            }
        }
    }

    public function test_photo_upload_replace_remove_and_audit(): void
    {
        Storage::fake('public');
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->put('/admin/profile/photo', ['photo' => UploadedFile::fake()->image('photo.jpg', 100, 100)])->assertSessionHasNoErrors();
        $old = $admin->fresh()->photo_path;
        Storage::disk('public')->assertExists($old);
        $this->assertStringEndsWith('.png', $old);
        $this->put('/admin/profile/photo', ['photo' => UploadedFile::fake()->image('replacement.png', 120, 120)])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $new = $admin->fresh()->photo_path;
        $this->delete('/admin/profile/photo')->assertSessionHasNoErrors();
        $this->assertNull($admin->fresh()->photo_path);
        Storage::disk('public')->assertMissing($new);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'photo.removed']);
    }

    public function test_upload_validation_rejects_non_images_oversize_small_and_large_dimensions(): void
    {
        Storage::fake('public');
        $this->actingAs(Admin::factory()->create(['is_super_admin' => true]), 'admin');
        foreach ([
            UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('oversize.png', 100, 100)->size(3000),
            UploadedFile::fake()->image('tiny.png', 10, 10),
            UploadedFile::fake()->image('wide.png', 2001, 40),
        ] as $file) {
            $this->put('/admin/profile/photo', ['photo' => $file])->assertSessionHasErrors('photo');
        }
        $this->put('/admin/profile/logo', ['logo' => UploadedFile::fake()->image('tiny.png', 10, 10)])->assertSessionHasErrors('logo');
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_super_admin_can_upload_replace_remove_shared_logo(): void
    {
        Storage::fake('public');
        $this->actingAs(Admin::factory()->create(['is_super_admin' => true]), 'admin');
        $this->put('/admin/profile/logo', ['logo' => UploadedFile::fake()->image('logo.png', 128, 64)])->assertSessionHasNoErrors();
        $old = app(BrandingService::class)->path();
        Storage::disk('public')->assertExists($old);
        $this->get('/admin/dashboard')->assertSee(Storage::disk('public')->url($old));
        $this->put('/admin/profile/logo', ['logo' => UploadedFile::fake()->image('logo2.png', 128, 64)])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $new = app(BrandingService::class)->path();
        $this->delete('/admin/profile/logo')->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($new);
        $this->assertNull(app(BrandingService::class)->path());
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'logo.updated']);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'logo.removed']);
    }

    public function test_regular_admin_cannot_change_branding(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->put('/admin/profile/logo')->assertForbidden();
        $this->delete('/admin/profile/logo')->assertForbidden();
        $this->get('/admin/profile')->assertSee('Only a Super Admin');
    }

    public function test_password_change_requires_current_password_confirmation_and_strength(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->put('/admin/profile/password', ['current_password' => 'wrong', 'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123'])->assertSessionHasErrors('current_password');
        $this->put('/admin/profile/password', ['current_password' => 'TestPassword!123', 'password' => 'weak', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('TestPassword!123', $admin->fresh()->password));
        $this->assertNull(session()->getOldInput('current_password'));
    }

    public function test_password_change_hashes_and_removes_other_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $admin = Admin::factory()->create();
        $this->sessionRow($admin, 'other-session');
        $this->actingAs($admin, 'admin')->put('/admin/profile/password', ['current_password' => 'TestPassword!123', 'password' => 'NewPassword!123', 'password_confirmation' => 'NewPassword!123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword!123', $admin->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->get('/admin/dashboard')->assertOk();
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'password.changed']);
        $this->assertStringNotContainsString('NewPassword!123', AdminActivityLog::first()->toJson());
    }

    public function test_stale_password_hash_is_rejected_on_next_request(): void
    {
        $admin = Admin::factory()->create();
        $oldHash = $admin->password;
        $admin->update(['password' => 'ChangedPassword!123']);
        $this->actingAs($admin->fresh(), 'admin')->withSession(['password_hash_admin' => $oldHash])->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_audit_metadata_is_allowlisted_and_detail_is_read_only(): void
    {
        $admin = Admin::factory()->create();
        $log = app(ActivityLogger::class)->log('profile.updated', $admin, [
            'password' => 'do-not-record', 'token' => 'secret-token', 'session_id' => 'secret-session',
            'changed_fields' => ['name', 'email', 'password', 'is_super_admin'],
        ]);
        $this->assertSame(['changed_fields' => ['name', 'email']], $log->metadata);
        $this->assertStringNotContainsString('do-not-record', $log->toJson());
        $this->actingAs($admin, 'admin')->get('/admin/developer/activity-logs/'.$log->id)->assertOk()->assertSee('Updated profile information');
        $this->delete('/admin/developer/activity-logs/'.$log->id)->assertStatus(405);
        $this->patch('/admin/developer/activity-logs/'.$log->id)->assertStatus(405);
    }

    public function test_regular_admin_cannot_read_other_admin_audit_records_or_bypass_search_scope(): void
    {
        $admin = Admin::factory()->create();
        $other = Admin::factory()->create();
        $log = app(ActivityLogger::class)->log('password.changed', $other);
        $this->actingAs($admin, 'admin')->get('/admin/developer/activity-logs/'.$log->id)->assertForbidden();
        $this->get('/admin/developer/activity-logs?search=password')->assertOk()->assertDontSee('password.changed');
        $this->get('/admin/developer/activity-logs?admin_id='.$other->id)->assertOk()->assertDontSee($other->name);
    }

    public function test_super_admin_audit_filters_pagination_and_escaped_output(): void
    {
        $admin = Admin::factory()->create(['is_super_admin' => true, 'name' => '<script>alert(1)</script>']);
        for ($i = 0; $i < 18; $i++) {
            app(ActivityLogger::class)->log('login.success', $admin);
        }
        $this->actingAs($admin, 'admin')->get('/admin/developer/activity-logs?search=login&admin_id='.$admin->id.'&from='.now()->format('Y-m-d').'&to='.now()->format('Y-m-d'))
            ->assertOk()->assertSee('18 events')->assertSee('Page 1 of 2')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/admin/developer/activity-logs?from=invalid')->assertSessionHasErrors('from');
    }

    public function test_date_filters_allow_either_bound_independently_and_reject_reversed_range(): void
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin, 'admin');
        $this->get('/admin/developer/activity-logs?to='.now()->format('Y-m-d'))->assertOk()->assertSessionHasNoErrors();
        $this->get('/admin/developer/activity-logs?from='.now()->format('Y-m-d'))->assertOk()->assertSessionHasNoErrors();
        $this->get('/admin/developer/activity-logs?from=2026-10-12&to=2026-10-01')->assertSessionHasErrors('to');
    }

    public function test_sessions_are_scoped_and_ids_are_not_rendered(): void
    {
        config(['session.driver' => 'database']);
        $admin = Admin::factory()->create();
        $other = Admin::factory()->create();
        $this->sessionRow($admin, 'raw-session-secret', 'Browser A', null, [
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'permission_status' => 'granted',
        ]);
        $this->sessionRow($admin, 'location-not-shared', 'Browser without location');
        $this->sessionRow($other, 'other-secret', 'Other admin browser');
        $this->sessionRow($admin, 'expired-secret', 'Expired browser', now()->subHours(3)->timestamp);
        $this->actingAs($admin, 'admin')->get('/admin/security/active-sessions')->assertOk()->assertSee('Browser A')
            ->assertSee($admin->name)->assertSee('Other session')->assertSee('37.77490, -122.41940')
            ->assertSee('maps.google.com/maps?q=37.7749,-122.4194', false)
            ->assertSee('name="reference"', false)->assertSee('Log out other session')
            ->assertSee('Location not shared by browser')->assertSee('Precise location unavailable')
            ->assertDontSee('raw-session-secret')->assertDontSee('Other admin browser')->assertDontSee('Expired browser');
    }

    public function test_revoke_other_session_is_server_side_and_owner_scoped(): void
    {
        config(['session.driver' => 'database']);
        $admin = Admin::factory()->create();
        $other = Admin::factory()->create();
        $this->sessionRow($admin, 'own-device');
        $this->sessionRow($other, 'other-device');
        $this->actingAs($admin, 'admin')->delete('/admin/security/active-sessions', ['reference' => Crypt::encryptString('other-device')])->assertNotFound();
        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);
        $this->delete('/admin/security/active-sessions', ['reference' => 'tampered'])->assertNotFound();
        $this->delete('/admin/security/active-sessions', ['reference' => Crypt::encryptString('own-device')])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'own-device']);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'session.revoked']);
    }

    public function test_revoke_current_session_logs_out_and_rotates_session(): void
    {
        config(['session.driver' => 'database']);
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->get('/admin/dashboard');
        $id = session()->getId();
        session()->save();
        $this->withCookie(config('session.cookie'), $id)->delete('/admin/security/active-sessions', ['reference' => Crypt::encryptString($id)])->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
        $this->assertDatabaseMissing('sessions', ['id' => $id]);
        $this->assertNotSame($id, session()->getId());
    }

    public function test_unsupported_session_driver_has_clear_limitation_and_blocks_revocation(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')->get('/admin/security/active-sessions')->assertOk()->assertSee('Session listing is unavailable')->assertSee('SESSION_DRIVER=database');
        $this->delete('/admin/security/active-sessions', ['reference' => Crypt::encryptString('unknown')])->assertStatus(409);
    }

    public function test_logout_invalidates_session_and_csrf_token(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin')->get('/admin/dashboard');
        $id = session()->getId();
        $token = session()->token();
        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
        $this->assertNotSame($id, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'logout']);
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_password_reset_sends_admin_notification_with_configured_host_and_generic_response(): void
    {
        Notification::fake();
        $admin = Admin::factory()->create();
        config(['app.url' => 'https://admin.example.com']);
        $known = $this->post('/admin/forgot-password', ['email' => $admin->email]);
        $known->assertSessionHas('status', 'If an admin account matches this email, a reset link has been sent.');
        Notification::assertSentTo($admin, ResetPassword::class, function ($notification) use ($admin) {
            $this->assertStringStartsWith('https://admin.example.com/admin/reset-password/', $notification->toMail($admin)->actionUrl);

            return true;
        });
        $this->post('/admin/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('status', session('status'));
        $this->assertDatabaseHas('admin_password_reset_tokens', ['email' => $admin->email]);
    }

    public function test_password_reset_rejects_invalid_token_and_accepts_valid_once(): void
    {
        config(['session.driver' => 'database']);
        $admin = Admin::factory()->create();
        $this->sessionRow($admin, 'old-device');
        $data = ['email' => $admin->email, 'token' => 'invalid', 'password' => 'ResetPassword!123', 'password_confirmation' => 'ResetPassword!123'];
        $this->post('/admin/reset-password', $data)->assertSessionHasErrors('email');
        $data['token'] = Password::broker('admins')->createToken($admin);
        $this->get('/admin/reset-password/'.$data['token'].'?email='.$admin->email)->assertOk();
        $this->post('/admin/reset-password', $data)->assertRedirect(route('admin.login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('ResetPassword!123', $admin->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device']);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'password.reset']);
        $this->post('/admin/reset-password', $data)->assertSessionHasErrors('email');
    }

    public function test_reset_endpoints_are_rate_limited(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/admin/forgot-password', ['email' => 'unknown@example.com']);
        }
        $this->post('/admin/forgot-password', ['email' => 'unknown@example.com'])->assertStatus(429);
    }

    public function test_csrf_protects_state_changing_requests_and_headers_are_present(): void
    {
        // Laravel normally skips CSRF in tests. Switch environment for this request.
        $this->app['env'] = 'local';
        $this->post('/admin/login', ['email' => 'unknown@example.com', 'password' => 'wrong'])->assertStatus(419);
        $this->get('/admin/login')->assertOk()->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
    }

    private function sessionRow(Admin $admin, string $id, string $agent = 'Test Browser', ?int $last = null, ?array $location = null): void
    {
        $attributes = $location === null ? [] : ['admin_location' => $location];
        $payload = config('session.serialization', 'php') === 'json'
            ? json_encode($attributes, JSON_THROW_ON_ERROR)
            : serialize($attributes);
        if (config('session.encrypt')) {
            $payload = Crypt::encrypt($payload);
        }

        DB::table('sessions')->updateOrInsert(['id' => $id], [
            'user_id' => $admin->id, 'ip_address' => '127.0.0.1', 'user_agent' => $agent,
            'payload' => base64_encode($payload), 'last_activity' => $last ?? now()->timestamp,
        ]);
    }
}
