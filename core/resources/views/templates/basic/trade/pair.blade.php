@php
    $meta = (object) $meta;
    $pair = @$meta->pair;
@endphp
<div class=" @if (@$meta->screen == 'small') col-sm-12  d-xl-none d-block @else d-xl-block d-none @endif ">
    <div class="trading-header skeleton selected-pair">
        <h4 class="trading-header__title"> {{ str_replace('_', '/', $pair->symbol) }} </h4>
        <div>
            <span class="text--base fs-12">@lang('Price')</span>
            <p class="trading-header-number">
                <span
                    class="market-price-{{ @$pair->marketData->id }} {{ @$pair->marketData->html_classes->price_change }}">
                    {{ showAmount(@$pair->marketData->price,currencyFormat:false) }}
                </span>
            </p>
        </div>
    </div>
</div>

@push('script')
    <script>
        "use strict";
        (function($) {
            setTimeout(() => {
                $('.selected-pair').removeClass('skeleton');
            }, 1500);
        })(jQuery);
    </script>
@endpush
