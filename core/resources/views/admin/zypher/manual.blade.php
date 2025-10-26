@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Zypher Manual Control')</h5>
                </div>
                <div class="card-body">
                    @if($status && $status['success'])
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="alert alert-info" role="alert">
                                    <h5 class="alert-heading"><i class="las la-info-circle"></i> @lang('Current Status')</h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>@lang('Mode'):</strong> 
                                            <span class="badge badge--{{ $status['data']['mode'] == 'manual' ? 'success' : 'warning' }}">
                                                {{ ucfirst($status['data']['mode']) }}
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>@lang('Current Price'):</strong> ${{ number_format($status['data']['currentPrice'], 2) }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>@lang('Service'):</strong> 
                                            <span class="badge badge--{{ $status['data']['isRunning'] ? 'success' : 'danger' }}">
                                                {{ $status['data']['isRunning'] ? 'Running' : 'Stopped' }}
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>@lang('Active Control'):</strong> 
                                            <span class="badge badge--{{ isset($status['data']['activeManualControl']) ? 'success' : 'secondary' }}">
                                                {{ isset($status['data']['activeManualControl']) ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($status['data']['activeManualControl']) && $status['data']['activeManualControl'])
                            <div class="alert alert-warning" role="alert">
                                <h5 class="alert-heading">
                                    <i class="las la-exclamation-triangle"></i> @lang('Active Manual Control')
                                </h5>
                                <p>@lang('There is currently an active manual control. It will expire at') <strong>{{ date('Y-m-d H:i:s', strtotime($status['data']['activeManualControl']['expires_at'])) }}</strong></p>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>@lang('Direction'):</strong> 
                                        <span class="badge badge--{{ $status['data']['activeManualControl']['direction'] == 'up' ? 'success' : ($status['data']['activeManualControl']['direction'] == 'down' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($status['data']['activeManualControl']['direction']) }}
                                        </span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>@lang('Speed'):</strong> {{ $status['data']['activeManualControl']['speed'] * 100 }}%
                                    </div>
                                    <div class="col-md-4">
                                        <strong>@lang('Intensity'):</strong> {{ $status['data']['activeManualControl']['intensity'] }}x
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('admin.zypher.manual.apply') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Direction') <i class="las la-info-circle" title="Price movement direction"></i></label>
                                        <select name="direction" class="form-control" required>
                                            <option value="">@lang('Select Direction')</option>
                                            <option value="up">@lang('⬆️ Up (Bullish)')</option>
                                            <option value="down">@lang('⬇️ Down (Bearish)')</option>
                                            <option value="neutral">@lang('➡️ Neutral (Sideways)')</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Speed (%)') <i class="las la-info-circle" title="Price change speed (0.1% to 10%)"></i></label>
                                        <input type="number" name="speed" class="form-control" step="0.001" min="0.001" max="0.1" value="0.02" required>
                                        <small class="text-muted">@lang('Min: 0.1%, Max: 10% (0.001 to 0.1)')</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Intensity (Multiplier)') <i class="las la-info-circle" title="Volatility multiplier (0.1x to 10x)"></i></label>
                                        <input type="number" name="intensity" class="form-control" step="0.1" min="0.1" max="10" value="1.5" required>
                                        <small class="text-muted">@lang('Min: 0.1x, Max: 10x')</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>@lang('Duration (Seconds)') <i class="las la-info-circle" title="How long the control should last"></i></label>
                                        <input type="number" name="duration_seconds" class="form-control" min="30" max="3600" value="300" required>
                                        <small class="text-muted">@lang('Min: 30 sec (0.5 min), Max: 3600 sec (60 min)')</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3" role="alert">
                                <strong><i class="las la-exclamation-triangle"></i> @lang('Warning:')</strong>
                                @lang('Manual control will override the automatic trading mode temporarily. Make sure you understand the impact before applying.')
                            </div>

                            <button type="submit" class="btn btn--primary w-100">
                                <i class="las la-sliders-h"></i> @lang('Apply Manual Control')
                            </button>
                        </form>

                        <div class="mt-4">
                            <h5 class="mb-3">@lang('Quick Presets')</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg--success">
                                        <div class="card-body text-center">
                                            <h6 class="text-white">@lang('Gentle Rise')</h6>
                                            <p class="text-white mb-2">@lang('Slow upward trend')</p>
                                            <button type="button" class="btn btn-sm btn-light preset-btn" 
                                                data-direction="up" 
                                                data-speed="0.01" 
                                                data-intensity="0.5" 
                                                data-duration="600">
                                                @lang('Apply')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg--primary">
                                        <div class="card-body text-center">
                                            <h6 class="text-white">@lang('Strong Bull')</h6>
                                            <p class="text-white mb-2">@lang('Fast upward movement')</p>
                                            <button type="button" class="btn btn-sm btn-light preset-btn" 
                                                data-direction="up" 
                                                data-speed="0.05" 
                                                data-intensity="2.0" 
                                                data-duration="300">
                                                @lang('Apply')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg--danger">
                                        <div class="card-body text-center">
                                            <h6 class="text-white">@lang('Strong Bear')</h6>
                                            <p class="text-white mb-2">@lang('Fast downward movement')</p>
                                            <button type="button" class="btn btn-sm btn-light preset-btn" 
                                                data-direction="down" 
                                                data-speed="0.05" 
                                                data-intensity="2.0" 
                                                data-duration="300">
                                                @lang('Apply')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    <a href="{{ route('admin.zypher.mode') }}" class="btn btn-outline--primary">
        <i class="las la-sync"></i> @lang('Trading Mode')
    </a>
@endpush

@push('script')
<script>
    "use strict";
    (function($) {
        // Preset buttons
        $('.preset-btn').on('click', function() {
            const direction = $(this).data('direction');
            const speed = $(this).data('speed');
            const intensity = $(this).data('intensity');
            const duration = $(this).data('duration');
            
            $('select[name="direction"]').val(direction);
            $('input[name="speed"]').val(speed);
            $('input[name="intensity"]').val(intensity);
            $('input[name="duration_seconds"]').val(duration);
            
            // Scroll to form
            $('html, body').animate({
                scrollTop: $('form').offset().top - 100
            }, 500);
        });
    })(jQuery);
</script>
@endpush

