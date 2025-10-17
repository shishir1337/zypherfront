@extends($activeTemplate . 'layouts.master')
@section('content')
    @php
        $walletBalance = showAmount($wallet->balance, currencyFormat: false);
        $general = gs();
        $transferCharge = getAmount($general->other_user_transfer_charge);
        $transferChargeForOtherWallet = getAmount($general->other_wallet_transfer_charge);
    @endphp
    <div class="row gy-3 justify-content-center mb-3">
        <div class="col-lg-12">
            <div class="d-flex flex-wrap flex-between align-items-center">
                <h4 class="mb-0">{{ __($pageTitle) }}</h4>
                <a href="{{ route('user.wallet.list', $walletType) }}" class="btn btn--base btn--sm outline">
                    <i class="la la-undo"></i> @lang('Back')
                </a>
            </div>
        </div>
    </div>
    <div class="row gy-4 mb-3 justify-content-center">
        <div class="col-xxl-3 col-sm-6">
            
            <div class="dashboard-card ">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--base">
                        <i class="las la-spinner"></i>
                    </span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.order.open') }}?currency={{ $currency->symbol }}" class="dashboard-card__coin-name mb-0 ">
                            @lang('Open Order')
                        </a>
                        <h6 class="dashboard-card__coin-title"> {{ getAmount(@$widget['open_order']) }} </h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card ">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--success">
                        <i class="las la-check-circle"></i>
                    </span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.order.completed') }}?currency={{ $currency->symbol }}" class="dashboard-card__coin-name mb-0">
                            @lang('Completed Order')
                        </a>
                        <h6 class="dashboard-card__coin-title"> {{ getAmount(@$widget['completed_order']) }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card ">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--danger">
                        <i class="las la-times-circle"></i>
                    </span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.order.canceled') }}?currency={{ $currency->symbol }}" class="dashboard-card__coin-name mb-0 ">
                            @lang('Canceled Order')
                        </a>
                        <h6 class="dashboard-card__coin-title"> {{ getAmount(@$widget['canceled_order']) }}</h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--base fs-50 icon-order"></span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.order.history') }}?search={{ @$currency->symbol }}" class="dashboard-card__coin-name mb-0">
                            @lang('Total Order')
                        </a>
                        <h6 class="dashboard-card__coin-title">
                            {{ getAmount($widget['total_order']) }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row gy-3 mb-3 justify-content-center">
        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--base fs-50 icon-deposit"></span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.deposit.history') }}?search={{ @$currency->symbol }}" class="dashboard-card__coin-name mb-0">
                            @lang('Total Deposit')
                        </a>
                        <h6 class="dashboard-card__coin-title">
                            {{ __(@$wallet->currency->sign) }}{{ showAmount($widget['total_deposit'], currencyFormat: false) }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--base fs-50 icon-withdraw">
                    </span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.withdraw.history') }}?search={{ @$currency->symbol }}" class="dashboard-card__coin-name mb-0 ">
                            @lang('Total Withdraw')
                        </a>
                        <h6 class="dashboard-card__coin-title">
                            {{ __(@$wallet->currency->sign) }}{{ showAmount($widget['total_withdraw'], currencyFormat: false) }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--base fs-50 icon-transaction"></span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.transactions') }}?symbol={{ @$currency->symbol }}&wallet_type={{ $walletType }}"
                            class="dashboard-card__coin-name mb-0">
                            @lang('Total Transaction')
                        </a>
                        <h6 class="dashboard-card__coin-title">
                            {{ getAmount($widget['total_transaction']) }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-sm-6">
            <div class="dashboard-card ">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="dashboard-card__icon text--base">
                        <span class="icon-trade fs-50"></span>
                    </span>
                    <div class="dashboard-card__content">
                        <a href="{{ route('user.trade.history') }}" class="dashboard-card__coin-name mb-0">@lang('Total Trade') </a>
                        <h6 class="dashboard-card__coin-title"> {{ getAmount(@$widget['total_trade']) }} </h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row gy-3 mb-3 justify-content-center">
        <div class="col-lg-12 col-xl-4">
            <div class="card border-0 mb-3">
                <div class="card-body">
                    <div class="wallet-currency text-center mb-3">
                        <img src="{{ @$wallet->currency->image_url }}">
                        <div class="">
                            <p class="mb-0 fs-16">{{ __(@$wallet->currency->name) }}</p>
                            <p class="mt-0 fs-12">{{ __(@$wallet->currency->symbol) }}</p>
                        </div>
                    </div>
                    <div class="wallet-ballance p-3 mb-3">
                        <p class="mb-0 fs-16">{{ __(@$wallet->currency->sign) }}{{ showAmount($wallet->balance, currencyFormat: false) }}
                        </p>
                        <p class="mt-0 fs-12">@lang('Available Balance')</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="flex-fill wallet-ballance p-3 mt-3">
                            <p class="mb-0 fs-16">
                                {{ __(@$wallet->currency->sign) }}{{ showAmount($wallet->in_order, currencyFormat: false) }}</p>
                            <p class="mt-0 fs-12">@lang('In Order')</p>
                        </div>
                        <div class="flex-fill wallet-ballance p-3 mt-3 ">
                            <p class="mb-0 fs-16">
                                {{ __(@$wallet->currency->sign) }}{{ showAmount($wallet->total_balance, currencyFormat: false) }}</p>
                            <p class="mt-0 fs-12">@lang('Total Balance')</p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if (checkWalletConfiguration($walletType, 'deposit'))
                            <button type="button" class="btn btn--success outline flex-fill btn--sm depositBtn">
                                <span class="icon-deposit"></span> @lang('Deposit')
                            </button>
                        @endif

                        @if (checkWalletConfiguration($walletType, 'withdraw'))
                            <button type="button" class="btn btn--danger outline flex-fill btn--sm withdrawBtn">
                                <span class="icon-withdraw"></span> @lang('Withdraw')
                            </button>
                        @endif

                        @if (checkWalletConfiguration($walletType, 'transfer_other_user') || checkWalletConfiguration($walletType, 'transfer_other_wallet'))
                            <button type="button" class="btn btn--base outline flex-fill btn--sm transferBtn">
                                <i class="las la-exchange-alt"></i> @lang('Transfer')
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-12 col-xl-8">
            <div class="card custom--card border-0">
                <div class="card-body p-0">
                    <h4 class="card-title">@lang('Transaction History')</h4>
                    <table class="table table--responsive--lg">
                        <thead>
                            <tr>
                                <th>@lang('Transacted')</th>
                                <th>@lang('Trx')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Post Balance')</th>
                                <th>@lang('Detail')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $trx)
                                <tr>
                                    <td>
                                        <div>
                                            {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $trx->trx }}</strong>
                                    </td>
                                    <td class="budget">
                                        <span class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                            {{ $trx->trx_type }} {{ showAmount($trx->amount, currencyFormat: false) }}
                                            {{ __($trx->wallet->currency->symbol) }}
                                        </span>
                                    </td>
                                    <td class="budget"> {{ showAmount($trx->post_balance, currencyFormat: false) }}
                                        {{ __($trx->wallet->currency->symbol) }}
                                    </td>
                                    <td>{{ __($trx->details) }}</td>
                                </tr>
                            @empty
                                @php echo userTableEmptyMessage('transaction') @endphp
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($transactions->hasPages())
                {{ paginateLinks($transactions) }}
            @endif
        </div>
    </div>

    @if (checkWalletConfiguration($walletType, 'deposit'))
        <x-flexible-view :view="$activeTemplate . 'user.components.canvas.deposit'" :meta="['gateways' => $gateways, 'single_currency' => $currency, 'wallet_type' => $walletType]" />
    @endif

    @if (checkWalletConfiguration($walletType, 'withdraw'))
        <x-flexible-view :view="$activeTemplate . 'user.components.canvas.withdraw'" :meta="[
            'withdrawMethods' => $withdrawMethods,
            'single_currency' => $currency,
            'wallet_type' => $walletType,
        ]" />
    @endif

    @if (checkWalletConfiguration($walletType, 'transfer_other_user') || checkWalletConfiguration($walletType, 'transfer_other_wallet'))
        <div class="offcanvas offcanvas-end" tabindex="-1" id="transfer-offcanvas" aria-labelledby="offcanvasLabel">
            <div class="offcanvas-header">
                <h4 class="mb-0 fs-18 offcanvas-title">
                    @lang("Transfer $currency->symbol")
                </h4>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="fa fa-times-circle"></i>
                </button>
            </div>
            <div class="offcanvas-body">
                @if (checkWalletConfiguration($walletType, 'transfer_other_wallet'))
                    @php
                        // Auto-determine target wallet
                        $targetWallet = $walletType == 'spot' ? 'funding' : 'spot';
                        $targetWalletTitle = $walletType == 'spot' ? 'Funding' : 'Spot';
                    @endphp
                    <form action="{{ route('user.wallet.transfer.to.other.wallet') }}" method="post" class="other-wallet-transfer transfer-wrapper">
                        @csrf
                        <input type="hidden" name="currency" value="{{ $currency->id }}">
                        <input type="hidden" name="from_wallet" value="{{ $walletType }}">
                        <input type="hidden" name="to_wallet" value="{{ $targetWallet }}">

                        <div class="alert alert-info border--base mb-3" role="alert" style="background-color: rgba(38, 166, 154, 0.1); border: 1px solid #26a69a; color: #d1d4dc;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="las la-exchange-alt" style="font-size: 20px; color: #26a69a;"></i>
                                <strong>@lang('Transfer Between Wallets')</strong>
                            </div>
                            <div class="fs-14" style="line-height: 1.5;">
                                @lang("Transfer $currency->symbol from your") <strong>{{ __(ucfirst($walletType)) }} @lang('Wallet')</strong> @lang('to your') <strong>{{ __($targetWalletTitle) }} @lang('Wallet')</strong>
                            </div>
                            <div class="mt-2 fs-12" style="opacity: 0.8;">
                                <i class="las la-info-circle"></i> @lang('Free transfer • Instant • No fees')
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form--label">@lang('From Wallet')</label>
                            <div class="input-group">
                                <input type="text" class="form-control form--control" value="{{ __(ucfirst($walletType)) }} @lang('Wallet')" readonly style="background-color: rgba(255, 255, 255, 0.05);">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form--label">@lang('To Wallet')</label>
                            <div class="input-group">
                                <input type="text" class="form-control form--control" value="{{ __($targetWalletTitle) }} @lang('Wallet')" readonly style="background-color: rgba(255, 255, 255, 0.05);">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form--label">@lang('Amount')</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    {{ @$currency->sign }}
                                </span>
                                <input type="number" step="any" class="form-control form--control" name="transfer_amount" placeholder="0.00" required />
                                <span class="input-group-text max cursor-pointer"
                                    data-max="{{ getAmount($wallet->balance) }}">@lang('MAX')</span>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                @lang('Available'): <strong>{{ getAmount($wallet->balance) }} {{ $currency->symbol }}</strong>
                            </small>
                        </div>

                        <button class="btn btn--base w-100" type="submit" style="padding: 12px;">
                            <i class="las la-exchange-alt"></i> @lang('Transfer Now')
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
@endsection

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush
@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {

            $('.depositBtn').on('click', function(e) {
                canvasShow("deposit-canvas");
            });

            $('.withdrawBtn').on('click', function(e) {
                canvasShow("withdraw-offcanvas");
            });

            $('.transferBtn').on('click', function(e) {
                canvasShow("transfer-offcanvas");
            });

            function canvasShow(id) {
                let myOffcanvas = document.getElementById(id);
                new bootstrap.Offcanvas(myOffcanvas).show();
            }

            $('.max').on('click', function(e) {
                const max = $(this).data('max');
                $(this).closest('div').find(`input`).val(max);
            });

        
        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .wallet-currency img {
            width: 70px;
            border-radius: 50%;
            object-fit: cover;
        }

        .wallet-ballance {
            background-color: #09171a;
        }

        .offcanvas {
            padding: 30px;
        }

        .custom--tab {
            justify-content: flex-start;
            border-radius: 0;
            border-bottom: 2px solid hsl(var(--white)/0.1);
            border-radius: 4px;
            padding: 10px;
            padding-bottom: 15px;
            margin-bottom: 0px !important;
            margin-bottom: 25px !important;
            background-color: #0d2227;
        }

        .custom--tab .nav-item {
            padding: 0;
            width: 50%;
            display: flex;
            justify-content: center;
            cursor: pointer;
        }

        .custom--tab .nav-item .nav-link {
            background-color: transparent !important;
            border-radius: 0;
            border: 0 !important;
            padding: 0 50px !important;
            position: relative;
            font-size: 1rem;
            font-weight: 600;
        }

        .custom--tab .nav-item .nav-link.active::before {
            position: absolute;
            content: "";
            left: 0;
            bottom: -5px;
            width: 100%;
            height: 2px;
            background-color: hsl(var(--base));
            display: none;
            font-weight: normal;
        }

        .custom--tab .nav-item .nav-link::after {
            position: absolute;
            content: "";
            bottom: -17px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: hsl(var(--base)) !important;
        }

        .custom--tab .nav-item .nav-link.active {
            color: hsl(var(--base)) !important;
            background-color: transparent !important;
        }

        .custom--tab .nav-item .nav-link.active.nav-link::after {
            width: 100%;
        }

        .custom--tab .nav-item .nav-link.active:hover {
            color: hsl(var(--base)) !important;
        }

        @media screen and (max-width:991px) {
            .offcanvas {
                padding: 20px;
            }

            .custom--tab .nav-item .nav-link {
                padding: 0 20px !important;
            }
        }

        @media screen and (max-width:991px) {
            .offcanvas {
                padding: 15px;
            }

            .custom--tab .nav-item .nav-link {
                padding: 0 10px !important;
                font-size: 15px;
            }
        }

        .select2-image {
            max-width: 50px;
        }
    </style>
@endpush
