@php
 $meta   = (object) $meta;
 $pair   = $meta->pair;
 $isZypher = strpos($pair->symbol, 'ZPH') !== false;
@endphp

<div class="trading-chart p-0 two">
    {{-- Lightweight Charts for ALL pairs --}}
    <div id="trading-chart-container" style="width: 100%; height: 450px;"></div>
</div>

{{-- Scripts for all pairs --}}
@push('style')
<style>
    /* Quotex-style ultra-smooth price updates */
    .market-price-{{ @$pair->marketData->id }},
    .price-icon-{{ @$pair->marketData->id }},
    .order-book-price-all,
    .market-last-price-{{ @$pair->marketData->id }},
    .market-percent-change-1h-{{ @$pair->marketData->id }} {
        /* Fixed-width numbers prevent layout shift */
        font-variant-numeric: tabular-nums;
        font-family: 'SF Mono', 'Roboto Mono', 'Courier New', monospace;
        font-weight: 500;
        letter-spacing: 0.5px;
        
        /* Buttery smooth color transitions (Quotex-style) */
        transition: 
            color 0.5s cubic-bezier(0.4, 0, 0.2, 1),
            background-color 0.5s cubic-bezier(0.4, 0, 0.2, 1),
            opacity 0.3s ease-out;
        
        /* GPU acceleration + subpixel rendering */
        will-change: contents;
        transform: translateZ(0) translate3d(0, 0, 0);
        -webkit-font-smoothing: subpixel-antialiased;
        -moz-osx-font-smoothing: grayscale;
        backface-visibility: hidden;
        
        /* Prevent text selection flicker */
        user-select: none;
        -webkit-user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Prevent layout shift during updates */
    .trading-header__title,
    .trading-header-number {
        min-width: 120px;
    }
    
    /* Remove TradingView watermark/attribution completely */
    #trading-chart-container div[style*="position: absolute"],
    #trading-chart-container a[href*="tradingview"],
    #trading-chart-container > div > div:last-child {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Smooth chart interactions */
    #trading-chart-container {
        cursor: crosshair;
        position: relative;
        overflow: hidden;
    }
    
    /* Smooth chart canvas rendering */
    #trading-chart-container canvas {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
</style>
@endpush

@push('script-lib')
    <script src="{{ asset($activeTemplateTrue . 'js/lightweight-chart.js') }}"></script>
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
@endpush

@push('script')
<script>
    'use strict';
    (function($) {
        const ZYPHER_API_URL = 'http://localhost:3001/api';
        const ZYPHER_SOCKET_URL = 'http://localhost:3001';
        const PAIR_SYMBOL = '{{ $pair->symbol }}';
        const IS_ZYPHER = {{ $isZypher ? 'true' : 'false' }};
        
        let chart;
        let candlestickSeries;
        let socket;
        let webSocket;
        let currentCandle = null;
        let currentPrice = 0;
        let priceLine = null;

        // Chart configuration
        const chartOptions = {
            width: document.getElementById('trading-chart-container').offsetWidth,
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
                visible: false,  // Hide TradingView watermark
            },
            handleScale: {
                mouseWheel: true,
                pinch: true,
                axisPressedMouseMove: true,
            },
            handleScroll: {
                mouseWheel: true,
                pressedMouseMove: true,
                horzTouchDrag: true,
                vertTouchDrag: true,
            },
        };

        function initChart() {
            chart = LightweightCharts.createChart(
                document.getElementById('trading-chart-container'),
                chartOptions
            );

            // Add candlestick series
            candlestickSeries = chart.addCandlestickSeries({
                upColor: '#26a69a',
                downColor: '#ef5350',
                borderVisible: false,
                wickUpColor: '#26a69a',
                wickDownColor: '#ef5350',
            });

            // Load historical data
            loadHistoricalData();
            
            // Setup real-time updates (Socket.IO for ZPH, WebSocket for others)
            if (IS_ZYPHER) {
                setupSocketIO();
                startPricePolling(); // Fallback for ZPH
            } else {
                setupBinanceWebSocket();
            }

            // Auto-resize chart
            window.addEventListener('resize', () => {
                chart.applyOptions({ 
                    width: document.getElementById('trading-chart-container').offsetWidth 
                });
            });
        }

        async function loadHistoricalData() {
            try {
                let apiUrl, candles;
                const symbol = PAIR_SYMBOL.replace('_', '');
                
                if (IS_ZYPHER) {
                    // Zypher API for ZPH pairs
                    const toTimestamp = Math.floor(Date.now() / 1000);
                    const fromTimestamp = toTimestamp - (24 * 60 * 60); // Last 24 hours
                    apiUrl = `${ZYPHER_API_URL}/tradingview/history?symbol=ZPHUSD&resolution=1&from=${fromTimestamp}&to=${toTimestamp}`;
                    
                    const response = await fetch(apiUrl);
                    const data = await response.json();
                    
                    if (data.s === 'ok' && data.t && data.t.length > 0) {
                        candles = data.t.map((time, i) => ({
                            time: time,
                            open: parseFloat(data.o[i]),
                            high: parseFloat(data.h[i]),
                            low: parseFloat(data.l[i]),
                            close: parseFloat(data.c[i]),
                        }));
                        console.log('✅ Loaded', candles.length, 'candles from Zypher API');
                    } else {
                        console.warn('⚠️ No data available from Zypher API');
                        showNoDataMessage();
                        return;
                    }
                } else {
                    // Binance API for other pairs - Try with USDT if USD doesn't work
                    let binanceSymbol = symbol.toUpperCase();
                    // Convert USD to USDT for Binance compatibility
                    if (binanceSymbol.endsWith('USD') && !binanceSymbol.endsWith('USDT')) {
                        binanceSymbol = binanceSymbol.replace('USD', 'USDT');
                    }
                    
                    apiUrl = `https://api.binance.com/api/v3/klines?symbol=${binanceSymbol}&interval=1m&limit=1440`; // Last 24 hours of 1-minute candles
                    
                    console.log('📡 Fetching from Binance:', apiUrl);
                    const response = await fetch(apiUrl);
                    const data = await response.json();
                    
                    if (Array.isArray(data) && data.length > 0) {
                        candles = data.map(d => ({
                            time: d[0] / 1000,
                            open: parseFloat(d[1]),
                            high: parseFloat(d[2]),
                            low: parseFloat(d[3]),
                            close: parseFloat(d[4]),
                        }));
                        console.log('✅ Loaded', candles.length, 'candles from Binance API');
                    } else {
                        console.warn('⚠️ No data available from Binance for', binanceSymbol);
                        console.log('API Response:', data);
                        showErrorMessage(`No Binance data for ${binanceSymbol}. This pair may not be available on Binance.`);
                        return;
                    }
                }
                
                if (candles && candles.length > 0) {
                    candlestickSeries.setData(candles);
                    chart.timeScale().fitContent();
                }
            } catch (error) {
                console.error('❌ Error loading historical data:', error);
                showErrorMessage('Failed to load chart data: ' + error.message);
            }
        }

        let lastCandleUpdate = 0;
        let candleUpdateThrottle = 600; // Very slow for Quotex-style smoothness (1 second = 1 update/sec)
        let pendingCandleUpdate = null;
        let queuedCandlePrice = null;
        let smoothCandleEnabled = true; // Enable smooth candle transitions

        function updateCandle(price) {
            if (!candlestickSeries) return;
            
            // Queue the latest price
            queuedCandlePrice = price;
            
            // Heavy throttling for smooth, controlled updates
            const now = Date.now();
            if (now - lastCandleUpdate < candleUpdateThrottle) {
                return; // Skip this update, will use queued price on next cycle
            }
            
            // Use queued price for update
            const priceToUse = queuedCandlePrice || price;
            
            // Cancel any pending candle update
            if (pendingCandleUpdate) {
                cancelAnimationFrame(pendingCandleUpdate);
            }
            
            pendingCandleUpdate = requestAnimationFrame(() => {
                performCandleUpdate(priceToUse);
                pendingCandleUpdate = null;
                lastCandleUpdate = Date.now();
                queuedCandlePrice = null;
            });
        }
        
        function performCandleUpdate(price) {
            const timestamp = Math.floor(Date.now() / 1000);
            const currentMinuteStart = Math.floor(timestamp / 60) * 60;

            if (!currentCandle || currentCandle.time !== currentMinuteStart) {
                // New minute - create new candle
                currentCandle = {
                    time: currentMinuteStart,
                    open: price,
                    high: price,
                    low: price,
                    close: price
                };
            } else {
                // Same minute - update existing candle (OHLC)
                // Only update if values actually changed
                const newHigh = Math.max(currentCandle.high, price);
                const newLow = Math.min(currentCandle.low, price);
                
                // Skip update if nothing changed
                if (currentCandle.close === price && 
                    currentCandle.high === newHigh && 
                    currentCandle.low === newLow) {
                    return;
                }
                
                currentCandle = {
                    time: currentMinuteStart,
                    open: currentCandle.open,  // Open stays the same
                    high: newHigh,  // Track highest
                    low: newLow,    // Track lowest
                    close: price  // Current price
                };
            }
            
            // Update chart smoothly
            candlestickSeries.update(currentCandle);
            updatePriceLine(price);
        }

        function updatePriceLine(price) {
            if (!candlestickSeries) return;
            
            // Remove old price line
            if (priceLine) {
                candlestickSeries.removePriceLine(priceLine);
            }
            
            // Create new price line at current price
            priceLine = candlestickSeries.createPriceLine({
                price: price,
                color: '#2196F3',
                lineWidth: 1,
                lineStyle: LightweightCharts.LineStyle.Dashed,
                axisLabelVisible: true,
                title: 'Current Price',
            });
        }

        let lastDisplayedPrice = 0;
        let pendingPriceUpdate = null;
        let lastUpdateTime = 0;
        let updateThrottle = 300; // Slower updates = smoother (300ms between updates)
        let animatingPrice = false;
        let targetPrice = 0;

        function updatePriceDisplays(price) {
            // Store target price for smooth animation
            targetPrice = price;
            
            // Throttle updates more aggressively for ultra-smooth feel
            const now = Date.now();
            if (now - lastUpdateTime < updateThrottle || animatingPrice) {
                return;
            }
            
            // Cancel any pending update
            if (pendingPriceUpdate) {
                cancelAnimationFrame(pendingPriceUpdate);
            }
            
            pendingPriceUpdate = requestAnimationFrame(() => {
                animatePriceTransition(lastDisplayedPrice, targetPrice);
                pendingPriceUpdate = null;
                lastUpdateTime = Date.now();
            });
        }
        
        // Smooth number animation (counts up/down like Quotex)
        function animatePriceTransition(from, to) {
            if (Math.abs(to - from) < 0.001 || from === 0) {
                // Skip animation for tiny changes or first load
                performPriceUpdate(to);
                return;
            }
            
            animatingPrice = true;
            const duration = 250; // 250ms animation duration
            const startTime = performance.now();
            const difference = to - from;
            
            function animate(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Easing function for smooth animation (ease-out)
                const eased = 1 - Math.pow(1 - progress, 3);
                const currentPrice = from + (difference * eased);
                
                performPriceUpdate(currentPrice, progress < 1);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    animatingPrice = false;
                    lastDisplayedPrice = to;
                }
            }
            
            requestAnimationFrame(animate);
        }

        function performPriceUpdate(price, isAnimating = false) {
            const marketDataId = '{{ @$pair->marketData->id }}';
            
            // Format price with FIXED decimals (always same width)
            const displayPrice = price.toFixed(4);  // Always 4 decimals - no flicker!
            
            // Determine price direction (only when not animating)
            let priceClass = '';
            let priceIcon = '';
            
            if (!isAnimating && lastDisplayedPrice > 0) {
                if (price > lastDisplayedPrice) {
                    priceClass = 'up text--success';
                    priceIcon = '<i class="fas fa-arrow-up"></i>';
                } else if (price < lastDisplayedPrice) {
                    priceClass = 'down text--danger';
                    priceIcon = '<i class="fas fa-arrow-down"></i>';
                }
            }
            
            // Batch all DOM updates in single animation frame
            requestAnimationFrame(() => {
                // Update main price display in header
                const $priceElements = $('.market-price-' + marketDataId);
                
                // Only update if text actually changed
                if ($priceElements.text() !== displayPrice) {
                    $priceElements.text(displayPrice);
                }
                
                if (priceClass) {
                    $priceElements.removeClass('up down text--success text--danger').addClass(priceClass);
                }
                
                // Update order book price (batched)
                const $orderBookPrice = $('.order-book-price-all, .market-price-all');
                $orderBookPrice.each(function() {
                    const $this = $(this);
                    const $span = $this.find('span').first();
                    const target = $span.length ? $span : $this;
                    
                    if (target.text() !== displayPrice) {
                        target.text(displayPrice);
                    }
                    
                    if (priceClass) {
                        $this.removeClass('up down text--success text--danger').addClass(priceClass);
                    }
                });
                
                // Update price icon (smooth transition)
                if (priceIcon) {
                    const $priceIcon = $('.price-icon-' + marketDataId);
                    if ($priceIcon.html() !== priceIcon) {
                        $priceIcon.html(priceIcon);
                        $priceIcon.removeClass('up down text--success text--danger').addClass(priceClass);
                    }
                }
                
                // Update buy/sell form default prices (only if not focused and not animating)
                if (!isAnimating) {
                    $('.buy-rate').each(function() {
                        if (!$(this).is(':focus') && $(this).val() !== displayPrice) {
                            $(this).val(displayPrice);
                        }
                    });
                    $('.sell-rate').each(function() {
                        if (!$(this).is(':focus') && $(this).val() !== displayPrice) {
                            $(this).val(displayPrice);
                        }
                    });
                }
            });
            
            // Only update lastDisplayedPrice when not animating
            if (!isAnimating) {
                lastDisplayedPrice = price;
            }
        }

        function setupSocketIO() {
            try {
                // Use Socket.IO (not plain WebSocket!)
                socket = io(ZYPHER_SOCKET_URL);
                
                socket.on('connect', () => {
                    console.log('✅ Socket.IO connected to Zypher API');
                    socket.emit('subscribe', { symbol: 'ZPHUSD' });
                });

                // Handle LIVE OHLC updates (tick-by-tick - updates every 1 second)
                socket.on('live_ohlc', (data) => {
                    if (data.symbol === 'ZPHUSD') {
                        currentPrice = parseFloat(data.close);
                        updateCandle(currentPrice);
                        updatePriceDisplays(currentPrice);
                        console.log('📊 Live OHLC tick:', currentPrice.toFixed(2), '| High:', parseFloat(data.high).toFixed(2), '| Low:', parseFloat(data.low).toFixed(2), '| (' + (data.timeRemaining || 0) + 's remaining)');
                    }
                });
                
                // Handle price updates (every 1 second - backup)
                socket.on('price_update', (data) => {
                    if (data.symbol === 'ZPHUSD') {
                        currentPrice = parseFloat(data.price);
                        updateCandle(currentPrice);
                        updatePriceDisplays(currentPrice);
                        console.log('💹 Price update:', currentPrice.toFixed(2), data.changePercent ? '(' + data.changePercent.toFixed(2) + '%)' : '');
                    }
                });
                
                // Handle completed candle updates (every 60 seconds)
                socket.on('candle_update', (data) => {
                    if (data.symbol === 'ZPHUSD') {
                        console.log('✅ New candle completed at', parseFloat(data.c || data.close).toFixed(2));
                        // The next live_ohlc will start a new candle automatically
                    }
                });

                socket.on('disconnect', () => {
                    console.log('🔌 Socket.IO disconnected from Zypher API');
                });

                socket.on('error', (error) => {
                    console.error('❌ Socket.IO error:', error);
                });

            } catch (error) {
                console.error('❌ Error setting up Socket.IO:', error);
            }
        }

        // Polling fallback (every 1 second)
        function startPricePolling() {
            setInterval(async () => {
                try {
                    const response = await fetch(`${ZYPHER_API_URL}/tradingview/price`, {
                        cache: 'no-cache'
                    });
                    const data = await response.json();
                    if (data.success && data.data.symbol === 'ZPHUSD') {
                        const newPrice = data.data.price;
                        if (newPrice !== currentPrice) {
                            currentPrice = newPrice;
                            updateCandle(newPrice);
                            updatePriceDisplays(newPrice);
                        }
                    }
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 1000);
        }

        // Add Binance WebSocket for non-ZPH pairs
        function setupBinanceWebSocket() {
            try {
                let binanceSymbol = PAIR_SYMBOL.replace('_', '');
                // Convert USD to USDT for Binance
                if (binanceSymbol.toUpperCase().endsWith('USD') && !binanceSymbol.toUpperCase().endsWith('USDT')) {
                    binanceSymbol = binanceSymbol.replace(/USD$/i, 'USDT');
                }
                
                const wsUrl = `wss://stream.binance.com:9443/ws/${binanceSymbol.toLowerCase()}@kline_1m`;
                
                console.log('📡 Connecting to Binance WebSocket:', wsUrl);
                webSocket = new WebSocket(wsUrl);
                
                webSocket.onopen = () => {
                    console.log('✅ Connected to Binance WebSocket for', binanceSymbol);
                };
                
                webSocket.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    if (data.k) {
                        const candle = data.k;
                        const price = parseFloat(candle.c);
                        updateCandle(price);
                        updatePriceDisplays(price);
                    }
                };
                
                webSocket.onerror = (error) => {
                    console.error('❌ Binance WebSocket error:', error);
                };
                
                webSocket.onclose = () => {
                    console.log('🔌 Binance WebSocket disconnected');
                    // Reconnect after 3 seconds
                    setTimeout(() => {
                        console.log('🔄 Reconnecting to Binance WebSocket...');
                        setupBinanceWebSocket();
                    }, 3000);
                };
            } catch (error) {
                console.error('❌ Error setting up Binance WebSocket:', error);
            }
        }

        function showNoDataMessage() {
            const container = document.getElementById('trading-chart-container');
            container.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #d1d4dc;">
                    <div style="text-align: center;">
                        <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p style="font-size: 16px; margin: 10px 0;">No chart data available</p>
                        <p style="font-size: 14px; opacity: 0.7;">Waiting for market data...</p>
                    </div>
                </div>
            `;
        }

        function showErrorMessage(message) {
            const container = document.getElementById('trading-chart-container');
            container.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #ef5350;">
                    <div style="text-align: center; padding: 20px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px;"></i>
                        <p style="font-size: 16px; margin: 10px 0; font-weight: bold;">Chart Error</p>
                        <p style="font-size: 14px; opacity: 0.9; max-width: 400px;">${message}</p>
                    </div>
                </div>
            `;
        }

        // Initialize chart when page loads
        $(document).ready(function() {
            initChart();
        });

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (socket) {
                socket.disconnect();
            }
            if (webSocket) {
                webSocket.close();
            }
        });

    })(jQuery);
</script>
@endpush
