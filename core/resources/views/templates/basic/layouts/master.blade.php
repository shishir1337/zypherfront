<!doctype html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> {{ gs()->siteName(__($pageTitle)) }}</title>
    @include('partials.seo')

    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}" />

    @stack('style-lib')
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'dashboard/css/icomoon.css') }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'dashboard/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/custom.css') }}">

    @stack('style')

    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/color.php') }}?color={{ gs('base_color') }}">


    @if (session('app'))
        <style>
            .dashboard-header,
            .dashboardBodyNav {
                display: none !important;
            }
        </style>
    @endif

</head>
@php echo loadExtension('google-analytics') @endphp

<body>
    @if (!request()->routeIs('user.home'))
        <div class="preloader">
            <div class="loader-p"></div>
        </div>
    @endif

    <div class="body-overlay"></div>
    <div class="sidebar-overlay"></div>
    <a class="scroll-top"><i class="fas fa-angle-double-up"></i></a>

    <div class="dashboard-fluid position-relative">
        <div class="dashboard__inner">
            @include($activeTemplate . 'partials.user_sidebar')
            <div class="dashboard__right">
                @include($activeTemplate . 'partials.user_topbar')
                <div class="dashboard-body">
                    <div class="d-flex justify-content-between mb-3 align-items-center dashboardBodyNav">
                        <div class="dashboard-body__bar d-xl-none d-inline-block">
                            <button class="dashboard-sidebar-filter__button">
                                <i class="las la-bars"></i>
                            </button>
                        </div>
                        @if (request()->routeIs('user.home'))
                            <div class="dashboard-body__bar style ">
                                <button class="dashboard-body__bar-two-icon toggle-dashboard-right d-flex align-items-center gap-2" type="button" title="@lang('Wallet Overview')">
                                    <span class="icon-wallet"></span>
                                    <span class="d-none d-sm-inline">@lang('Wallet')</span>
                                </button>
                            </div>
                        @endif

                        @if (request()->routeIs('user.p2p*'))
                            <div class="p2p-sidebar__menu">
                                <span class="p2p-sidebar__menu-icon">
                                    <i class="fas fa-bars"></i>
                                </span>
                            </div>
                        @endif
                    </div>
                    @stack('topContent')
                    @yield('content')
                </div>
            </div>
            <!-- Wallet Overview Sidebar - Available on all pages -->
            <div class="dashboard-right">
                <div class="right-sidebar">
                    <div class="right-sidebar__header mb-3 skeleton">
                        <div class="d-flex flex-between flex-wrap">
                            <div>
                                <h4 class="mb-0 fs-18">@lang('Wallet Overview')</h4>
                                <p class="mt-0 fs-12">@lang('Available wallet balance including the converted total balance')</p>
                            </div>
                            <span class="toggle-dashboard-right dashboard--popup-close"><i class="las la-times"></i></span>
                        </div>
                    </div>
                    <div class="text-center mb-3 skeleton">
                        <h3 class="right-sidebar__number mb-0 pb-0 wallet-estimated-balance">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </h3>
                        <span class="fs-14 mt-0">@lang('Estimated Total Balance')</span>
                    </div>
                    <div class="right-sidebar__menu">
                        <div class="wallet-wrapper">
                            <!-- Wallet list will be loaded via AJAX -->
                        </div>
                        <button type="button" class="w-100 show-more-wallet right-sidebar__button skeleton mt-2" style="display: none;">
                            <span class="right-sidebar__button-icon">
                                <i class="las la-chevron-circle-down"></i>@lang('Show More')
                            </span>
                        </button>
                    </div>
                </div>
                <div class="right-sidebar mt-3">
                    <div class="right-sidebar__header mb-3 skeleton">
                        <h4 class="mb-0 fs-18">@lang('Deposit Money')</h4>
                        <p class="mt-0 fs-12">@lang('Make crypto & fiat deposits in a few steps')</p>
                    </div>
                    <div class="right-sidebar__deposit custom-select2">
                        <form class="skeleton deposit-form" action="{{ route('user.deposit.insert') }}" method="post">
                            @csrf
                            <div class="form-group position-relative" id="currency_list_wrapper">
                                <div class="input-group">
                                    <input type="number" step="any" name="amount" class="form--control form-control" placeholder="@lang('Amount')">
                                    <div class="input-group-text skeleton">
                                        <x-currency-list :action="route('user.currency.all')" valueType="2" logCurrency="true" />
                                    </div>
                                </div>
                            </div>
                            <button class="deposit__button btn btn--base w-100" type="submit">
                                <span class="icon-deposit"></span> @lang('Deposit')
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    @stack('script-lib')

    <script src="{{ asset($activeTemplateTrue . 'dashboard/js/main.js') }}"></script>

    <script>
        window.allow_decimal = "{{ gs('allow_decimal_after_number') }}";
    </script>

    @include('partials.notify')

    @php echo loadExtension('tawk-chat') @endphp

    @if (gs('pn'))
        @include('partials.push_script')
    @endif

    @stack('script')

    <script>
        (function($) {
            "use strict";

            // Wallet Overview Sidebar - Load data when opened
            let walletDataLoaded = false;
            let walletSkip = 3;

            // Override the default toggle handler to also load wallet data
            $(document).off('click', '.toggle-dashboard-right').on("click", ".toggle-dashboard-right", function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(".dashboard-right").toggleClass('show');
                
                // Load wallet data if not already loaded and sidebar is now showing
                if (!walletDataLoaded && $(".dashboard-right").hasClass('show')) {
                    loadWalletOverview();
                }
            });

            function loadWalletOverview() {
                $.ajax({
                    url: "{{ route('user.wallet.overview') }}",
                    type: "GET",
                    dataType: 'json',
                    cache: false,
                    beforeSend: function() {
                        $('.wallet-estimated-balance').html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                        $('.wallet-wrapper').html('');
                    },
                    success: function(resp) {
                        if (resp.success) {
                            // Update estimated balance
                            $('.wallet-estimated-balance').html(getAmount(resp.estimated_balance));
                            
                            // Load wallets
                            if (resp.wallets && resp.wallets.length > 0) {
                                let html = "";
                                $.each(resp.wallets, function(i, wallet) {
                                    html += `
                                        <div class="right-sidebar__item flex-wrap wallet-list skeleton">
                                            <div class="d-flex align-items-center">
                                                <span class="right-sidebar__item-icon">
                                                    <img src="${wallet.currency.image_url}">
                                                </span>
                                                <h6 class="right-sidebar__item-name">
                                                    ${wallet.currency.name.length > 10 ? wallet.currency.name.substring(0, 10) + '...' : wallet.currency.name}
                                                    <span class="fs-11 d-block">
                                                        ${wallet.currency.symbol}
                                                    </span>
                                                </h6>
                                            </div>
                                            <h6 class="right-sidebar__item-number">${getAmount(wallet.balance, false)}</h6>
                                        </div>
                                    `;
                                });
                                $('.wallet-wrapper').html(html);
                                
                                // Show "Show More" button if there might be more wallets
                                if (resp.wallets.length >= 3) {
                                    $('.show-more-wallet').show();
                                }
                                
                                // Remove skeleton class after a short delay
                                setTimeout(() => {
                                    $('.wallet-list').removeClass('skeleton');
                                }, 300);
                            }
                            walletDataLoaded = true;
                        }
                    },
                    error: function() {
                        $('.wallet-estimated-balance').html('0.00');
                        notify('error', "@lang('Failed to load wallet data')");
                    }
                });
            }

            // Show More Wallets functionality
            $(document).on('click', '.show-more-wallet', function(e) {
                let $this = $(this);
                let route = "{{ route('user.more.wallet', ':skip') }}";
                
                $.ajax({
                    url: route.replace(':skip', walletSkip),
                    type: "GET",
                    dataType: 'json',
                    cache: false,
                    beforeSend: function() {
                        $this.html(`
                            <span class="right-sidebar__button-icon">
                                <i class="las la-spinner la-spin"></i>
                            </span>
                        `).attr('disabled', true);
                    },
                    complete: function() {
                        setTimeout(() => {
                            $this.html(`
                                <span class="right-sidebar__button-icon">
                                    <i class="las la-chevron-circle-down"></i>@lang('Show More')
                                </span>
                            `).attr('disabled', false);
                            $('.wallet-list').removeClass('skeleton');
                        }, 500);
                    },
                    success: function(resp) {
                        if (resp.success && (resp.wallets && resp.wallets.length > 0)) {
                            let html = "";
                            $.each(resp.wallets, function(i, wallet) {
                                html += `
                                    <div class="right-sidebar__item wallet-list skeleton">
                                        <div class="d-flex align-items-center">
                                            <span class="right-sidebar__item-icon">
                                                <img src="${wallet.currency.image_url}">
                                            </span>
                                            <h6 class="right-sidebar__item-name">
                                                ${wallet.currency.name}
                                                <span class="fs-11 d-block">
                                                    ${wallet.currency.symbol}
                                                </span>
                                            </h6>
                                        </div>
                                        <h6 class="right-sidebar__item-number">${getAmount(wallet.balance, false)}</h6>
                                    </div>
                                `;
                            });
                            walletSkip += 3;
                            $('.wallet-wrapper').append(html);
                            
                            // Remove skeleton class
                            setTimeout(() => {
                                $('.wallet-list').removeClass('skeleton');
                            }, 300);
                        } else {
                            $this.remove();
                        }

                        $('.right-sidebar__menu').animate({
                            scrollTop: $('.right-sidebar__menu')[0].scrollHeight + 150
                        }, "slow");
                    },
                    error: function() {
                        notify('error', "@lang('Something went to wrong')");
                        $this.remove();
                    }
                });
            });

            var inputElements = $('[type=text],[type=password],select,textarea');
            $.each(inputElements, function(index, element) {
                element = $(element);
                element.closest('.form-group').find('label').attr('for', element.attr('name'));
                element.attr('id', element.attr('name'))
            });

            $.each($('input, select, textarea'), function(i, element) {
                if (element.hasAttribute('required')) {
                    $(element).closest('.form-group').find('label').addClass('required');
                }
            });

            $('.showFilterBtn').on('click', function() {
                $('.responsive-filter-card').slideToggle();
            });

            Array.from(document.querySelectorAll('table')).forEach(table => {
                let heading = table.querySelectorAll('thead tr th');
                Array.from(table.querySelectorAll('tbody tr')).forEach((row) => {
                    if (row.querySelectorAll('td').length > 1) {
                        Array.from(row.querySelectorAll('td')).forEach((colum, i) => {
                            colum.setAttribute('data-label', heading[i].innerText)
                        });
                    }
                });
            });

            @if (session('app'))
                $('.btn--base').each(function() {
                    var isInForm = $(this).closest('form').length > 0;
                    if (isInForm) {
                        $(this).closest('form').on("submit", function() {
                            let html = `<span class="spinner-border spinner-border-sm" role="status"></span>`;
                            $(this).find('.btn--base').attr('disabled', true).html(html);
                        });
                    } else {
                        $(this).on('click', function() {
                            let html = `<span class="spinner-border spinner-border-sm" role="status"></span>`;
                            $(this).attr('disabled', true).html(html);
                        });
                    }
                });
            @endif
        })(jQuery);
    </script>
</body>

</html>
