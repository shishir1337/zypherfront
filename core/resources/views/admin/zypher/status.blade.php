@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Zypher System Status')</h5>
                </div>
                <div class="card-body">
                    @if($status && $status['success'])
                        <div class="row">
                            <div class="col-lg-4 col-sm-6 mb-3">
                                <div class="widget-two box--shadow2 has--link b-radius--5 bg--white">
                                    <div class="widget-two__icon b-radius--5 bg--{{ $status['data']['isRunning'] ? 'success' : 'danger' }}">
                                        <i class="las la-{{ $status['data']['isRunning'] ? 'check-circle' : 'times-circle' }}"></i>
                                    </div>
                                    <div class="widget-two__content">
                                        <h3>{{ $status['data']['isRunning'] ? 'Running' : 'Stopped' }}</h3>
                                        <p>@lang('Service Status')</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6 mb-3">
                                <div class="widget-two box--shadow2 has--link b-radius--5 bg--white">
                                    <div class="widget-two__icon b-radius--5 bg--primary">
                                        <i class="las la-sync"></i>
                                    </div>
                                    <div class="widget-two__content">
                                        <h3>{{ ucfirst($status['data']['mode']) }}</h3>
                                        <p>@lang('Trading Mode')</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6 mb-3">
                                <div class="widget-two box--shadow2 has--link b-radius--5 bg--white">
                                    <div class="widget-two__icon b-radius--5 bg--info">
                                        <i class="las la-dollar-sign"></i>
                                    </div>
                                    <div class="widget-two__content">
                                        <h3>${{ number_format($status['data']['currentPrice'], 2) }}</h3>
                                        <p>@lang('Current Price')</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6 mb-3">
                                <div class="widget-two box--shadow2 has--link b-radius--5 bg--white">
                                    <div class="widget-two__icon b-radius--5 bg--success">
                                        <i class="las la-chart-bar"></i>
                                    </div>
                                    <div class="widget-two__content">
                                        <h3>{{ number_format($status['data']['totalCandles']) }}</h3>
                                        <p>@lang('Total Candles')</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6 mb-3">
                                <div class="widget-two box--shadow2 has--link b-radius--5 bg--white">
                                    <div class="widget-two__icon b-radius--5 bg--warning">
                                        <i class="las la-clock"></i>
                                    </div>
                                    <div class="widget-two__content">
                                        <h3>{{ gmdate('H:i:s', $status['data']['uptime'] / 1000) }}</h3>
                                        <p>@lang('Uptime')</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-6 mb-3">
                                <div class="widget-two box--shadow2 has--link b-radius--5 bg--white">
                                    <div class="widget-two__icon b-radius--5 bg--dark">
                                        <i class="las la-calendar"></i>
                                    </div>
                                    <div class="widget-two__content">
                                        <h3>{{ date('H:i:s', $status['data']['lastCandleTime'] / 1000) }}</h3>
                                        <p>@lang('Last Candle')</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($status['data']['activeManualControl']) && $status['data']['activeManualControl'])
                            <div class="alert alert-info" role="alert">
                                <h5 class="alert-heading">
                                    <i class="las la-info-circle"></i> @lang('Active Manual Control')
                                </h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>@lang('Direction'):</strong> 
                                        <span class="badge badge--{{ $status['data']['activeManualControl']['direction'] == 'up' ? 'success' : ($status['data']['activeManualControl']['direction'] == 'down' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($status['data']['activeManualControl']['direction']) }}
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('Speed'):</strong> {{ $status['data']['activeManualControl']['speed'] * 100 }}%
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('Intensity'):</strong> {{ $status['data']['activeManualControl']['intensity'] }}x
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('Expires'):</strong> {{ date('H:i:s', strtotime($status['data']['activeManualControl']['expires_at'])) }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <h5 class="mb-3">@lang('Service Control')</h5>
                            <form action="{{ route('admin.zypher.start') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success" {{ $status['data']['isRunning'] ? 'disabled' : '' }}>
                                    <i class="las la-play"></i> @lang('Start Service')
                                </button>
                            </form>
                            <form action="{{ route('admin.zypher.stop') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger" {{ !$status['data']['isRunning'] ? 'disabled' : '' }}>
                                    <i class="las la-stop"></i> @lang('Stop Service')
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-danger" role="alert">
                            <i class="las la-exclamation-triangle"></i>
                            @lang('Unable to connect to Zypher API. Please make sure the service is running on') 
                            <strong>{{ env('ZYPHER_API_URL', 'https://zypher.bigbuller.com/api') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.zypher.manual') }}" class="btn btn-outline--primary">
        <i class="las la-sliders-h"></i> @lang('Manual Control')
    </a>
    <a href="{{ route('admin.zypher.mode') }}" class="btn btn-outline--info">
        <i class="las la-sync"></i> @lang('Trading Mode')
    </a>
    <button type="button" class="btn btn-outline--success" onclick="location.reload()">
        <i class="las la-sync-alt"></i> @lang('Refresh')
    </button>
@endpush

@push('style')
<style>
    .widget-two {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .widget-two__icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
    }
    .widget-two__content h3 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .widget-two__content p {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 0;
    }
</style>
@endpush

