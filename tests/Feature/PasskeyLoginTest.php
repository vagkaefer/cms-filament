<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Spatie\LaravelPasskeys\Events\PasskeyUsedToAuthenticateEvent;
use VagKaefer\CmsFilament\Actions\Passkeys\ConfigurePasskeyCeremonies;
use VagKaefer\CmsFilament\Http\Controllers\PasskeyLoginController;
use VagKaefer\CmsFilament\Providers\CmsFilamentServiceProvider;
use VagKaefer\CmsFilament\Tests\Fixtures\AdminPanelProvider;
use VagKaefer\CmsFilament\Tests\Fixtures\FakeFindPasskeyAction;
use VagKaefer\CmsFilament\Tests\Fixtures\Passkeys;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * Entrar no painel com passkey, sem senha e sem o código do segundo fator.
 *
 * A cerimônia WebAuthn (assinatura do autenticador) é do spatie e fica de
 * fora: o FakeFindPasskeyAction devolve a passkey "reconhecida". O que se testa
 * é a regra do painel em volta dela.
 */
class PasskeyLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), AdminPanelProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('passkeys.actions.find_passkey', FakeFindPasskeyAction::class);
    }

    protected function tearDown(): void
    {
        FakeFindPasskeyAction::$passkey = null;

        parent::tearDown();
    }

    private function user(bool $active = true): User
    {
        $user = User::create([
            'name' => 'Redator',
            'email' => 'redator@exemplo.com',
            'password' => 'secret',
        ]);

        $user->forceFill(['active' => $active])->save();

        return $user;
    }

    /**
     * Pede as opções ao servidor, como o botão faz, e devolve a resposta do
     * "navegador".
     */
    private function enter(): TestResponse
    {
        $this->get('/painel/passkeys/opcoes')->assertOk();

        return $this->post('/painel/passkeys/entrar', [
            'start_authentication_response' => json_encode(['id' => 'qualquer']),
        ]);
    }

    /**
     * O botão entra pelo render hook do formulário de login. O hook é
     * renderizado direto: a página de login inteira não sobe no Testbench.
     */
    private function loginHook(): string
    {
        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();

        // O @js do Blade escapa as barras das URLs.
        return str_replace('\\/', '/', FilamentView::renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER)->toHtml());
    }

    public function testLoginFormOffersThePasskeyButton(): void
    {
        $html = $this->loginHook();

        $this->assertStringContainsString('Entrar com passkey', $html);
        $this->assertStringContainsString('/painel/passkeys/entrar', $html);
        $this->assertStringContainsString('/painel/passkeys/opcoes', $html);
    }

    public function testOptionsCarryAChallengeForTheRelyingParty(): void
    {
        $options = $this->get('/painel/passkeys/opcoes')
            ->assertOk()
            ->json();

        $this->assertNotEmpty($options['challenge']);
        $this->assertSame('localhost', $options['rpId']);
        $this->assertNotNull(session('passkey-authentication-options'));
    }

    public function testRelyingPartyIdComesFromTheEnvironment(): void
    {
        config(['cms-filament.passkeys.rp_id' => 'exemplo.com.br']);
        (new CmsFilamentServiceProvider($this->app))->boot();

        $this->assertSame('exemplo.com.br', config('passkeys.relying_party.id'));
    }

    public function testUsesTheCeremonyRulesOfThePackage(): void
    {
        $this->assertSame(
            ConfigurePasskeyCeremonies::class,
            config('passkeys.actions.configure_ceremony_step_manager_factory')
        );
        $this->assertSame(User::class, config('passkeys.models.authenticatable'));
    }

    public function testRecognizedPasskeyLogsInWithoutSecondFactor(): void
    {
        Event::fake([PasskeyUsedToAuthenticateEvent::class]);

        $user = $this->user();
        // Mesmo com o app autenticador ligado, a passkey entra direto.
        $user->forceFill(['app_authentication_secret' => 'segredo'])->save();
        FakeFindPasskeyAction::$passkey = Passkeys::create($user);

        $this->enter()->assertRedirect('/painel');

        $this->assertAuthenticatedAs($user);
        Event::assertDispatched(PasskeyUsedToAuthenticateEvent::class);
    }

    public function testDeactivatedAccountDoesNotGetIn(): void
    {
        FakeFindPasskeyAction::$passkey = Passkeys::create($this->user(active: false));

        $this->enter()
            ->assertRedirect('/painel/login')
            ->assertSessionHas(PasskeyLoginController::ERROR_SESSION_KEY);

        $this->assertGuest();
    }

    public function testUnknownPasskeyDoesNotGetIn(): void
    {
        $this->user();

        $this->enter()
            ->assertRedirect('/painel/login')
            ->assertSessionHas(PasskeyLoginController::ERROR_SESSION_KEY);

        $this->assertGuest();
    }

    public function testAnswerWithoutAChallengeIsRefused(): void
    {
        FakeFindPasskeyAction::$passkey = Passkeys::create($this->user());

        // Sem passar pelas opções: não há desafio na sessão para conferir.
        $this->post('/painel/passkeys/entrar', [
            'start_authentication_response' => json_encode(['id' => 'qualquer']),
        ])->assertRedirect('/painel/login');

        $this->assertGuest();
    }

    public function testChallengeIsSingleUse(): void
    {
        $user = $this->user();
        FakeFindPasskeyAction::$passkey = Passkeys::create($user);

        $this->enter()->assertRedirect('/painel');
        auth()->logout();

        $this->post('/painel/passkeys/entrar', [
            'start_authentication_response' => json_encode(['id' => 'qualquer']),
        ])->assertRedirect('/painel/login');

        $this->assertGuest();
    }

    public function testLoginFormShowsTheFailure(): void
    {
        $this->user();
        $this->enter();

        $this->assertStringContainsString('Não foi possível entrar com esta passkey.', $this->loginHook());
    }
}
