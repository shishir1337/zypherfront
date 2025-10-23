@php
 $meta   = (object) $meta;
 $pair   = $meta->pair;
 $isZypher = strpos($pair->symbol, 'ZPH') !== false;
@endphp

<div class="trading-chart p-0 two">
    <div class="chart-header" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background: #1e222d; border-bottom: 1px solid #2B2B43;">
        <div class="chart-title" style="color: #d1d4dc; font-size: 14px; font-weight: 500;">
            {{ $pair->symbol }} Chart
        </div>
        <div class="interval-buttons" style="display: flex; gap: 5px;">
            <button class="interval-btn active" data-interval="1m" data-zypher="1" style="padding: 5px 12px; background: #2962ff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">1m</button>
            <button class="interval-btn" data-interval="5m" data-zypher="5" style="padding: 5px 12px; background: #2B2B43; color: #d1d4dc; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">5m</button>
            <button class="interval-btn" data-interval="15m" data-zypher="15" style="padding: 5px 12px; background: #2B2B43; color: #d1d4dc; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">15m</button>
            <button class="interval-btn" data-interval="1h" data-zypher="60" style="padding: 5px 12px; background: #2B2B43; color: #d1d4dc; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">1h</button>
            <button class="interval-btn" data-interval="1d" data-zypher="1D" style="padding: 5px 12px; background: #2B2B43; color: #d1d4dc; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;">1d</button>
        </div>
    </div>
    <div id="trading-chart-container" style="width: 100%; height: 450px;"></div>
</div>

@push('style')
<style>
    .market-price-{{ @$pair->marketData->id }},
    .price-icon-{{ @$pair->marketData->id }},
    .order-book-price-all,
    .market-last-price-{{ @$pair->marketData->id }},
    .market-percent-change-1h-{{ @$pair->marketData->id }} {
        font-variant-numeric: tabular-nums;
        font-family: 'SF Mono', 'Roboto Mono', 'Courier New', monospace;
        font-weight: 500;
        letter-spacing: 0.5px;
        transition: color 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }
    
    #trading-chart-container div[style*="position: absolute"],
    #trading-chart-container a[href*="tradingview"] {
        display: none !important;
    }
    
    #trading-chart-container {
        cursor: crosshair;
        position: relative;
        overflow: hidden;
    }
    
    .interval-btn:hover {
        background: #3a3e4a !important;
    }
    
    .interval-btn.active {
        background: #2962ff !important;
        color: white !important;
    }
    
    .interval-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
@endpush

@push('script-lib')
    <script src="{{ asset($activeTemplateTrue . 'js/lightweight-chart.js') }}?v={{ time() }}"></script>
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
@endpush

@push('script')
<script>
    'use strict';
    (function($) {
        const ZYPHER_API_URL = 'https://zypher.bigbuller.com/api';
        const ZYPHER_SOCKET_URL = 'https://zypher.bigbuller.com';
        const PAIR_SYMBOL = '{{ $pair->symbol }}';
        const IS_ZYPHER = {{ $isZypher ? 'true' : 'false' }};
        
        let chart, candlestickSeries, socket, webSocket;
        let currentCandle = null;
        let currentPrice = 0;
        let lastPrice = 0;
        let isLoadingMore = false;
        let oldestTimestamp = null;
        let allCandles = [];
        let currentInterval = '1m'; // Default interval
        let currentZypherResolution = '1'; // Default Zypher resolution

        const chartOptions = {
            width: document.getElementById('trading-chart-container')?.offsetWidth || 1200,
            height: 450,
            layout: {
                background: { color: '#1e222d' },
                textColor: '#d1d4dc',
            },
            grid: {
                vertLines: { color: '#2B2B43' },
                horzLines: { color: '#2B2B43' },
            },
            crosshair: {
                mode: LightweightCharts.CrosshairMode.Normal,
            },
            rightPriceScale: {
                borderColor: '#2B2B43',
            },
            timeScale: {
                borderColor: '#2B2B43',
                timeVisible: true,
                secondsVisible: false,
            },
            watermark: {
                visible: false,
            },
        };

        function initChart() {
            try {
                if (typeof LightweightCharts === 'undefined') {
                    return;
                }
                
                const container = document.getElementById('trading-chart-container');
                if (!container) return;
                
                chart = LightweightCharts.createChart(container, chartOptions);

                candlestickSeries = chart.addCandlestickSeries({
                    upColor: '#26a69a',
                    downColor: '#ef5350',
                    borderUpColor: '#26a69a',
                    borderDownColor: '#ef5350',
                    borderVisible: true,
                    wickUpColor: '#26a69a',
                    wickDownColor: '#ef5350',
                    wickVisible: true,
                });

                loadHistoricalData();
                
                if (IS_ZYPHER) {
                    setupZypherConnection();
                } else {
                    setupBinanceConnection();
                    
                    // Infinite scroll for Binance
                    chart.timeScale().subscribeVisibleLogicalRangeChange(() => {
                        const logicalRange = chart.timeScale().getVisibleLogicalRange();
                        if (logicalRange !== null && logicalRange.from < 10 && !isLoadingMore) {
                            loadMoreBinanceData();
                        }
                    });
                }

                window.addEventListener('resize', () => {
                    chart.applyOptions({ 
                        width: document.getElementById('trading-chart-container').offsetWidth 
                    });
                });
            } catch (error) {
                console.error('Chart error:', error.message);
            }
        }

        async function loadHistoricalData() {
            try {
                if (IS_ZYPHER) {
                    await loadZypherData();
                } else {
                    await loadBinanceData();
                }
            } catch (error) {
                console.error('Error loading data:', error.message);
            }
        }

        async function loadZypherData() {
            try {
                const toTimestamp = Math.floor(Date.now() / 1000);
                // Always fetch more data for aggregation
                let timeRange = 6 * 3600; // Default 6 hours
                if (currentInterval === '5m') timeRange = 12 * 3600; // 12 hours for 5m
                else if (currentInterval === '15m') timeRange = 24 * 3600; // 24 hours for 15m
                else if (currentInterval === '1h') timeRange = 7 * 24 * 3600; // 7 days for 1h
                else if (currentInterval === '1d') timeRange = 90 * 24 * 3600; // 90 days for 1d
                
                const fromTimestamp = toTimestamp - timeRange;
                // Always fetch 1-minute data from Zypher (only resolution they support)
                const url = `${ZYPHER_API_URL}/tradingview/history?symbol=ZPHUSD&resolution=1&from=${fromTimestamp}&to=${toTimestamp}`;
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (!data.s || data.s !== 'ok') {
                    throw new Error(data.errmsg || 'Invalid Zypher response');
                }
                
                if (data.t && data.t.length > 0) {
                    const oneMinuteCandles = data.t.map((time, i) => ({
                        time: time,
                        open: parseFloat(data.o[i]),
                        high: parseFloat(data.h[i]),
                        low: parseFloat(data.l[i]),
                        close: parseFloat(data.c[i])
                    }));
                    
                    if (oneMinuteCandles.length > 0) {
                        // Aggregate candles if interval is not 1m
                        const candles = currentInterval === '1m' 
                            ? oneMinuteCandles 
                            : aggregateCandles(oneMinuteCandles, getIntervalSeconds());
                        
                        allCandles = candles;
                        candlestickSeries.setData(candles);
                        lastPrice = candles[candles.length - 1].close;
                        chart.timeScale().fitContent();
                    }
                } else {
                    console.warn('No Zypher candle data available');
                }
            } catch (error) {
                console.error('Zypher error:', error.message);
            }
        }
        
        function aggregateCandles(oneMinuteCandles, intervalSeconds) {
            const aggregated = [];
            const grouped = {};
            
            // Group 1-minute candles by the target interval
            oneMinuteCandles.forEach(candle => {
                const periodStart = Math.floor(candle.time / intervalSeconds) * intervalSeconds;
                
                if (!grouped[periodStart]) {
                    grouped[periodStart] = [];
                }
                grouped[periodStart].push(candle);
            });
            
            // Aggregate each group into a single candle
            Object.keys(grouped).sort((a, b) => a - b).forEach(periodStart => {
                const candlesInPeriod = grouped[periodStart];
                
                if (candlesInPeriod.length > 0) {
                    aggregated.push({
                        time: parseInt(periodStart),
                        open: candlesInPeriod[0].open,
                        high: Math.max(...candlesInPeriod.map(c => c.high)),
                        low: Math.min(...candlesInPeriod.map(c => c.low)),
                        close: candlesInPeriod[candlesInPeriod.length - 1].close
                    });
                }
            });
            
            return aggregated;
        }

        async function loadBinanceData() {
            const symbol = PAIR_SYMBOL.replace('_', '').toUpperCase();
            const binanceSymbol = symbol.endsWith('USD') && !symbol.endsWith('USDT') 
                ? symbol.replace('USD', 'USDT') 
                : symbol;
            
            const url = `https://api.binance.com/api/v3/klines?symbol=${binanceSymbol}&interval=${currentInterval}&limit=500`;
            
            try {
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.code) {
                    // Binance error response
                    throw new Error(data.msg || 'Binance API error');
                }
                
                if (Array.isArray(data) && data.length > 0) {
                    const candles = data.map(d => ({
                        time: d[0] / 1000,
                        open: parseFloat(d[1]),
                        high: parseFloat(d[2]),
                        low: parseFloat(d[3]),
                        close: parseFloat(d[4])
                    }));
                    
                    allCandles = candles;
                    oldestTimestamp = candles[0].time;
                    candlestickSeries.setData(allCandles);
                    lastPrice = candles[candles.length - 1].close;
                    chart.timeScale().fitContent();
                } else {
                    console.warn('No Binance candle data available');
                }
            } catch (error) {
                console.error('Binance error:', error.message);
            }
        }

        async function loadMoreBinanceData() {
            if (isLoadingMore || !oldestTimestamp) return;
            
            isLoadingMore = true;
            const symbol = PAIR_SYMBOL.replace('_', '').toUpperCase();
            const binanceSymbol = symbol.endsWith('USD') && !symbol.endsWith('USDT') 
                ? symbol.replace('USD', 'USDT') 
                : symbol;
            
            const endTime = (oldestTimestamp * 1000) - 1;
            const url = `https://api.binance.com/api/v3/klines?symbol=${binanceSymbol}&interval=${currentInterval}&limit=500&endTime=${endTime}`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (Array.isArray(data) && data.length > 0) {
                    const newCandles = data.map(d => ({
                        time: d[0] / 1000,
                        open: parseFloat(d[1]),
                        high: parseFloat(d[2]),
                        low: parseFloat(d[3]),
                        close: parseFloat(d[4])
                    }));
                    
                    allCandles = [...newCandles, ...allCandles];
                    oldestTimestamp = newCandles[0].time;
                    candlestickSeries.setData(allCandles);
                }
            } catch (error) {
                // Silently fail
            } finally {
                isLoadingMore = false;
            }
        }

        function setupZypherConnection() {
            if (typeof io === 'undefined') return;
            
            socket = io(ZYPHER_SOCKET_URL);
            
            socket.on('connect', () => {
                socket.emit('subscribe', { symbol: 'ZPHUSD' });
            });

            socket.on('live_ohlc', (data) => {
                if (data.symbol === 'ZPHUSD') {
                    updateCandleFromOHLC(data);
                    updatePriceDisplays(parseFloat(data.close));
                }
            });
            
            socket.on('price_update', (data) => {
                if (data.symbol === 'ZPHUSD') {
                    updateCandle(parseFloat(data.price));
                    updatePriceDisplays(parseFloat(data.price));
                }
            });
        }

        function setupBinanceConnection() {
            // Close existing connection if any
            if (webSocket) {
                webSocket.close();
            }
            
            const symbol = PAIR_SYMBOL.replace('_', '').toLowerCase();
            const binanceSymbol = symbol.endsWith('usd') && !symbol.endsWith('usdt') 
                ? symbol.replace('usd', 'usdt') 
                : symbol;
            
            const wsUrl = `wss://stream.binance.com:9443/ws/${binanceSymbol}@kline_${currentInterval}`;
            
            try {
                webSocket = new WebSocket(wsUrl);
                
                webSocket.onopen = () => {
                    console.log('Binance chart WebSocket connected');
                };
                
                webSocket.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        if (data.k) {
                            const kline = data.k;
                            updateCandleFromOHLC({
                                open: parseFloat(kline.o),
                                high: parseFloat(kline.h),
                                low: parseFloat(kline.l),
                                close: parseFloat(kline.c)
                            });
                            updatePriceDisplays(parseFloat(kline.c));
                        }
                    } catch (error) {
                        console.error('Error parsing WebSocket message:', error);
                    }
                };
                
                webSocket.onerror = (error) => {
                    console.error('Binance chart WebSocket error:', error);
                };
                
                webSocket.onclose = () => {
                    console.warn('Binance chart WebSocket closed, will reconnect on interval change');
                };
            } catch (error) {
                console.error('Error creating Binance chart WebSocket:', error);
            }
        }

        function getIntervalSeconds() {
            const intervals = {
                '1m': 60,
                '5m': 300,
                '15m': 900,
                '1h': 3600,
                '1d': 86400
            };
            return intervals[currentInterval] || 60;
        }

        function updateCandleFromOHLC(data) {
            if (!candlestickSeries) return;
            
            const close = parseFloat(data.close);
            const open = parseFloat(data.open) || close;
            const high = parseFloat(data.high) || close;
            const low = parseFloat(data.low) || close;
            
            if (isNaN(close) || !isFinite(close) || close <= 0 ||
                isNaN(open) || !isFinite(open) || open <= 0 ||
                isNaN(high) || !isFinite(high) || high <= 0 ||
                isNaN(low) || !isFinite(low) || low <= 0) {
                return;
            }
            
            const timestamp = Math.floor(Date.now() / 1000);
            const intervalSeconds = getIntervalSeconds();
            const currentPeriodStart = Math.floor(timestamp / intervalSeconds) * intervalSeconds;

            if (!currentCandle || currentCandle.time !== currentPeriodStart) {
                currentCandle = {
                    time: currentPeriodStart,
                    open: open,
                    high: high,
                    low: low,
                    close: close
                };
            } else {
                currentCandle = {
                    time: currentPeriodStart,
                    open: currentCandle.open,
                    high: Math.max(currentCandle.high, high),
                    low: Math.min(currentCandle.low, low),
                    close: close
                };
            }
            
            if (currentCandle.open > 0 && currentCandle.high > 0 && 
                currentCandle.low > 0 && currentCandle.close > 0) {
                try {
                    candlestickSeries.update(currentCandle);
                } catch (error) {
                    // Silently fail
                }
            }
        }

        function updateCandle(price) {
            if (!candlestickSeries || !price || isNaN(price) || price <= 0) return;
            
            const timestamp = Math.floor(Date.now() / 1000);
            const intervalSeconds = getIntervalSeconds();
            const currentPeriodStart = Math.floor(timestamp / intervalSeconds) * intervalSeconds;

            if (!currentCandle || currentCandle.time !== currentPeriodStart) {
                currentCandle = {
                    time: currentPeriodStart,
                    open: price,
                    high: price,
                    low: price,
                    close: price
                };
            } else {
                currentCandle.high = Math.max(currentCandle.high || price, price);
                currentCandle.low = Math.min(currentCandle.low || price, price);
                currentCandle.close = price;
            }
            
            if (currentCandle.open > 0 && currentCandle.high > 0 && 
                currentCandle.low > 0 && currentCandle.close > 0) {
                try {
                    candlestickSeries.update(currentCandle);
                } catch (error) {
                    // Silently fail
                }
            }
        }

        function updatePriceDisplays(price) {
            const marketDataId = '{{ @$pair->marketData->id }}';
            const displayPrice = price.toFixed(4);
            
            $('.market-price-' + marketDataId).text(displayPrice);
            $('.order-book-price-all span').text(displayPrice);
            $('.buy-rate, .sell-rate').filter(':not(:focus)').val(displayPrice);
        }

        function changeInterval(interval, zypherResolution) {
            // Update current interval
            currentInterval = interval;
            currentZypherResolution = zypherResolution;
            
            // Reset candle
            currentCandle = null;
            
            // Update button states
            $('.interval-btn').removeClass('active').css({
                'background': '#2B2B43',
                'color': '#d1d4dc'
            });
            $(`.interval-btn[data-interval="${interval}"]`).addClass('active').css({
                'background': '#2962ff',
                'color': 'white'
            });
            
            // Reload chart data
            loadHistoricalData();
            
            // Reconnect WebSocket with new interval (only for Binance)
            if (!IS_ZYPHER && webSocket) {
                setupBinanceConnection();
            }
        }

        $(document).ready(function() {
            setTimeout(initChart, 100);
            
            // Handle interval button clicks
            $('.interval-btn').on('click', function() {
                const interval = $(this).data('interval');
                const zypherResolution = $(this).data('zypher');
                changeInterval(interval, zypherResolution.toString());
            });
        });

        $(window).on('beforeunload', function() {
            if (socket) socket.disconnect();
            if (webSocket) webSocket.close();
        });

    })(jQuery);
</script>
@endpush
