<?php

namespace VagKaefer\CmsFilament\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use VagKaefer\CmsFilament\Filament\Resources\Roles\RoleResource;
use VagKaefer\CmsFilament\Filament\Resources\Users\UserResource;
use VagKaefer\CmsFilament\Tests\Fixtures\Resources\SupporterResource;
use VagKaefer\CmsFilament\Tests\TestCase;

/**
 * O prefixo de permissão é derivado do nome da classe do Resource.
 *
 * É o contrato entre quem cria as permissões (`permissions:generate-resources`)
 * e quem as consulta em runtime (a própria trait): uma divergência produz
 * permissões que a autorização nunca lê — acesso quebrado sem erro visível.
 */
class PermissionPrefixTest extends TestCase
{
    private function prefixOf(string $resourceClass): string
    {
        $method = new ReflectionMethod($resourceClass, 'getPermissionPrefix');

        return $method->invoke(null);
    }

    #[DataProvider('resourceClasses')]
    public function testPrefixIsDerivedFromTheClassName(string $resourceClass, string $expected): void
    {
        $this->assertSame($expected, $this->prefixOf($resourceClass));
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function resourceClasses(): array
    {
        return [
            'resource do projeto' => [SupporterResource::class, 'supporter'],
            'usuários' => [UserResource::class, 'user'],
            'cargos' => [RoleResource::class, 'role'],
        ];
    }

    public function testRequiredPermissionsCoverTheFourActions(): void
    {
        $permissions = SupporterResource::getRequiredPermissions();

        $this->assertSame(
            ['view_supporter', 'create_supporter', 'edit_supporter', 'delete_supporter'],
            array_keys($permissions),
        );

        $this->assertSame('Ver Apoiadores', $permissions['view_supporter']);
    }
}
