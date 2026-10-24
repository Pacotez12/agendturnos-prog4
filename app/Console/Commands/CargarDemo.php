<?php

namespace App\Console\Commands;

use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class CargarDemo extends Command
{
    protected $signature = 'demo:cargar {--fresh : Ejecuta migrate:fresh --seed antes de cargar los datos} {--force : Fuerza la ejecución incluso en entorno de producción}';

    protected $description = 'Carga datos de demostración con fechas relativas a hoy para pruebas y presentaciones';

    /**
     * @var array<int, array{nombre: string, apellido: string, documento: string, telefono: string, email: ?string, familiar?: array{nombre: string, apellido: string, documento: string, fecha_nacimiento: string}}>
     */
    private array $pacientesFicticios = [
        [
            'nombre' => 'Ramón',
            'apellido' => 'Benítez',
            'documento' => '3456789',
            'telefono' => '0981 333 444',
            'email' => 'ramon@demo.test',
            'familiar' => [
                'nombre' => 'Lucas',
                'apellido' => 'Benítez',
                'documento' => '6543210',
                'fecha_nacimiento' => '2016-05-10',
            ],
        ],
        [
            'nombre' => 'Beatriz',
            'apellido' => 'Ferreira',
            'documento' => '3987654',
            'telefono' => '0982 555 666',
            'email' => 'beatriz@demo.test',
            'familiar' => [
                'nombre' => 'Mateo',
                'apellido' => 'Ferreira',
                'documento' => '6789012',
                'fecha_nacimiento' => '2018-09-22',
            ],
        ],
        [
            'nombre' => 'Carmen',
            'apellido' => 'Villalba',
            'documento' => '4567123',
            'telefono' => '0983 777 888',
            'email' => 'carmen@demo.test',
        ],
        [
            'nombre' => 'Diego',
            'apellido' => 'Galeano',
            'documento' => '4876543',
            'telefono' => '0984 111 222',
            'email' => 'diego@demo.test',
        ],
        [
            'nombre' => 'Fátima',
            'apellido' => 'Ayala',
            'documento' => '5123987',
            'telefono' => '0985 333 444',
            'email' => 'fatima@demo.test',
        ],
        [
            'nombre' => 'Gustavo',
            'apellido' => 'Insfrán',
            'documento' => '5345678',
            'telefono' => '0986 555 666',
            'email' => 'gustavo@demo.test',
        ],
        [
            'nombre' => 'Oscar',
            'apellido' => 'Vera',
            'documento' => '5678123',
            'telefono' => '0971 777 888',
            'email' => 'oscar@demo.test',
        ],
        [
            'nombre' => 'Jorge',
            'apellido' => 'Romero',
            'documento' => '4234567',
            'telefono' => '0972 999 000',
            'email' => null,
        ],
        [
            'nombre' => 'Laura',
            'apellido' => 'Duarte',
            'documento' => '4456789',
            'telefono' => '0973 111 222',
            'email' => null,
        ],
        [
            'nombre' => 'Marcos',
            'apellido' => 'Bogado',
            'documento' => '4678901',
            'telefono' => '0974 333 444',
            'email' => null,
        ],
        [
            'nombre' => 'Natalia',
            'apellido' => 'Rojas',
            'documento' => '4890123',
            'telefono' => '0975 555 666',
            'email' => null,
        ],
        [
            'nombre' => 'Patricia',
            'apellido' => 'Samaniego',
            'documento' => '5012345',
            'telefono' => '0976 777 888',
            'email' => null,
        ],
        [
            'nombre' => 'Rodrigo',
            'apellido' => 'Caballero',
            'documento' => '5234561',
            'telefono' => '0991 111 222',
            'email' => null,
        ],
        [
            'nombre' => 'Silvana',
            'apellido' => 'Cañete',
            'documento' => '5456782',
            'telefono' => '0992 333 444',
            'email' => null,
        ],
        [
            'nombre' => 'Victorino',
            'apellido' => 'Silvero',
            'documento' => '5678903',
            'telefono' => '0993 555 666',
            'email' => null,
        ],
    ];

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Este comando no puede ejecutarse en el entorno de producción sin la opción --force.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->info('Reiniciando base de datos y ejecutando seeders base...');
            $this->call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);
        }

        $profesionales = Profesional::ordenados()->get();
        if ($profesionales->count() < 2 || ! Consultorio::exists()) {
            $this->info('Ejecutando seeders base necesarios...');
            $this->call('db:seed', ['--force' => true]);
            $profesionales = Profesional::ordenados()->get();
        }

        $funcionarioUser = User::where('rol', RolUsuario::Funcionario)->firstOrFail();
        $prof1 = $profesionales->firstWhere('nombre', 'Ana') ?? $profesionales->first();
        $prof2 = $profesionales->firstWhere('nombre', 'Gloria') ?? $profesionales->last();
        $consultorio = Consultorio::firstOrFail();

        $servicioGeneral = Servicio::where('nombre', 'Consulta general')->firstOrFail();
        $servicioInfantil = Servicio::where('nombre', 'Control infantil')->first() ?? $servicioGeneral;
        $servicioOrtodoncia = Servicio::where('nombre', 'Consulta de ortodoncia')->first() ?? $servicioGeneral;

        // Pacientes existentes
        $pacienteCarlos = Paciente::where('documento', '4123456')->firstOrFail(); // tutor
        $pacienteMaria = Paciente::where('documento', '5234567')->firstOrFail(); // paciente
        $pacienteSofia = Paciente::where('documento', '6345678')->firstOrFail(); // familiar de tutor

        // Crear pacientes ficticios
        $password = Hash::make('password');
        $pacientesCreados = [];

        foreach ($this->pacientesFicticios as $data) {
            $user = null;
            if ($data['email']) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'password' => $password,
                        'rol' => RolUsuario::Paciente,
                    ]
                );
            }

            $paciente = Paciente::firstOrCreate(
                ['documento' => $data['documento']],
                [
                    'user_id' => $user?->id,
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'tipo_documento' => 'CI',
                    'telefono' => $data['telefono'],
                    'fecha_nacimiento' => Carbon::now()->subYears(rand(22, 50))->toDateString(),
                ]
            );

            if (isset($data['familiar'])) {
                $familiar = Paciente::firstOrCreate(
                    ['documento' => $data['familiar']['documento']],
                    [
                        'user_id' => null,
                        'responsable_id' => $user?->id,
                        'nombre' => $data['familiar']['nombre'],
                        'apellido' => $data['familiar']['apellido'],
                        'tipo_documento' => 'CI',
                        'telefono' => null,
                        'fecha_nacimiento' => $data['familiar']['fecha_nacimiento'],
                    ]
                );
                $pacientesCreados[] = $familiar;
            }

            $pacientesCreados[] = $paciente;
        }

        $todosPacientes = collect([$pacienteMaria, $pacienteCarlos, $pacienteSofia, ...$pacientesCreados]);
        $pacienteIndex = 0;
        $siguientePaciente = function () use (&$pacienteIndex, $todosPacientes): Paciente {
            $p = $todosPacientes[$pacienteIndex % $todosPacientes->count()];
            $pacienteIndex++;

            return $p;
        };

        $tz = config('app.timezone', 'America/Asuncion');
        $ahora = now($tz);

        // Determinación del día principal (hoy si tiene disponibilidad; de lo contrario, próximo día con disponibilidad)
        $diaPrincipalTieneDisponibilidad = Disponibilidad::where('dia_semana', $ahora->dayOfWeekIso)->exists();
        $diaPrincipal = $diaPrincipalTieneDisponibilidad
            ? $ahora->copy()
            : $this->siguienteDiaConDisponibilidad($ahora);

        if (! $diaPrincipalTieneDisponibilidad) {
            $this->warn("Aviso: Hoy no hay disponibilidad ({$ahora->translatedFormat('l')}). Se utiliza el próximo día disponible ({$diaPrincipal->translatedFormat('l j \d\e F')}) como día principal.");
        }

        $bloquesUsados = [];

        // ---------------------------------------------------------------------
        // 1. DÍA PRINCIPAL:
        //    - Bloques de disponibilidad real para el día principal
        //    - Partir en pasados (fin <= ahora) y futuros (inicio > ahora + 15 min)
        //    - Pasados: más recientes primero, alternando profesionales (hasta 2 Atendido + 1 Ausente)
        //    - Futuros: más cercanos primero, alternando profesionales (5 Confirmado + 1 Solicitado)
        //    - Si no entran todos hoy, el resto pasa al siguiente día hábil
        // ---------------------------------------------------------------------
        $bloquesDiaPrincipal = $this->bloquesDisponiblesParaDia($profesionales, $diaPrincipal);
        $ahoraMas15 = $ahora->copy()->addMinutes(15);

        // Bloques pasados: solo aplican si diaPrincipal es hoy
        $bloquesPasadosHoy = [];
        if ($diaPrincipal->isSameDay($ahora)) {
            $bloquesPasadosHoy = array_filter($bloquesDiaPrincipal, fn ($b) => $b['fin']->lte($ahora));
        }

        $pasadosIntercalados = $this->ordenarEIntercalarBloques($bloquesPasadosHoy, 'desc');

        $estadosPasadosDeseados = [
            EstadoTurno::Atendido,
            EstadoTurno::Atendido,
            EstadoTurno::Ausente,
        ];

        foreach ($pasadosIntercalados as $idx => $bloque) {
            if ($idx >= count($estadosPasadosDeseados)) {
                break;
            }
            $estado = $estadosPasadosDeseados[$idx];
            $prof = $bloque['profesional'];
            $servicio = ($prof->id === $prof1->id) ? $servicioGeneral : $servicioOrtodoncia;

            $this->crearTurno($siguientePaciente(), $prof, $servicio, $consultorio, $bloque['inicio'], $bloque['fin'], $estado, $funcionarioUser);
            $clave = "{$prof->id}_{$bloque['inicio']->toIso8601String()}";
            $bloquesUsados[$clave] = true;
        }

        // Bloques futuros de hoy (o del día principal si es futuro)
        $bloquesFuturosHoy = array_filter(
            $bloquesDiaPrincipal,
            fn ($b) => $b['inicio']->gt($ahoraMas15) && ! isset($bloquesUsados["{$b['profesional']->id}_{$b['inicio']->toIso8601String()}"])
        );

        $futurosIntercalados = $this->ordenarEIntercalarBloques($bloquesFuturosHoy, 'asc');

        $estadosFuturosDeseados = [
            EstadoTurno::Confirmado,
            EstadoTurno::Confirmado,
            EstadoTurno::Confirmado,
            EstadoTurno::Confirmado,
            EstadoTurno::Confirmado,
            EstadoTurno::Solicitado,
        ];

        $futurosColocados = 0;
        foreach ($futurosIntercalados as $bloque) {
            if ($futurosColocados >= count($estadosFuturosDeseados)) {
                break;
            }
            $estado = $estadosFuturosDeseados[$futurosColocados];
            $prof = $bloque['profesional'];
            $servicio = ($prof->id === $prof1->id) ? $servicioGeneral : $servicioOrtodoncia;

            $this->crearTurno($siguientePaciente(), $prof, $servicio, $consultorio, $bloque['inicio'], $bloque['fin'], $estado, $funcionarioUser);
            $clave = "{$prof->id}_{$bloque['inicio']->toIso8601String()}";
            $bloquesUsados[$clave] = true;
            $futurosColocados++;
        }

        // Si no entraron todos hoy, el resto pasa al siguiente día disponible
        if ($futurosColocados < count($estadosFuturosDeseados)) {
            $diaSiguiente = $this->siguienteDiaConDisponibilidad($diaPrincipal);
            $bloquesSiguiente = $this->bloquesDisponiblesParaDia($profesionales, $diaSiguiente);
            $futurosSiguienteIntercalados = $this->ordenarEIntercalarBloques($bloquesSiguiente, 'asc');

            foreach ($futurosSiguienteIntercalados as $bloque) {
                if ($futurosColocados >= count($estadosFuturosDeseados)) {
                    break;
                }
                $clave = "{$bloque['profesional']->id}_{$bloque['inicio']->toIso8601String()}";
                if (isset($bloquesUsados[$clave])) {
                    continue;
                }

                $estado = $estadosFuturosDeseados[$futurosColocados];
                $prof = $bloque['profesional'];
                $servicio = ($prof->id === $prof1->id) ? $servicioGeneral : $servicioOrtodoncia;

                $this->crearTurno($siguientePaciente(), $prof, $servicio, $consultorio, $bloque['inicio'], $bloque['fin'], $estado, $funcionarioUser);
                $bloquesUsados[$clave] = true;
                $futurosColocados++;
            }
        }

        // ---------------------------------------------------------------------
        // 2. PRÓXIMOS DÍAS:
        //    ~15 turnos entre confirmados y solicitados (al menos 3 solicitados).
        //    Todos inicio > ahora y dentro de disponibilidad.
        //    paciente@, tutor@ y Sofía tienen turnos asignados aquí.
        // ---------------------------------------------------------------------
        $turnosProximosDeseados = 15;
        $solicitadosProximosDeseados = 3;
        $proximosColocados = 0;
        $solicitadosColocados = 0;

        // Pacientes obligatorios con turno próximo
        $pacientesObligatorios = [$pacienteMaria, $pacienteCarlos, $pacienteSofia];

        $cursorDiaFuturo = $diaPrincipal->copy()->addDay();
        while ($proximosColocados < $turnosProximosDeseados) {
            if (Disponibilidad::where('dia_semana', $cursorDiaFuturo->dayOfWeekIso)->exists()) {
                $bloquesDia = $this->bloquesDisponiblesParaDia($profesionales, $cursorDiaFuturo);
                $bloquesFuturos = array_filter(
                    $bloquesDia,
                    fn ($b) => $b['inicio']->gt($ahoraMas15) && ! isset($bloquesUsados["{$b['profesional']->id}_{$b['inicio']->toIso8601String()}"])
                );

                $bloquesIntercalados = $this->ordenarEIntercalarBloques($bloquesFuturos, 'asc');

                foreach ($bloquesIntercalados as $bloque) {
                    if ($proximosColocados >= $turnosProximosDeseados) {
                        break;
                    }

                    $clave = "{$bloque['profesional']->id}_{$bloque['inicio']->toIso8601String()}";
                    if (isset($bloquesUsados[$clave])) {
                        continue;
                    }

                    // Determinar paciente
                    if (! empty($pacientesObligatorios)) {
                        $pacienteTurno = array_shift($pacientesObligatorios);
                    } else {
                        $pacienteTurno = $siguientePaciente();
                    }

                    // Determinar estado: asegurar al menos 3 solicitados
                    $restantes = $turnosProximosDeseados - $proximosColocados;
                    $faltanSolicitados = $solicitadosProximosDeseados - $solicitadosColocados;

                    if ($faltanSolicitados > 0 && ($faltanSolicitados >= $restantes || $proximosColocados % 4 === 3)) {
                        $estado = EstadoTurno::Solicitado;
                        $solicitadosColocados++;
                    } else {
                        $estado = EstadoTurno::Confirmado;
                    }

                    $prof = $bloque['profesional'];
                    $servicio = ($pacienteTurno->id === $pacienteSofia->id && $prof->id === $prof1->id)
                        ? $servicioInfantil
                        : (($prof->id === $prof1->id) ? $servicioGeneral : $servicioOrtodoncia);

                    $this->crearTurno($pacienteTurno, $prof, $servicio, $consultorio, $bloque['inicio'], $bloque['fin'], $estado, $funcionarioUser);
                    $bloquesUsados[$clave] = true;
                    $proximosColocados++;
                }
            }

            $cursorDiaFuturo->addDay();
        }

        // ---------------------------------------------------------------------
        // 3. ÚLTIMOS 60 DÍAS (HISTÓRICO):
        //    ~40 atendidos, ~4 ausentes, ~5 cancelados con motivo.
        //    Todos fin <= ahora y dentro de disponibilidad.
        // ---------------------------------------------------------------------
        $motivosCancelacion = [
            'Imposibilidad de asistir por motivos laborales',
            'Enfermedad imprevista del paciente',
            'Viaje familiar no programado',
            'Dificultad de traslado por inclemencia climática',
            'Reagendamiento solicitado por el paciente',
        ];

        $estadosPasados = array_merge(
            array_fill(0, 40, EstadoTurno::Atendido),
            array_fill(0, 4, EstadoTurno::Ausente),
            array_fill(0, 5, EstadoTurno::Cancelado)
        );
        shuffle($estadosPasados);

        $cursorDiaPasado = $ahora->copy()->subDay();
        $motivoIndex = 0;
        $estadoPasadoIndex = 0;

        while ($estadoPasadoIndex < count($estadosPasados) && $cursorDiaPasado->gte($ahora->copy()->subDays(65))) {
            if (Disponibilidad::where('dia_semana', $cursorDiaPasado->dayOfWeekIso)->exists()) {
                $bloquesDia = $this->bloquesDisponiblesParaDia($profesionales, $cursorDiaPasado);
                $bloquesPasados = array_filter(
                    $bloquesDia,
                    fn ($b) => $b['fin']->lte($ahora) && ! isset($bloquesUsados["{$b['profesional']->id}_{$b['inicio']->toIso8601String()}"])
                );

                $bloquesIntercalados = $this->ordenarEIntercalarBloques($bloquesPasados, 'desc');

                // Tomamos 1 o 2 turnos por día pasado para distribuirlos naturalmente
                $tomadosHoy = 0;
                foreach ($bloquesIntercalados as $bloque) {
                    if ($estadoPasadoIndex >= count($estadosPasados) || $tomadosHoy >= 2) {
                        break;
                    }

                    $clave = "{$bloque['profesional']->id}_{$bloque['inicio']->toIso8601String()}";
                    if (isset($bloquesUsados[$clave])) {
                        continue;
                    }

                    $estado = $estadosPasados[$estadoPasadoIndex];
                    $estadoPasadoIndex++;
                    $tomadosHoy++;

                    $prof = $bloque['profesional'];
                    $servicio = ($prof->id === $prof1->id) ? $servicioGeneral : $servicioOrtodoncia;

                    $motivo = null;
                    if ($estado === EstadoTurno::Cancelado) {
                        $motivo = $motivosCancelacion[$motivoIndex % count($motivosCancelacion)];
                        $motivoIndex++;
                    }

                    $this->crearTurno($siguientePaciente(), $prof, $servicio, $consultorio, $bloque['inicio'], $bloque['fin'], $estado, $funcionarioUser, $motivo);
                    $bloquesUsados[$clave] = true;
                }
            }

            $cursorDiaPasado->subDay();
        }

        // ---------------------------------------------------------------------
        // 4. RESUMEN DINÁMICO Y CREDENCIALES
        // ---------------------------------------------------------------------
        $totalTurnos = Turno::count();
        $turnosHoy = Turno::whereDate('inicio', $ahora->toDateString())->get();
        $atendidosHoy = $turnosHoy->where('estado', EstadoTurno::Atendido)->count();
        $ausentesHoy = $turnosHoy->where('estado', EstadoTurno::Ausente)->count();
        $confirmadosHoy = $turnosHoy->where('estado', EstadoTurno::Confirmado)->count();
        $solicitadosHoy = $turnosHoy->where('estado', EstadoTurno::Solicitado)->count();

        $turnosProximos7Dias = Turno::where('inicio', '>', $ahora)
            ->where('inicio', '<=', $ahora->copy()->addDays(7))
            ->count();

        $turnosHistoricos = Turno::where('inicio', '<', $ahora)->get();
        $atendidosHistoricos = $turnosHistoricos->where('estado', EstadoTurno::Atendido)->count();
        $ausentesHistoricos = $turnosHistoricos->where('estado', EstadoTurno::Ausente)->count();
        $canceladosHistoricos = $turnosHistoricos->where('estado', EstadoTurno::Cancelado)->count();

        $this->newLine();
        $this->info('======================================================');
        $this->info('  DATOS DE DEMOSTRACIÓN CARGADOS EXITOSAMENTE');
        $this->info('======================================================');
        $this->line("Turnos creados: <comment>{$totalTurnos}</comment>");
        $this->line("  - Hoy ({$ahora->toDateString()}): <comment>{$turnosHoy->count()}</comment> ({$atendidosHoy} atendidos, {$ausentesHoy} ausentes, {$confirmadosHoy} confirmados, {$solicitadosHoy} solicitados)");
        $this->line("  - Próximos 7 días: <comment>{$turnosProximos7Dias}</comment>");
        $this->line("  - Histórico pasado: <comment>{$turnosHistoricos->count()}</comment> ({$atendidosHistoricos} atendidos, {$ausentesHistoricos} ausentes, {$canceladosHistoricos} cancelados)");
        $this->line('Pacientes disponibles: <comment>'.count($todosPacientes).'</comment>');
        $this->newLine();

        $this->table(
            ['Rol / Caso', 'Correo electrónico', 'Contraseña'],
            [
                ['Funcionario (Panel)', 'funcionario@glowdental.test', 'password'],
                ['Profesional 1', 'profesional1@glowdental.test', 'password'],
                ['Profesional 2', 'profesional2@glowdental.test', 'password'],
                ['Paciente (individual)', 'paciente@glowdental.test', 'password'],
                ['Tutor (con dependiente)', 'tutor@glowdental.test', 'password'],
                ['Paciente demo (familia)', 'ramon@demo.test', 'password'],
                ['Paciente demo', 'carmen@demo.test', 'password'],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Profesional>|array<int, Profesional>  $profesionales
     * @return array<int, array{inicio: Carbon, fin: Carbon, profesional: Profesional, consultorio_id: int}>
     */
    private function bloquesDisponiblesParaDia($profesionales, Carbon $fecha, int $duracionMin = 30): array
    {
        $tz = config('app.timezone', 'America/Asuncion');
        $diaSemanaIso = $fecha->dayOfWeekIso;
        $todosBloques = [];

        foreach ($profesionales as $prof) {
            $disponibilidades = $prof->disponibilidades()
                ->where('dia_semana', $diaSemanaIso)
                ->orderBy('hora_inicio')
                ->get();

            foreach ($disponibilidades as $disp) {
                $inicioFranja = Carbon::parse($fecha->toDateString().' '.$disp->hora_inicio, $tz);
                $finFranja = Carbon::parse($fecha->toDateString().' '.$disp->hora_fin, $tz);
                $puntero = $inicioFranja->copy();

                while ($puntero->copy()->addMinutes($duracionMin)->lte($finFranja)) {
                    $todosBloques[] = [
                        'inicio' => $puntero->copy(),
                        'fin' => $puntero->copy()->addMinutes($duracionMin),
                        'profesional' => $prof,
                        'consultorio_id' => $disp->consultorio_id,
                    ];
                    $puntero->addMinutes($duracionMin);
                }
            }
        }

        return $todosBloques;
    }

    /**
     * @param  array<int, array{inicio: Carbon, fin: Carbon, profesional: Profesional, consultorio_id: int}>  $bloques
     * @param  'asc'|'desc'  $orden
     * @return array<int, array{inicio: Carbon, fin: Carbon, profesional: Profesional, consultorio_id: int}>
     */
    private function ordenarEIntercalarBloques(array $bloques, string $orden = 'asc'): array
    {
        $porProf = [];
        foreach ($bloques as $b) {
            $porProf[$b['profesional']->id][] = $b;
        }

        foreach ($porProf as $id => $lista) {
            usort($lista, function ($a, $b) use ($orden) {
                return $orden === 'asc'
                    ? $a['inicio']->getTimestamp() <=> $b['inicio']->getTimestamp()
                    : $b['inicio']->getTimestamp() <=> $a['inicio']->getTimestamp();
            });
            $porProf[$id] = $lista;
        }

        $intercalados = [];
        $maxCount = ! empty($porProf) ? max(array_map('count', $porProf)) : 0;

        for ($i = 0; $i < $maxCount; $i++) {
            foreach ($porProf as $id => $lista) {
                if (isset($lista[$i])) {
                    $intercalados[] = $lista[$i];
                }
            }
        }

        return $intercalados;
    }

    private function siguienteDiaConDisponibilidad(Carbon $fecha): Carbon
    {
        $cursor = $fecha->copy()->addDay();
        while (! Disponibilidad::where('dia_semana', $cursor->dayOfWeekIso)->exists()) {
            $cursor->addDay();
        }

        return $cursor;
    }

    private function crearTurno(
        Paciente $paciente,
        Profesional $profesional,
        Servicio $servicio,
        Consultorio $consultorio,
        Carbon $inicio,
        Carbon $fin,
        EstadoTurno $estado,
        User $funcionario,
        ?string $motivoCancelacion = null
    ): Turno {
        $turno = Turno::create([
            'paciente_id' => $paciente->id,
            'profesional_id' => $profesional->id,
            'servicio_id' => $servicio->id,
            'consultorio_id' => $consultorio->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => $estado,
            'motivo_cancelacion' => $motivoCancelacion,
        ]);

        $pacienteUserId = $paciente->user_id ?? $paciente->responsable_id ?? $funcionario->id;

        // Historial coherente
        $turno->historial()->create([
            'estado_anterior' => null,
            'estado_nuevo' => EstadoTurno::Solicitado,
            'usuario_id' => $pacienteUserId,
            'detalle' => 'Turno solicitado',
            'created_at' => $inicio->copy()->subDays(3),
        ]);

        if ($estado === EstadoTurno::Confirmado) {
            $turno->historial()->create([
                'estado_anterior' => EstadoTurno::Solicitado,
                'estado_nuevo' => EstadoTurno::Confirmado,
                'usuario_id' => $funcionario->id,
                'detalle' => 'Turno confirmado por recepción',
                'created_at' => $inicio->copy()->subDays(2),
            ]);
        } elseif ($estado === EstadoTurno::Atendido) {
            $turno->historial()->create([
                'estado_anterior' => EstadoTurno::Solicitado,
                'estado_nuevo' => EstadoTurno::Confirmado,
                'usuario_id' => $funcionario->id,
                'detalle' => 'Turno confirmado por recepción',
                'created_at' => $inicio->copy()->subDays(2),
            ]);
            $turno->historial()->create([
                'estado_anterior' => EstadoTurno::Confirmado,
                'estado_nuevo' => EstadoTurno::Atendido,
                'usuario_id' => $profesional->user_id ?? $funcionario->id,
                'detalle' => 'Atención completada',
                'created_at' => $fin,
            ]);
        } elseif ($estado === EstadoTurno::Ausente) {
            $turno->historial()->create([
                'estado_anterior' => EstadoTurno::Solicitado,
                'estado_nuevo' => EstadoTurno::Confirmado,
                'usuario_id' => $funcionario->id,
                'detalle' => 'Turno confirmado por recepción',
                'created_at' => $inicio->copy()->subDays(2),
            ]);
            $turno->historial()->create([
                'estado_anterior' => EstadoTurno::Confirmado,
                'estado_nuevo' => EstadoTurno::Ausente,
                'usuario_id' => $funcionario->id,
                'detalle' => 'El paciente no se presentó',
                'created_at' => $fin,
            ]);
        } elseif ($estado === EstadoTurno::Cancelado) {
            $turno->historial()->create([
                'estado_anterior' => EstadoTurno::Solicitado,
                'estado_nuevo' => EstadoTurno::Cancelado,
                'usuario_id' => $pacienteUserId,
                'detalle' => $motivoCancelacion ?? 'Cancelado por el paciente',
                'created_at' => $inicio->copy()->subDay(),
            ]);
        }

        return $turno;
    }
}
