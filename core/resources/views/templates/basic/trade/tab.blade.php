@php
    $meta    = (object) $meta;
    $screen  = @$meta->screen;
    $pair    = $meta->pair;
    $markets = $meta->markets;
@endphp

<div class="trading-bottom__tab">
    <div class="@if($screen == 'small')  d-sm-block d-md-none @endif">
        <ul class="nav nav-pills  mb-3 custom--tab "
        id="pills-{{ $screen }}-tab-list" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill"
                data-bs-target="#pills-{{ $screen }}-order-book" type="button" role="tab"
                aria-controls="pills-orderbookthree" aria-selected="true">
                @lang('Order Book')
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-{{ $screen }}-market-list"
                type="button" role="tab" aria-controls="pills-markettwentyfive" aria-selected="false">
                @lang('Market')
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-{{ @$screen }}-tab" data-bs-toggle="pill"
                data-bs-target="#pills-{{ $screen }}-trade-history" type="button" role="tab"
                aria-controls="pills-historytwentyfive" aria-selected="false">
                @lang('History')
            </button>
        </li>
    </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade active"
            id="pills-{{ $screen }}-order-book" role="tabpanel">
            <x-flexible-view :view="$activeTemplate . 'trade.order_book'" :meta="['pair' => $pair, 'screen' => 'small']" />
        </div>
        <div class="tab-pane fade" id="pills-{{ $screen }}-market-list" role="tabpanel">
            <x-flexible-view :view="$activeTemplate . 'trade.pair_list'" :meta="['markets' => $markets, 'screen' => 'small']" />
        </div>
        <div class="tab-pane fade" id="pills-{{ $screen }}-trade-history" role="tabpanel">
            <x-flexible-view :view="$activeTemplate . 'trade.history'" :meta="['pair' => $pair]" />
        </div>
    </div>


</div>
