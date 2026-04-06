<?php

namespace App\Modules\Auth\Repositories;

use App\Support\Repositories\LegacySqlRepository;
use App\Modules\Auth\Support\AuthenticatedAppUser;

class RealIdentityRepository extends LegacySqlRepository
{
    public function findUserForLogin(string $empresaCodigo, string $username): ?AuthenticatedAppUser
    {
        $row = $this->connection()->selectOne($this->userBaseSql(true), [
            $this->normalizeKey($empresaCodigo),
            $this->normalizeKey($username),
        ]);

        return $this->hydrateUser($row);
    }

    public function findUserByTokenContext(int $usuarioId, string $empresaCodigo): ?AuthenticatedAppUser
    {
        $row = $this->connection()->selectOne(
            $this->userBaseSql(false),
            [
                $usuarioId,
                $this->normalizeKey($empresaCodigo),
            ]
        );

        return $this->hydrateUser($row);
    }

    public function ListEmpresas(): array
    {
        return collect($this->connection()->select(
            'select trim(e."COD_EMP") as codigo, e."DES_EMP" as nombre, e."IMAGEN" as imagen '
            .'from '.$this->schemaTable('maestro', 'EMPRESA').' e '
            .'where '.$this->SoloActivos('e').' '
            .'order by e."DES_EMP", trim(e."COD_EMP")'
        ))->map(function ($empresa) {
            return [
                'codigo' => $this->normalizeValue($empresa->codigo),
                'id' => $this->normalizeValue($empresa->codigo),
                'nombre' => $this->normalizeValue($empresa->nombre),
                'imagen' => $this->normalizeNullable($empresa->imagen),
            ];
        })->values()->all();
    }

    protected function hydrateUser($row): ?AuthenticatedAppUser
    {
        if (! $row) {
            return null;
        }

        $codUsuario = $this->normalizeValue($row->cod_usuario);
        $empresaCodigo = $this->normalizeValue($row->empresa_codigo);

        return new AuthenticatedAppUser([
            'id' => (int) $row->usuario_id,
            'username' => $codUsuario,
            'codUsuario' => $codUsuario,
            'fullName' => $this->buildFullName($row),
            'password' => $row->password,
            'empresaCodigo' => $empresaCodigo,
            'empresaNombre' => $this->normalizeValue($row->empresa_nombre),
            'empresaImagen' => $this->normalizeNullable($row->empresa_imagen),
            'permisos' => $this->permissionIdsFor($codUsuario, $empresaCodigo),
        ]);
    }

    protected function userBaseSql(bool $byCredentials): string
    {
        $where = $byCredentials
            ? 'trim(ue."COD_EMP") = ? and trim(u."COD_USUARIO") = ?'
            : 'u."ID" = ? and trim(ue."COD_EMP") = ?';

        return 'select '
            .'u."ID" as usuario_id, '
            .'trim(u."COD_USUARIO") as cod_usuario, '
            .'u."NOM_USUARIO" as nom_usuario, '
            .'u."NOMBRES" as nombres, '
            .'u."DES_APELLP" as apellido_paterno, '
            .'u."DES_APELLM" as apellido_materno, '
            .'u."USU_CLAVE" as password, '
            .'trim(e."COD_EMP") as empresa_codigo, '
            .'e."DES_EMP" as empresa_nombre, '
            .'e."IMAGEN" as empresa_imagen '
            .'from '.$this->schemaTable('seguridad', 'USUARIO').' u '
            .'inner join '.$this->schemaTable('seguridad', 'USUARIO_EMPRESA').' ue '
                .'on trim(ue."COD_USUARIO") = trim(u."COD_USUARIO") '
            .'inner join '.$this->schemaTable('maestro', 'EMPRESA').' e '
                .'on trim(e."COD_EMP") = trim(ue."COD_EMP") '
            .'where '.$this->SoloActivos('u').' '
                .'and '.$this->SoloActivos('ue').' '
                .'and '.$this->SoloActivos('e').' '
                .'and '.$where.' '
            .'limit 1';
    }

    protected function permissionIdsFor(string $codUsuario, string $empresaCodigo): array
    {
        $systemCodes = collect($this->connection()->select(
            'select trim(us."COD_SISTEMA") as codigo '
            .'from '.$this->schemaTable('seguridad', 'USUARIO_SISTEMA').' us '
            .'inner join '.$this->schemaTable('seguridad', 'SISTEMA').' s '
                .'on trim(s."COD_SISTEMA") = trim(us."COD_SISTEMA") '
            .'where '.$this->SoloActivos('us').' '
                .'and '.$this->SoloActivos('s').' '
                .'and trim(us."COD_EMP") = ? '
                .'and trim(us."COD_USUARIO") = ? '
            .'order by trim(us."COD_SISTEMA")',
            [$empresaCodigo, $codUsuario]
        ))->pluck('codigo')->map(function ($codigo) {
            return $this->normalizeValue($codigo);
        })->unique()->values()->all();

        $permissions = [];

        foreach (config('mobile.permission_systems', []) as $permission => $systemCode) {
            if (in_array((string) $systemCode, $systemCodes, true)) {
                $permissions[] = $permission;
            }
        }

        return array_values($permissions);
    }

    protected function buildFullName($row): string
    {
        $parts = array_filter([
            $this->normalizeNullable($row->nombres),
            $this->normalizeNullable($row->apellido_paterno),
            $this->normalizeNullable($row->apellido_materno),
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        if (! empty($parts)) {
            return implode(' ', $parts);
        }

        $nombreUsuario = $this->normalizeNullable($row->nom_usuario);

        if ($nombreUsuario) {
            return $nombreUsuario;
        }

        return '';
    }

    protected function SoloActivos(string $alias): string
    {
        return "trim({$alias}.\"IND_ESTADO\") = 'A'";
    }
}
