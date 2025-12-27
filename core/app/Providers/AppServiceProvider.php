<?php

namespace App\Providers;

use App\Constants\Status;
use App\Lib\Searchable;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\P2P\Trade;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Builder::mixin(new Searchable);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Viserlab.com is shutdown - bypassing install check
        // System is always considered installed since remote validation is no longer needed
        try {
            // Try to set cache, but don't fail if cache is not available
            cache()->put('SystemInstalled', true);
        } catch (\Exception $e) {
            // Cache might not be available yet (e.g., during Docker build), ignore
        }
        
        // OLD CODE - Disabled since viserlab.com is shutdown and install route doesn't exist
        /*
        if (!cache()->get('SystemInstalled')) {
            $envFilePath = base_path('.env');
            if (!file_exists($envFilePath)) {
                header('Location: install');
                exit;
            }
            $envContents = file_get_contents($envFilePath);
            if (empty($envContents)) {
                header('Location: install');
                exit;
            } else {
                cache()->put('SystemInstalled', true);
            }
        }
        */

        $viewShare['emptyMessage'] = 'Data not found';
        view()->share($viewShare);

        view()->composer('admin.partials.sidenav', function ($view) {
            $view->with([
                'bannedUsersCount'           => User::banned()->count(),
                'emailUnverifiedUsersCount'  => User::emailUnverified()->count(),
                'mobileUnverifiedUsersCount' => User::mobileUnverified()->count(),
                'kycUnverifiedUsersCount'    => User::kycUnverified()->count(),
                'kycPendingUsersCount'       => User::kycPending()->count(),
                'pendingTicketCount'         => SupportTicket::whereIN('status', [Status::TICKET_OPEN, Status::TICKET_REPLY])->count(),
                'pendingDepositsCount'       => Deposit::pending()->count(),
                'pendingWithdrawCount'       => Withdrawal::pending()->count(),
                'reportedTrade'              => Trade::reported()->count(),
                'updateAvailable'            => version_compare(gs('available_version'), systemDetails()['version'], '>') ? 'v' . gs('available_version') : false,
            ]);
        });

        view()->composer('admin.partials.topnav', function ($view) {
            $view->with([
                'adminNotifications'     => AdminNotification::where('is_read', Status::NO)->with('user')->orderBy('id', 'desc')->take(10)->get(),
                'adminNotificationCount' => AdminNotification::where('is_read', Status::NO)->count(),
            ]);
        });

        view()->composer('partials.seo', function ($view) {
            $seo = Frontend::where('data_keys', 'seo.data')->first();
            $view->with([
                'seo' => $seo ? $seo->data_values : $seo,
            ]);
        });

        if (gs('force_ssl')) {
            \URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();

    }
}
