<?php

namespace Tests\Feature\Auth;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;
use Tests\Concerns\RegistersVerifiedUsers;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use RegistersVerifiedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/Register')
                ->has('turnstileSiteKey')
            );
    }

    public function test_registration_requires_verified_email_token(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SmsPortal-Test-Pass1!',
            'password_confirmation' => 'SmsPortal-Test-Pass1!',
            'company_name' => 'Test Company Ltd',
            'website' => '',
        ]);

        $response->assertSessionHasErrors(['registration_token']);
        $this->assertGuest();
    }

    public function test_new_users_can_register_after_email_otp(): void
    {
        $response = $this->registerVerifiedUser([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->email_verified_at)->not->toBeNull();
    }

    public function test_honeypot_blocks_registration(): void
    {
        Mail::fake();

        $this->postJson(route('register.otp.send'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'website' => 'https://spam.example',
        ])->assertUnprocessable();

        Mail::assertNothingSent();
    }

    public function test_otp_send_and_verify_flow(): void
    {
        Mail::fake();

        $this->postJson(route('register.otp.send'), [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'website' => '',
        ])->assertOk();

        Mail::assertSent(RegistrationOtpMail::class);

        $code = null;
        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->postJson(route('register.otp.verify'), [
            'email' => 'ada@example.com',
            'code' => '000000',
            'website' => '',
        ])->assertUnprocessable();

        $this->postJson(route('register.otp.verify'), [
            'email' => 'ada@example.com',
            'code' => $code,
            'website' => '',
        ])->assertOk()
            ->assertJsonStructure(['registration_token']);
    }
}
