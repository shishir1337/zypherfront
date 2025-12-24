@php
    $user = auth()->user();
@endphp
<div class="dashboard-header">
    <div class="dashboard-header__inner">
        <div class="dashboard-header__left d-none d-md-block">
            <div class="copy-link">
                <input type="text" class="copyText" value="{{ route('home') }}?reference={{ $user->username }}" readonly>
                <button class="copy-link__button copyTextBtn" data-bs-toggle="tooltip" data-bs-placement="right" title="@lang('Copy URL')">
                    <span class="copy-link__icon"><i class="las la-copy"></i>
                    </span>
                </button>
            </div>
        </div>
        <div class="dashboard-header__right">
            <a href="{{ route('trade') }}" target="_blank" class="btn btn--base outline btn--sm trade-btn">
                <span class="icon-trade"></span> @lang('TRADE')
            </a>
            <button class="btn btn--success outline btn--sm d-none d-md-inline-flex toggle-dashboard-right" type="button" title="@lang('Wallet Overview')">
                <span class="icon-deposit"></span> @lang('Deposit')
            </button>
            <div class="user-info">
                <div class="user-info__right">
                    <div class="user-info__button">
                        <div class="user-info__profile">
                            @if($user->image)
                                <img src="{{ getImage(getFilePath('userProfile').'/'. $user->image, getFileSize('userProfile'), true) }}" alt="@lang('Profile')" class="user-info__avatar">
                            @else
                                <div class="user-info__avatar user-info__avatar--default">
                                    <i class="las la-user"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <ul class="user-info-dropdown">
                    <li class="user-info-dropdown__item">
                        <a class="user-info-dropdown__link" href="{{ route('user.profile.setting') }}">
                            <span class="icon"><i class="far fa-user-circle"></i></span>
                            <span class="text">@lang('My Profile')</span>
                        </a>
                    </li>
                    <li class="user-info-dropdown__item">
                        <a class="user-info-dropdown__link" href="{{ route('user.change.password') }}">
                            <span class="icon"><i class="fa fa-key"></i></span>
                            <span class="text">@lang('Change Password')</span>
                        </a>
                    </li>
                    <li class="user-info-dropdown__item">
                        <a class="user-info-dropdown__link" href="{{ route('user.logout') }}">
                            <span class="icon"><i class="far fa-user-circle"></i></span>
                            <span class="text">@lang('Logout')</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    .user-info__avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
    }
    
    .user-info__avatar--default {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: hsl(var(--base));
        display: flex;
        align-items: center;
        justify-content: center;
        color: hsl(var(--white));
        font-size: 20px;
    }
    
    .user-info__profile {
        display: flex;
        align-items: center;
    }
</style>
@endpush
