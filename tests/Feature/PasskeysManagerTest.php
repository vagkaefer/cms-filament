<?php

namespace VagKaefer\CmsFilament\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VagKaefer\CmsFilament\Livewire\PasskeysManager;
use VagKaefer\CmsFilament\Tests\Fixtures\AdminPanelProvider;
use VagKaefer\CmsFilament\Tests\Fixtures\Passkeys;
use VagKaefer\CmsFilament\Tests\Fixtures\User;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * Seção "Passkeys" do perfil: cada um vê e remove só as próprias.
 */
class PasskeysManagerTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => 'Usuário',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    public function testListsOnlyTheOwnPasskeys(): void
    {
        $me = $this->user('eu@exemplo.com');
        $other = $this->user('outro@exemplo.com');
        Passkeys::create($me, 'Meu notebook');
        Passkeys::create($other, 'Celular do outro');

        $this->actingAs($me);

        Livewire::test(PasskeysManager::class)
            ->assertSee('Meu notebook')
            ->assertDontSee('Celular do outro');
    }

    public function testEmptyStateWhenThereIsNoPasskey(): void
    {
        $this->actingAs($this->user('eu@exemplo.com'));

        Livewire::test(PasskeysManager::class)
            ->assertSee('Nenhuma passkey cadastrada.');
    }

    public function testRemovesAnOwnPasskey(): void
    {
        $me = $this->user('eu@exemplo.com');
        $passkey = Passkeys::create($me);

        $this->actingAs($me);

        Livewire::test(PasskeysManager::class)
            ->callAction('deletePasskey', arguments: ['passkey' => $passkey->id])
            ->assertNotified('Passkey removida.');

        $this->assertSame(0, $me->passkeys()->count());
    }

    public function testCannotRemoveSomeoneElsesPasskey(): void
    {
        $me = $this->user('eu@exemplo.com');
        $passkey = Passkeys::create($this->user('outro@exemplo.com'));

        $this->actingAs($me);

        Livewire::test(PasskeysManager::class)
            ->callAction('deletePasskey', arguments: ['passkey' => $passkey->id]);

        $this->assertNotNull($passkey->fresh());
    }

    public function testAddingAsksTheBrowserWithRegistrationOptions(): void
    {
        $this->actingAs($this->user('eu@exemplo.com'));

        Livewire::test(PasskeysManager::class)
            ->callAction('addPasskey', data: ['name' => 'Notebook'])
            ->assertDispatched('passkeyPropertiesValidated');

        $options = json_decode((string) session('passkey-registration-options'), true);

        $this->assertSame('localhost', $options['rp']['id']);
        $this->assertSame('eu@exemplo.com', $options['user']['name']);
        // ES256 primeiro: o Bitwarden só aceita ele.
        $this->assertSame([-7, -257], array_column($options['pubKeyCredParams'], 'alg'));
    }

    public function testDeviceWithAPasskeyIsNotAskedToCreateAnother(): void
    {
        $me = $this->user('eu@exemplo.com');
        Passkeys::create($me);
        $this->actingAs($me);

        Livewire::test(PasskeysManager::class)
            ->callAction('addPasskey', data: ['name' => 'Outro']);

        $options = json_decode((string) session('passkey-registration-options'), true);

        $this->assertCount(1, $options['excludeCredentials']);
    }

    public function testAddingRequiresAName(): void
    {
        $this->actingAs($this->user('eu@exemplo.com'));

        Livewire::test(PasskeysManager::class)
            ->callAction('addPasskey', data: ['name' => ''])
            ->assertHasActionErrors(['name' => 'required'])
            ->assertNotDispatched('passkeyPropertiesValidated');
    }

    public function testInvalidCredentialFromTheBrowserIsReportedAndNotSaved(): void
    {
        $me = $this->user('eu@exemplo.com');
        $this->actingAs($me);

        Livewire::test(PasskeysManager::class)
            ->callAction('addPasskey', data: ['name' => 'Notebook'])
            ->call('storePasskey', json_encode(['id' => 'forjada']))
            ->assertNotified('Não foi possível cadastrar a passkey.');

        $this->assertSame(0, $me->passkeys()->count());
    }
}
