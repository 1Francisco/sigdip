<?php

namespace App\Http\Controllers;

use App\Models\Predio;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductorController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Productor::with(['medico'])->withCount('predios')
            ->orderByRaw('CASE WHEN clave IS NULL OR clave = "" THEN 1 ELSE 0 END, clave ASC')
            ->latest('id');

        if ($user && ! $user->hasRole('Administrador')) {
            $query->where('medico_id', $user->id);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('curp', 'like', "%{$search}%")
                    ->orWhere('upp', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $productores */
        $productores = $query->paginate(10);
        $productores = $productores->withQueryString();

        return view('productores.index', compact('productores'));
    }

    public function create()
    {
        $medicos = User::role('Medico_Campo')->orderBy('name')->get();

        return view('productores.create', compact('medicos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores',
            'upp' => 'nullable|string|unique:productores',
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'medico_id' => 'nullable|exists:users,id',
            'clave' => ['nullable', 'string', 'regex:/^(AD|AP|BD|BP|BF|BFC|BFE|BU|SG|GP)-?\d*$/i'],
            'zona' => 'nullable|string|in:A,B',
            'tipo_actividad' => 'nullable|string|in:Barrido,Buffer,Seguimiento',
            'sub_tipo_actividad' => 'required_if:tipo_actividad,Seguimiento|nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',

            // Validaciones para el predio (si se envían)
            'registrar_predio' => 'nullable|boolean',
            'nombre_rancho' => 'required_if:registrar_predio,1|nullable|string|max:255',
            'clave_unidad_produccion' => 'required_if:registrar_predio,1|nullable|string|unique:predios,clave_unidad_produccion',
            'predio_municipio' => 'required_if:registrar_predio,1|nullable|string|max:255',
            'predio_localidad' => 'required_if:registrar_predio,1|nullable|string|max:255',
            'latitud' => 'nullable|string|max:50',
            'longitud' => 'nullable|string|max:50',
            'predio_domicilio' => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $medicoId = $validated['medico_id'] ?? null;
                if (! auth()->user()->hasRole('Administrador')) {
                    $medicoId = auth()->id();
                    unset($validated['clave'], $validated['zona']);
                }

                if (($validated['tipo_actividad'] ?? '') !== 'Seguimiento') {
                    $validated['sub_tipo_actividad'] = null;
                }

                // 1. Crear el Productor
                $productor = Productor::create([
                    'nombre' => $validated['nombre'],
                    'apellido_paterno' => $validated['apellido_paterno'],
                    'apellido_materno' => $validated['apellido_materno'] ?? null,
                    'curp' => $validated['curp'] ?? null,
                    'upp' => $validated['upp'] ?? null,
                    'domicilio' => $validated['domicilio'] ?? null,
                    'municipio' => $validated['municipio'] ?? null,
                    'localidad' => $validated['localidad'] ?? null,
                    'estado' => $validated['estado'] ?? 'Sinaloa',
                    'telefono' => $validated['telefono'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'medico_id' => $medicoId,
                    'clave' => $validated['clave'] ?? null,
                    'zona' => $validated['zona'] ?? null,
                    'tipo_actividad' => $validated['tipo_actividad'] ?? null,
                    'sub_tipo_actividad' => $validated['sub_tipo_actividad'] ?? null,
                ]);

                $message = 'Productor registrado con éxito.';

                // 2. Crear el Predio si se solicitó
                if ($request->has('registrar_predio') && $request->registrar_predio == '1') {
                    Predio::create([
                        'nombre_rancho' => $validated['nombre_rancho'],
                        'clave_unidad_produccion' => $validated['clave_unidad_produccion'],
                        'municipio' => $validated['predio_municipio'],
                        'localidad' => $validated['predio_localidad'],
                        'latitud' => $validated['latitud'],
                        'longitud' => $validated['longitud'],
                        'domicilio' => $validated['predio_domicilio'],
                        'productor_id' => $productor->id,
                    ]);
                    $message = 'Productor y su Unidad de Producción (UPP) registrados con éxito.';
                }

                return redirect()->route('productores.index')->with('success', $message);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el registro: '.$e->getMessage());
        }
    }

    public function edit(Productor $productor)
    {
        if (! auth()->user()->hasRole('Administrador') && $productor->medico_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar este productor.');
        }

        $medicos = User::role('Medico_Campo')->orderBy('name')->get();

        return view('productores.edit', ['productor' => $productor, 'medicos' => $medicos]);
    }

    public function update(Request $request, Productor $productor)
    {
        if (! auth()->user()->hasRole('Administrador') && $productor->medico_id !== auth()->id()) {
            abort(403, 'No tienes permiso para editar este productor.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores,curp,'.$productor->id,
            'upp' => 'nullable|string|unique:productores,upp,'.$productor->id,
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'medico_id' => 'nullable|exists:users,id',
            'clave' => ['nullable', 'string', 'regex:/^(AD|AP|BD|BP|BF|BFC|BFE|BU|SG|GP)-?\d*$/i'],
            'zona' => 'nullable|string|in:A,B',
            'tipo_actividad' => 'nullable|string|in:Barrido,Buffer,Seguimiento',
            'sub_tipo_actividad' => 'required_if:tipo_actividad,Seguimiento|nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',
        ]);

        if (($validated['tipo_actividad'] ?? '') !== 'Seguimiento') {
            $validated['sub_tipo_actividad'] = null;
        }

        $medicoId = $validated['medico_id'] ?? $productor->medico_id;
        if (! auth()->user()->hasRole('Administrador')) {
            $medicoId = auth()->id();
            unset($validated['clave'], $validated['zona']);
        }
        $validated['medico_id'] = $medicoId;

        $productor->update($validated);

        return redirect()->route('productores.index')
            ->with('success', 'Datos del productor actualizados.');
    }

    public function show(Productor $productor)
    {
        if (! auth()->user()->hasRole('Administrador') && $productor->medico_id !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este productor.');
        }

        $productor->load(['predios', 'medico']);

        $vinculados = collect();
        if ($productor->clave) {
            $vinculados = Productor::where('clave', $productor->clave)
                ->where('id', '!=', $productor->id)
                ->with(['medico', 'predios'])
                ->get();
        }

        return view('productores.show', [
            'productor' => $productor,
            'vinculados' => $vinculados,
        ]);
    }

    public function destroy(Productor $productor)
    {
        if (! auth()->user()->hasRole('Administrador') && $productor->medico_id !== auth()->id()) {
            abort(403, 'No tienes permiso para eliminar este productor.');
        }

        $productor->predios()->delete();
        $productor->delete();

        return redirect()->route('productores.index')
            ->with('success', 'Productor y sus predios eliminados.');
    }

    public function storeAjax(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'curp' => 'nullable|string|size:18|unique:productores',
            'upp' => 'nullable|string|unique:productores',
            'domicilio' => 'nullable|string',
            'municipio' => 'nullable|string',
            'localidad' => 'nullable|string',
            'estado' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'medico_id' => 'nullable|exists:users,id',
            'clave' => 'nullable|string|max:50',
            'zona' => 'nullable|string|in:A,B',
            'tipo_actividad' => 'nullable|string|in:Barrido,Buffer,Seguimiento',
            'sub_tipo_actividad' => 'required_if:tipo_actividad,Seguimiento|nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',
        ]);

        if (($validated['tipo_actividad'] ?? '') !== 'Seguimiento') {
            $validated['sub_tipo_actividad'] = null;
        }

        $medicoId = $validated['medico_id'] ?? null;
        if (! auth()->user()->hasRole('Administrador')) {
            $medicoId = auth()->id();
            unset($validated['clave'], $validated['zona']);
        }
        $validated['medico_id'] = $medicoId;

        $productor = Productor::create($validated);

        return response()->json($productor);
    }

    public function buscarPorClave(Request $request)
    {
        $clave = $request->get('clave', '');
        $clave = strtoupper(trim($clave));

        if (strlen($clave) < 1) {
            return response()->json([]);
        }

        $productores = Productor::where('clave', 'like', $clave . '%')
            ->orderBy('clave')
            ->limit(50)
            ->get(['id', 'clave', 'nombre', 'apellido_paterno', 'apellido_materno', 'tipo_actividad', 'zona']);

        return response()->json($productores);
    }

    public function buscar(Request $request)
    {
        $q = $request->get('q', '');
        $q = trim($q);

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $productores = Productor::with('predios')->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido_paterno', 'like', "%{$q}%")
                    ->orWhere('apellido_materno', 'like', "%{$q}%")
                    ->orWhere('clave', 'like', "%{$q}%");
            })
            ->orderByRaw("CASE WHEN clave LIKE ? THEN 0 ELSE 1 END", [$q . '%'])
            ->orderBy('nombre')
            ->limit(15)
            ->get();

        $productores->each(function ($p) {
            $primerPredio = $p->predios->first();
            $p->setAttribute('_predio_nombre_rancho', $primerPredio?->nombre_rancho);
            $p->setAttribute('_predio_clave_unidad_produccion', $primerPredio?->clave_unidad_produccion);
            $p->setAttribute('_predio_latitud', $primerPredio?->latitud);
            $p->setAttribute('_predio_longitud', $primerPredio?->longitud);
            $p->setAttribute('_predio_domicilio', $primerPredio?->domicilio);
            $p->setAttribute('_predio_municipio', $primerPredio?->municipio);
            $p->setAttribute('_predio_localidad', $primerPredio?->localidad);
            unset($p->predios);
        });

        return response()->json($productores);
    }

    public function createMultiple(Request $request)
    {
        $medicos = User::role('Medico_Campo')->orderBy('name')->get();
        $prefillProductor = null;
        $prefillPredio = null;

        if ($request->has('prefill_from_productor_id')) {
            $prefillProductor = Productor::with('predios')->find($request->prefill_from_productor_id);
            if ($prefillProductor) {
                $prefillPredio = $prefillProductor->predios->first();
            }
        }

        return view('productores.create-multiple', compact('medicos', 'prefillProductor', 'prefillPredio'));
    }

    public function storeMultiple(Request $request)
    {
        $rules = [
            'productores' => 'required|array|min:1',
            'productores.*.nombre' => 'required|string|max:255',
            'productores.*.apellido_paterno' => 'required|string|max:255',
            'productores.*.apellido_materno' => 'nullable|string|max:255',
            'productores.*.curp' => 'nullable|string|size:18|unique:productores,curp',
            'productores.*.upp' => 'nullable|string|unique:productores,upp',
            'productores.*.clave' => ['nullable', 'string', 'regex:/^(AD|AP|BD|BP|BF|BFC|BFE|BU|SG|GP)-?\d*$/i'],
            'productores.*.domicilio' => 'nullable|string',
            'productores.*.municipio' => 'nullable|string',
            'productores.*.localidad' => 'nullable|string',
            'productores.*.estado' => 'nullable|string',
            'productores.*.telefono' => 'nullable|string|max:20',
            'productores.*.email' => 'nullable|email',
            'productores.*.medico_id' => 'nullable|exists:users,id',
            'productores.*.tipo_actividad' => 'nullable|string|in:Barrido,Buffer,Seguimiento',
            'productores.*.sub_tipo_actividad' => 'required_if:productores.*.tipo_actividad,Seguimiento|nullable|string|in:Cuarentena Precautoria,Cuarentena Definitiva,Hatos Relacionados y Expuestos',
            'productores.*.zona' => 'nullable|string|in:A,B',
            
            // Predio
            'productores.*.registrar_predio' => 'nullable|boolean',
            'productores.*.nombre_rancho' => 'required_if:productores.*.registrar_predio,1|nullable|string|max:255',
            'productores.*.clave_unidad_produccion' => 'required_if:productores.*.registrar_predio,1|nullable|string|unique:predios,clave_unidad_produccion',
            'productores.*.predio_municipio' => 'required_if:productores.*.registrar_predio,1|nullable|string|max:255',
            'productores.*.predio_localidad' => 'required_if:productores.*.registrar_predio,1|nullable|string|max:255',
            'productores.*.latitud' => 'nullable|string|max:50',
            'productores.*.longitud' => 'nullable|string|max:50',
            'productores.*.predio_domicilio' => 'nullable|string|max:255',
        ];

        $messages = [
            'productores.*.nombre.required' => 'El nombre es obligatorio para todos los productores.',
            'productores.*.apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'productores.*.curp.unique' => 'El CURP de uno de los productores ya está registrado.',
            'productores.*.curp.size' => 'El CURP debe tener exactamente 18 caracteres.',
            'productores.*.upp.unique' => 'El UPP de uno de los productores ya está registrado.',
            'productores.*.sub_tipo_actividad.required_if' => 'El subtipo de actividad es requerido cuando el tipo es Seguimiento.',
            'productores.*.clave_unidad_produccion.unique' => 'La clave UPP del predio de uno de los productores ya está registrada.',
        ];

        $validated = $request->validate($rules, $messages);

        try {
            DB::transaction(function () use ($validated) {
                foreach ($validated['productores'] as $pData) {
                    $medicoId = $pData['medico_id'] ?? null;
                    if (! auth()->user()->hasRole('Administrador')) {
                        $medicoId = auth()->id();
                        unset($pData['zona']);
                    }

                    if (($pData['tipo_actividad'] ?? '') !== 'Seguimiento') {
                        $pData['sub_tipo_actividad'] = null;
                    }

                    $productor = Productor::create([
                        'nombre' => $pData['nombre'],
                        'apellido_paterno' => $pData['apellido_paterno'],
                        'apellido_materno' => $pData['apellido_materno'] ?? null,
                        'curp' => $pData['curp'] ?? null,
                        'upp' => $pData['upp'] ?? null,
                        'domicilio' => $pData['domicilio'] ?? null,
                        'municipio' => $pData['municipio'] ?? null,
                        'localidad' => $pData['localidad'] ?? null,
                        'estado' => $pData['estado'] ?? 'Sinaloa',
                        'telefono' => $pData['telefono'] ?? null,
                        'email' => $pData['email'] ?? null,
                        'medico_id' => $medicoId,
                        'clave' => $pData['clave'] ?? null,
                        'zona' => $pData['zona'] ?? null,
                        'tipo_actividad' => $pData['tipo_actividad'] ?? null,
                        'sub_tipo_actividad' => $pData['sub_tipo_actividad'] ?? null,
                    ]);

                    if (isset($pData['registrar_predio']) && $pData['registrar_predio'] == '1') {
                        Predio::create([
                            'nombre_rancho' => $pData['nombre_rancho'],
                            'clave_unidad_produccion' => $pData['clave_unidad_produccion'],
                            'municipio' => $pData['predio_municipio'],
                            'localidad' => $pData['predio_localidad'],
                            'latitud' => $pData['latitud'] ?? null,
                            'longitud' => $pData['longitud'] ?? null,
                            'domicilio' => $pData['predio_domicilio'] ?? null,
                            'productor_id' => $productor->id,
                        ]);
                    }
                }
            });

            return redirect()->route('productores.index')->with('success', 'Productores registrados correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al procesar el registro masivo: ' . $e->getMessage());
        }
    }
}
