<?php

namespace App\Console\Commands;

use App\Models\AlertaDocumento;
use App\Models\VehiculoDocumento;
use Illuminate\Console\Command;

class VerificarAlertasDocumentos extends Command
{
    protected $signature = 'documentos:verificar-alertas';

    protected $description = 'Verifica documentos vencidos o por vencer y genera alertas automáticas.';

    public function handle(): int
    {
        $documentos = VehiculoDocumento::query()
            ->with(['tipoDocumento', 'vehiculo.responsableActivo'])
            ->get();

        $creadas = 0;
        $cerradas = 0;

        foreach ($documentos as $documento) {
            $estado = $documento->estadoAlertaDocumento();

            $alertasAbiertas = AlertaDocumento::query()
                ->where('vehiculo_documento_id', $documento->id)
                ->where('estatus', 'Abierta')
                ->get();

            if (! $estado) {
                foreach ($alertasAbiertas as $alerta) {
                    $alerta->update([
                        'estatus' => 'Cerrada',
                        'fecha_cierre' => now(),
                        'comentario' => trim((string) ($alerta->comentario ? $alerta->comentario . ' ' : '') . 'Cierre automático por regularización de vigencia.'),
                    ]);
                    $cerradas++;
                }

                continue;
            }

            $alertaMismoTipo = $alertasAbiertas->firstWhere('tipo', $estado);

            foreach ($alertasAbiertas->where('tipo', '!=', $estado) as $alerta) {
                $alerta->update([
                    'estatus' => 'Cerrada',
                    'fecha_cierre' => now(),
                    'comentario' => trim((string) ($alerta->comentario ? $alerta->comentario . ' ' : '') . 'Cierre automático por cambio de estado de vigencia.'),
                ]);
                $cerradas++;
            }

            if ($alertaMismoTipo) {
                continue;
            }

            $fechaVencimiento = $documento->fecha_vencimiento?->format('d/m/Y') ?? 'sin fecha';
            $descripcion = $estado === 'vencido'
                ? "El documento {$documento->nombre} venció el {$fechaVencimiento}."
                : "El documento {$documento->nombre} vence el {$fechaVencimiento}.";

            AlertaDocumento::create([
                'vehiculo_documento_id' => $documento->id,
                'vehiculo_id' => $documento->vehiculo_id,
                'tipo_documento_id' => $documento->tipo_documento_id,
                'responsable_user_id' => $documento->vehiculo?->responsableActivo?->responsable_user_id,
                'tipo' => $estado,
                'descripcion' => $descripcion,
                'estatus' => 'Abierta',
                'fecha_alerta' => now(),
            ]);

            $creadas++;
        }

        $this->info("Alertas de documentos creadas: {$creadas}");
        $this->info("Alertas de documentos cerradas: {$cerradas}");

        return self::SUCCESS;
    }
}
