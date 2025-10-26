@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Zypher Trading Mode')</h5>
                </div>
                <div class="card-body">
                    @if($currentMode && $currentMode['success'])
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-{{ $currentMode['data']['mode'] == 'auto' ? 'success' : 'warning' }}" role="alert">
                                    <h4 class="alert-heading">
                                        <i class="las la-{{ $currentMode['data']['mode'] == 'auto' ? 'robot' : 'hand-paper' }}"></i>
                                        @lang('Current Mode:') 
                                        <strong>{{ strtoupper($currentMode['data']['mode']) }}</strong>
                                    </h4>
                                    <hr>
                                    <p class="mb-0">
                                        @if($currentMode['data']['mode'] == 'auto')
                                            @lang('The system is currently running in automatic mode. Prices are generated automatically with realistic market behavior.')
                                        @else
                                            @lang('The system is currently in manual mode. You can control price movements through manual controls.')
                                        @endif
                                    </p>
                                    @if(isset($currentMode['data']['lastUpdated']))
                                        <small class="text-muted">@lang('Last updated:') {{ $currentMode['data']['lastUpdated'] }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($status && $status['success'])
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="las la-{{ $status['data']['isRunning'] ? 'check-circle' : 'times-circle' }} text-{{ $status['data']['isRunning'] ? 'success' : 'danger' }}" style="font-size: 48px;"></i>
                                            <h6 class="mt-2">@lang('Service Status')</h6>
                                            <span class="badge badge--{{ $status['data']['isRunning'] ? 'success' : 'danger' }}">
                                                {{ $status['data']['isRunning'] ? 'Running' : 'Stopped' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="las la-dollar-sign text-primary" style="font-size: 48px;"></i>
                                            <h6 class="mt-2">@lang('Current Price')</h6>
                                            <h5 class="mb-0">${{ number_format($status['data']['currentPrice'], 2) }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="las la-chart-bar text-info" style="font-size: 48px;"></i>
                                            <h6 class="mt-2">@lang('Total Candles')</h6>
                                            <h5 class="mb-0">{{ number_format($status['data']['totalCandles']) }}</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="las la-clock text-warning" style="font-size: 48px;"></i>
                                            <h6 class="mt-2">@lang('Uptime')</h6>
                                            <h5 class="mb-0">{{ gmdate('H:i:s', $status['data']['uptime'] / 1000) }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg--success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="las la-robot text-white" style="font-size: 48px; margin-right: 15px;"></i>
                                            <div>
                                                <h4 class="text-white mb-1">@lang('Automatic Mode')</h4>
                                                <p class="text-white mb-0">@lang('Let the system trade automatically')</p>
                                            </div>
                                        </div>
                                        
                                        <ul class="text-white mb-3">
                                            <li>✅ @lang('Realistic market behavior')</li>
                                            <li>✅ @lang('Automatic volatility')</li>
                                            <li>✅ @lang('Natural price movements')</li>
                                            <li>✅ @lang('No manual intervention needed')</li>
                                        </ul>

                                        @if($currentMode['data']['mode'] != 'auto')
                                            <form action="{{ route('admin.zypher.mode.update') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="mode" value="auto">
                                                <button type="submit" class="btn btn-light w-100">
                                                    <i class="las la-play"></i> @lang('Switch to Auto Mode')
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-light w-100" disabled>
                                                <i class="las la-check"></i> @lang('Currently Active')
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg--warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="las la-hand-paper text-white" style="font-size: 48px; margin-right: 15px;"></i>
                                            <div>
                                                <h4 class="text-white mb-1">@lang('Manual Mode')</h4>
                                                <p class="text-white mb-0">@lang('Take control of price movements')</p>
                                            </div>
                                        </div>
                                        
                                        <ul class="text-white mb-3">
                                            <li>⚙️ @lang('Full price control')</li>
                                            <li>⚙️ @lang('Custom speed & intensity')</li>
                                            <li>⚙️ @lang('Direction control (up/down/neutral)')</li>
                                            <li>⚙️ @lang('Temporary or persistent')</li>
                                        </ul>

                                        @if($currentMode['data']['mode'] != 'manual')
                                            <form action="{{ route('admin.zypher.mode.update') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="mode" value="manual">
                                                <button type="submit" class="btn btn-light w-100">
                                                    <i class="las la-play"></i> @lang('Switch to Manual Mode')
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-light w-100" disabled>
                                                <i class="las la-check"></i> @lang('Currently Active')
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4" role="alert">
                            <h5 class="alert-heading"><i class="las la-info-circle"></i> @lang('Information')</h5>
                            <hr>
                            <ul class="mb-0">
                                <li>
                                    <strong>@lang('Auto Mode'):</strong> 
                                    @lang('Best for realistic trading simulation. The system generates natural-looking price movements with automatic volatility.')
                                </li>
                                <li class="mt-2">
                                    <strong>@lang('Manual Mode'):</strong> 
                                    @lang('Use this when you need to control specific price movements. Perfect for testing, demonstrations, or creating specific market scenarios.')
                                </li>
                                <li class="mt-2">
                                    <strong>@lang('Note'):</strong> 
                                    @lang('You can switch between modes at any time. Manual controls will be cleared when switching back to auto mode.')
                                </li>
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-danger" role="alert">
                            <i class="las la-exclamation-triangle"></i>
                            @lang('Unable to connect to Zypher API. Please make sure the service is running.')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.zypher.status') }}" class="btn btn-outline--info">
        <i class="las la-chart-line"></i> @lang('System Status')
    </a>
    <a href="{{ route('admin.zypher.manual') }}" class="btn btn-outline--primary">
        <i class="las la-sliders-h"></i> @lang('Manual Control')
    </a>
@endpush

