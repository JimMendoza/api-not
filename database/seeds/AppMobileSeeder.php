<?php

use App\Models\Empresa;
use App\Models\Notificacion;
use App\Models\Tramite;
use App\Models\TramiteMovimiento;
use App\Models\TramiteSeguimiento;
use App\Models\UsuarioApp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AppMobileSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            $this->seedEmpresas();

            $empresa = Empresa::query()
                ->where('codigo', '0002')
                ->firstOrFail();

            $usuario = UsuarioApp::query()->updateOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'username' => 'asd',
                ],
                [
                    'full_name' => 'Usuario Demo',
                    'password' => Hash::make('123456'),
                    'activo' => true,
                ]
            );

            $tramiteUno = Tramite::query()->updateOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'codigo' => 'T-002',
                ],
                [
                    'usuario_app_id' => $usuario->id,
                    'titulo' => 'Solicitud de acceso a la información',
                    'descripcion' => 'Solicitud ingresada por mesa de partes virtual para acceso a la información pública.',
                    'fecha_registro' => '2026-03-09',
                    'estado_actual' => 'DERIVADO',
                    'activo' => true,
                ]
            );

            $tramiteDos = Tramite::query()->updateOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'codigo' => 'T-003',
                ],
                [
                    'usuario_app_id' => $usuario->id,
                    'titulo' => 'Actualización de datos del proveedor',
                    'descripcion' => 'Actualización de información administrativa del proveedor.',
                    'fecha_registro' => '2026-03-07',
                    'estado_actual' => 'EN EVALUACION',
                    'activo' => true,
                ]
            );

            $tramiteTres = Tramite::query()->updateOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'codigo' => 'T-004',
                ],
                [
                    'usuario_app_id' => $usuario->id,
                    'titulo' => 'Solicitud de constancia de trabajo',
                    'descripcion' => 'Emisión de constancia solicitada desde la aplicación móvil.',
                    'fecha_registro' => '2026-03-05',
                    'estado_actual' => 'RECIBIDO',
                    'activo' => true,
                ]
            );

            $this->seedMovimientos($tramiteUno, [
                [
                    'fecha_hora' => '2026-03-09 08:30:00',
                    'nro_doc' => 'SOL / 002-2026-ADM',
                    'destino' => 'MESA DE PARTES VIRTUAL',
                    'estado' => 'RECIBIDO',
                    'observacion' => 'Recepción inicial del trámite.',
                ],
                [
                    'fecha_hora' => '2026-03-09 16:13:00',
                    'nro_doc' => 'CARTA / 012-2026-JOME',
                    'destino' => 'OFICINA DE TECNOLOGIAS DE LA INFORMACION Y COMUNICACIONES',
                    'estado' => 'DERIVADO',
                    'observacion' => 'Derivado para evaluación interna.',
                ],
            ]);

            $this->seedMovimientos($tramiteDos, [
                [
                    'fecha_hora' => '2026-03-07 09:10:00',
                    'nro_doc' => 'FORM / 044-2026-PROV',
                    'destino' => 'OFICINA DE ABASTECIMIENTO',
                    'estado' => 'RECIBIDO',
                    'observacion' => 'Documentación registrada para revisión.',
                ],
                [
                    'fecha_hora' => '2026-03-08 11:45:00',
                    'nro_doc' => 'MEMO / 008-2026-LOG',
                    'destino' => 'SUB GERENCIA DE LOGISTICA',
                    'estado' => 'EN EVALUACION',
                    'observacion' => 'Validación en curso.',
                ],
            ]);

            $this->seedMovimientos($tramiteTres, [
                [
                    'fecha_hora' => '2026-03-05 10:00:00',
                    'nro_doc' => 'SOL / 119-2026-RRHH',
                    'destino' => 'OFICINA DE RECURSOS HUMANOS',
                    'estado' => 'RECIBIDO',
                    'observacion' => 'Solicitud registrada correctamente.',
                ],
            ]);

            TramiteSeguimiento::query()->updateOrCreate(
                [
                    'tramite_id' => $tramiteUno->id,
                    'usuario_app_id' => $usuario->id,
                ],
                [
                    'activo' => true,
                ]
            );

            TramiteSeguimiento::query()->updateOrCreate(
                [
                    'tramite_id' => $tramiteDos->id,
                    'usuario_app_id' => $usuario->id,
                ],
                [
                    'activo' => false,
                ]
            );

            TramiteSeguimiento::query()->updateOrCreate(
                [
                    'tramite_id' => $tramiteTres->id,
                    'usuario_app_id' => $usuario->id,
                ],
                [
                    'activo' => true,
                ]
            );

            Notificacion::query()->updateOrCreate(
                [
                    'tramite_id' => $tramiteUno->id,
                    'usuario_app_id' => $usuario->id,
                    'titulo' => 'Documentación en revisión',
                    'fecha_hora' => '2026-03-09 14:30:00',
                ],
                [
                    'mensaje' => 'Tu trámite cambió de estado.',
                    'tipo' => 'NUEVO',
                    'leida' => false,
                ]
            );

            Notificacion::query()->updateOrCreate(
                [
                    'tramite_id' => $tramiteUno->id,
                    'usuario_app_id' => $usuario->id,
                    'titulo' => 'Trámite derivado a otra oficina',
                    'fecha_hora' => '2026-03-09 16:20:00',
                ],
                [
                    'mensaje' => 'Tu trámite fue derivado a una nueva oficina.',
                    'tipo' => 'MOVIMIENTO',
                    'leida' => false,
                ]
            );

            Notificacion::query()->updateOrCreate(
                [
                    'tramite_id' => $tramiteTres->id,
                    'usuario_app_id' => $usuario->id,
                    'titulo' => 'Trámite recibido',
                    'fecha_hora' => '2026-03-05 10:05:00',
                ],
                [
                    'mensaje' => 'Tu trámite fue registrado correctamente.',
                    'tipo' => 'NUEVO',
                    'leida' => true,
                ]
            );
        });
    }

    protected function seedEmpresas()
    {
        foreach ($this->empresas() as $empresa) {
            Empresa::query()->updateOrCreate(
                [
                    'codigo' => $empresa['cod_emp'],
                ],
                [
                    'nombre' => $empresa['nombre'],
                    'abrv' => $empresa['abrv'],
                    'claims' => $empresa['claims'],
                    'color' => $empresa['color'],
                    'direccion' => $empresa['direccion'],
                    'imagen' => $empresa['imagen'],
                    'ruc' => $empresa['ruc'],
                    'telefono' => $empresa['telefono'],
                    'url' => $empresa['url'] !== null ? trim($empresa['url']) : null,
                    'activo' => true,
                ]
            );
        }
    }

    protected function seedMovimientos(Tramite $tramite, array $movimientos)
    {
        foreach ($movimientos as $movimiento) {
            TramiteMovimiento::query()->updateOrCreate(
                [
                    'tramite_id' => $tramite->id,
                    'fecha_hora' => $movimiento['fecha_hora'],
                    'estado' => $movimiento['estado'],
                ],
                [
                    'nro_doc' => $movimiento['nro_doc'],
                    'destino' => $movimiento['destino'],
                    'observacion' => $movimiento['observacion'],
                ]
            );
        }
    }

    protected function empresas()
    {
        return [
            [
                'nombre' => 'HOSPITAL DE VENTANILLA',
                'abrv' => null,
                'cod_emp' => '0005',
                'claims' => 'eyJpdiI6Im5TSnluQjNnUFFjS1RBakFvb3N6XC9RPT0iLCJ2YWx1ZSI6IjA2UE9jenhjREV6MTh2WmZnZktiQ3c9PSIsIm1hYyI6IjkzNzU2MjM0MTU2NjJkYzQ1N2U4ZGI2Y2U0Y2Y5YTk0YTQ0MWFjYjY0NjMxYWZkMWQ3OTJlZDZkM2NkZDUzZmQifQ==',
                'color' => null,
                'direccion' => null,
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-H-VENTANILLA.jpg',
                'ruc' => null,
                'telefono' => null,
                'url' => 'hdv                                               ',
            ],
            [
                'nombre' => 'HOSPITAL SAN JOSE CALLAO',
                'abrv' => null,
                'cod_emp' => '0008',
                'claims' => 'eyJpdiI6ImIyVFJucUtOTTVXVWRycnNlZXBnNnc9PSIsInZhbHVlIjoiSG9uQlJzWlVKVEp1VmRGVHdCcTR0UT09IiwibWFjIjoiMjRlNjczZDRkNjc3OTQzYjUzZWZkMTJmOWFhMTBkZjkwODdhNmJlMzcwYjI4NTE2NjFlNTI3YTRhZDE2MjZjMCJ9',
                'color' => null,
                'direccion' => null,
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-SAN-JOSE.png',
                'ruc' => null,
                'telefono' => null,
                'url' => 'hsj                                               ',
            ],
            [
                'nombre' => 'COLEGIO MILITAR LEONCIO PRADO',
                'abrv' => null,
                'cod_emp' => '0007',
                'claims' => 'eyJpdiI6IkpLcXQ4MlEwOXN4VFV2N3ZCdkVpanc9PSIsInZhbHVlIjoibHBKSVVTa0Q1NTc5cEczN1FqNXQwQT09IiwibWFjIjoiNzVlNmFjMGI3ZGVhYzI0MmE4MTY5NTNlZTIyZTM1NmJkMjVhMWQ2Zjg0Zjk5MTgzMWU1ZjM4NTQ3YThhNjM2YiJ9',
                'color' => null,
                'direccion' => null,
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-leoncio-prado.jpg',
                'ruc' => null,
                'telefono' => null,
                'url' => 'cmlp                                              ',
            ],
            [
                'nombre' => 'DIRECCION REGIONAL DE SALUD DEL CALLAO',
                'abrv' => null,
                'cod_emp' => '0006',
                'claims' => 'eyJpdiI6ImE4WFJrdWtUUlpsZnFVelp4akxiNXc9PSIsInZhbHVlIjoiZkZuSVd0UHh1MEJjV2ZET0ZXWUdoUT09IiwibWFjIjoiMTlhZjY0MmJjZjA2NDU1MzQxMjAyYjQyNGU4ZTg2YzgzM2M2ODlhNWVhOWY1Y2RkNDQzYjZiZWM5MzZiZjI2MyJ9',
                'color' => null,
                'direccion' => null,
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-DIRESA.jpg',
                'ruc' => null,
                'telefono' => null,
                'url' => 'diresa                                            ',
            ],
            [
                'nombre' => 'DIRECCION REGIONAL DE EDUCACION DEL CALLAO (DREC)',
                'abrv' => null,
                'cod_emp' => '0004',
                'claims' => 'eyJpdiI6IkdvK2JYeTludDVPUzFNM0VcL2NWdWdnPT0iLCJ2YWx1ZSI6IkFERDR2OURUS0ZnMVFMQUkrR3lubmc9PSIsIm1hYyI6IjRmMjRkYjcxOGE1MmJkZmJmZDZlZTVmMmU4OWE2MThkZTViMjlhMDIyMWQ1MzUyZjBlMGMwOGM4NWEzYTBhNGQifQ==',
                'color' => null,
                'direccion' => null,
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-DREC.jpg',
                'ruc' => null,
                'telefono' => null,
                'url' => 'drec                                              ',
            ],
            [
                'nombre' => 'HOSPITAL DE REHABILITACION DEL CALLAO',
                'abrv' => null,
                'cod_emp' => '0003',
                'claims' => 'eyJpdiI6IldWVUkwWDFzUDk2V1A4VkorMmQ3aXc9PSIsInZhbHVlIjoiRGxxb1ZQcjZEVUFaaFVFdHVaaUpYdz09IiwibWFjIjoiZGM5Y2Q2MTc0OTg0ZjRjMzY1ODYzOTQ4ZjNjZTJjOTQxMTM5NWNkZDYwNmM5MWVkNTQ3MDU0NTA0NzdmOWE3NyJ9',
                'color' => null,
                'direccion' => null,
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-VIGIL.jpg',
                'ruc' => null,
                'telefono' => null,
                'url' => 'hrc                                               ',
            ],
            [
                'nombre' => 'GOBIERNO REGIONAL DEL CALLAO',
                'abrv' => 'GORE',
                'cod_emp' => '0002',
                'claims' => 'eyJpdiI6InFhTjRROEpmNUZUMUhBeDBsXC84enV3PT0iLCJ2YWx1ZSI6IjJKT2RPZDZKcXFsNkhTS2dHZm9hMXc9PSIsIm1hYyI6IjliOTU5YzEzM2Q4ZDI5ODBhNDg1YzIyYjA1MzNjNDg1MmEwZDA4YmZkNTdiYzU1MjI4MjNiNjM4YTcwOTEwMDAifQ==',
                'color' => null,
                'direccion' => 'Av. Elmer Faucett 3970 Callao - Prov. Const. del Callao - Callao - Perú',
                'imagen' => 'https://plataforma.regioncallao.gob.pe/sir/public/img/empresa/logo-GORE.jpg',
                'ruc' => '20514746355 ',
                'telefono' => '((01) 206-0430',
                'url' => 'gore                                              ',
            ],
        ];
    }
}
