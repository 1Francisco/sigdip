<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspeccion;
use App\Models\DetalleInspeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InspeccionController extends Controller
{
    /**
     * Sincroniza los detalles de inspección desde la app móvil.
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inspeccion_id' => 'required|exists:inspecciones,id',
            'detalles' => 'required|array',
            'detalles.*.animal_id' => 'required|exists:animales,id',
            'detalles.*.resultado_prueba' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->detalles as $detalle) {
                DetalleInspeccion::updateOrCreate(
                    [
                        'inspeccion_id' => $request->inspeccion_id,
                        'animal_id' => $detalle['animal_id'],
                    ],
                    [
                        'resultado_prueba' => $detalle['resultado_prueba'],
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sincronización exitosa'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
}
