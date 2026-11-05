<?php

namespace App\Filament\Widgets;

use App\Support\BackupInventory;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Diagnostic de l'installation : tâche planifiée, sauvegardes, HTTPS, adresse IP vue par le site.
 * Visible des seules personnes qui gèrent les sauvegardes.
 */
class SystemStatus extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'État de l’installation';

    public static function canView(): bool
    {
        return auth()->user()?->can('sauvegardes.gerer') ?? false;
    }

    protected function getStats(): array
    {
        $heartbeat = Cache::get('hollow.scheduler.heartbeat');
        $lastRun = $heartbeat ? Carbon::createFromTimestamp((int) $heartbeat)->timezone(config('app.timezone')) : null;
        $schedulerOk = $lastRun && $lastRun->greaterThan(now()->subMinutes(15));

        try {
            $pendingJobs = config('queue.default') === 'database' ? DB::table(config('queue.connections.database.table', 'jobs'))->count() : 0;
            $failedJobs = DB::table(config('queue.failed.table', 'failed_jobs'))->count();
        } catch (Throwable) {
            $pendingJobs = 0;
            $failedJobs = 0;
        }

        $newestBackup = app(BackupInventory::class)->newestDate();
        $request = request();

        return [
            Stat::make('Tâche planifiée', $schedulerOk ? 'Active' : 'À vérifier')
                ->description($lastRun ? 'Dernier passage '.$lastRun->locale('fr')->diffForHumans() : 'Aucun passage enregistré : voir le guide d’installation')
                ->color($schedulerOk ? 'success' : 'danger'),
            Stat::make('File d’attente', $pendingJobs.' en attente')
                ->description($failedJobs > 0 ? $failedJobs.' tâche(s) en échec : voir le journal du serveur' : 'Aucune tâche en échec')
                ->color($failedJobs > 0 ? 'warning' : 'success'),
            Stat::make('Dernière sauvegarde', $newestBackup ?? 'Aucune')
                ->color($newestBackup ? 'success' : 'warning'),
            Stat::make('Connexion', $request->isSecure() ? 'HTTPS' : 'HTTP non chiffré')
                ->description('Votre adresse IP vue par le site : '.$request->ip())
                ->color($request->isSecure() ? 'success' : 'danger'),
        ];
    }
}
