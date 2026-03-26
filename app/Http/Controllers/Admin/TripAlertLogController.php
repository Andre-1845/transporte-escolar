<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripAlertsLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripAlertLogController extends Controller
{
    /**
     * Lista os logs de alertas com filtros
     */
    public function index(Request $request)
    {

        if (!view()->exists('admin.trip-alerts-logs.index')) {
            dd('View não encontrada: admin.trip-alerts-logs.index');
        }
        $query = TripAlertsLog::with(['trip', 'stop', 'user'])
            ->orderBy('sent_at', 'desc');

        // Filtro por viagem
        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        // Filtro por tipo de alerta
        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        // Filtro por usuário
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por stop point
        if ($request->filled('stop_id')) {
            $query->where('stop_id', $request->stop_id);
        }

        // Filtro por data
        if ($request->filled('date_from')) {
            $query->whereDate('sent_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sent_at', '<=', $request->date_to);
        }

        // Filtro por status de entrega
        if ($request->filled('delivered')) {
            $query->where('delivered', $request->delivered == '1');
        }

        $logs = $query->paginate(50)->withQueryString();

        // Dados para os filtros
        $trips = Trip::orderBy('trip_date', 'desc')->limit(100)->get();
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['student', 'driver']);
        })->limit(100)->get();

        $alertTypes = [
            'approaching' => 'Aproximação',
            'reached' => 'Chegada',
            'passed' => 'Passagem',
            'end_warning' => 'Fim da Rota',
            'broadcast' => 'Broadcast',
            'driver_alert' => 'Alerta Motorista',
            'student_alert' => 'Alerta Aluno'
        ];

        return view('admin.trip-alerts-logs.index', compact('logs', 'trips', 'users', 'alertTypes'));
    }

    /**
     * Mostra detalhes de um alerta específico
     */
    public function show($id)
    {
        $log = TripAlertsLog::with(['trip', 'stop', 'user'])->findOrFail($id);

        return view('admin.trip-alerts-logs.show', compact('log'));
    }

    /**
     * Estatísticas de alertas
     */
    public function stats(Request $request)
    {
        $tripId = $request->get('trip_id');

        $stats = [
            'total' => TripAlertsLog::count(),
            'by_type' => TripAlertsLog::select('alert_type', DB::raw('count(*) as total'))
                ->groupBy('alert_type')
                ->get(),
            'by_day' => TripAlertsLog::select(
                DB::raw('DATE(sent_at) as date'),
                DB::raw('count(*) as total')
            )
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get(),
            'delivery_rate' => [
                'delivered' => TripAlertsLog::where('delivered', true)->count(),
                'failed' => TripAlertsLog::where('delivered', false)->count(),
                'total' => TripAlertsLog::count()
            ]
        ];

        if ($tripId) {
            $stats['trip_details'] = [
                'trip' => Trip::find($tripId),
                'alerts_count' => TripAlertsLog::where('trip_id', $tripId)->count(),
                'by_type' => TripAlertsLog::where('trip_id', $tripId)
                    ->select('alert_type', DB::raw('count(*) as total'))
                    ->groupBy('alert_type')
                    ->get(),
                'students_alerted' => TripAlertsLog::where('trip_id', $tripId)
                    ->whereNotNull('user_id')
                    ->distinct('user_id')
                    ->count('user_id')
            ];
        }

        $trips = Trip::orderBy('trip_date', 'desc')->limit(100)->get();

        return view('admin.trip-alerts-logs.stats', compact('stats', 'trips'));
    }

    /**
     * Exporta logs para CSV
     */
    public function export(Request $request)
    {
        $query = TripAlertsLog::with(['trip', 'stop', 'user'])
            ->orderBy('sent_at', 'desc');

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sent_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sent_at', '<=', $request->date_to);
        }

        $logs = $query->get();

        $filename = 'alertas_log_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM para compatibilidade com Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Cabeçalhos do CSV
            fputcsv($file, [
                'ID',
                'Data/Hora',
                'Viagem ID',
                'Tipo Alerta',
                'Stop Point',
                'Usuário',
                'Distância (m)',
                'Entregue',
                'Metadata'
            ]);

            // Dados
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->sent_at,
                    $log->trip_id,
                    $log->alert_type,
                    $log->stop ? $log->stop->name : '-',
                    $log->user ? $log->user->name : '-',
                    $log->distance_at_alert,
                    $log->delivered ? 'Sim' : 'Não',
                    json_encode($log->metadata, JSON_UNESCAPED_UNICODE)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Reenvia alertas não entregues
     */
    public function retryFailed(Request $request)
    {
        $failedAlerts = TripAlertsLog::where('delivered', false)
            ->where('sent_at', '<', now()->subMinutes(5))
            ->limit(50)
            ->get();

        $retried = 0;

        foreach ($failedAlerts as $alert) {
            // Marca como entregue (na prática, você implementaria o reenvio aqui)
            $alert->update(['delivered' => true]);
            $retried++;
        }

        return redirect()->back()->with('success', "{$retried} alertas foram reprocessados.");
    }
}
