Pusher.logToConsole = false; // Disabled to reduce console noise

const PUSHER_APP_KEY = window.atob(window.my_pusher.app_key);
const PUSHER_CLUSTER = window.atob(window.my_pusher.app_cluster);
const BASE_URL       = my_pusher.base_url;

// Check if Pusher credentials are valid before initializing
var pusher = null;
if (PUSHER_APP_KEY && PUSHER_APP_KEY.length > 0 && PUSHER_APP_KEY !== 'null') {
    try {
        pusher = new Pusher(PUSHER_APP_KEY, {
            cluster: PUSHER_CLUSTER,
            enabledTransports: ['ws', 'wss'],
            disabledTransports: ['sockjs', 'xhr_polling', 'xhr_streaming'],
        });
        
        pusher.connection.bind('error', function(err) {
            console.warn('⚠️ Pusher connection error (non-critical for ZPH pairs):', err.error?.data?.code || 'unknown');
        });
        
        pusher.connection.bind('unavailable', function() {
            console.warn('⚠️ Pusher unavailable (non-critical for ZPH pairs)');
        });
    } catch (error) {
        console.warn('⚠️ Pusher initialization failed (non-critical for ZPH pairs):', error.message);
    }
} else {
    console.warn('⚠️ Pusher credentials not configured (non-critical for ZPH pairs)');
}

const pusherConnection = (eventName, callback) => {
    if (!pusher) {
        console.warn('⚠️ Pusher not initialized, skipping subscription to:', eventName);
        return;
    }
    
    pusher.connection.bind('connected', () => {
        const SOCKET_ID = pusher.connection.socket_id;
        const CHANNEL_NAME = `private-${eventName}`;
        pusher.config.authEndpoint = `${BASE_URL}/pusher/auth/${SOCKET_ID}/${CHANNEL_NAME}`;
        let channel = pusher.subscribe(CHANNEL_NAME);
        channel.bind('pusher:subscription_succeeded', function () {
            channel.bind(eventName, function (data) {
                callback(data);
            })
        });
    });
};

function marketChangeHtml(data) {
    $.each(data.marketData, function (i, marketData) {
        marketData = JSON.parse(marketData);
        let htmlClass = marketData.html_classes;
        $(`body`).find(`.market-price-${marketData.id}`).text(getAmount(marketData.price)).removeClass('up down').addClass(htmlClass.price_change);
        $(`body`).find(`.market-price-symbol-${marketData.id}`).removeClass('up down').addClass(htmlClass.price_change);
        if ($(`body`).find(`.price-icon-${marketData.id}`).length > 0) {
            $(`body`).find(`.price-icon-${marketData.id}`).removeClass('up down').addClass(htmlClass.price_change);
            if (htmlClass.price_change == 'up') {
                $(`body`).find(`.price-icon-${marketData.id} i`).removeClass('fa-arrow-down').addClass('fa-arrow-up');
            } else {
                $(`body`).find(`.price-icon-${marketData.id} i`).removeClass('fa-arrow-up').addClass('fa-arrow-down');
            }
        }
        $(`body`).find(`.market-last-price-${marketData.id}`).text(getAmount(marketData.last_price));
        $(`body`).find(`.market-percent-change-1h-${marketData.id}`).text(getAmount(marketData.percent_change_1h, 2) + '%').removeClass('up down').addClass(htmlClass.percent_change_1h);
        $(`body`).find(`.market-percent-change-24h-${marketData.id}`).text(getAmount(marketData.percent_change_24h, 2) + '%').removeClass('up down').addClass(htmlClass.percent_change_24h);
        $(`body`).find(`.market-market_cap-${marketData.id}`).text(getAmount(marketData.market_cap));

        if (window.visit_pair && Object.keys(window.visit_pair).length > 0 && parseInt(window.visit_pair.selection) == parseInt(marketData.id)) {
            let visitPair = window.visit_pair;
            document.title = `${visitPair.site_name} -  ${getAmount(marketData.price)} | ${visitPair.symbol}`;
        }
    });
}