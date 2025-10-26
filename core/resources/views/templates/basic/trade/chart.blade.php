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
        let oneMinuteBuffer = []; // Buffer for aggregating 1-minute candles
        let lastLoadTime = 0; // Track last load time to prevent rapid successive loads
        let hasMoreData = true; // Track if more data is available

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
                console.log('Initializing chart...', { IS_ZYPHER, PAIR_SYMBOL });
                
                if (typeof LightweightCharts === 'undefined') {
                    console.error('LightweightCharts library not loaded!');
                    return;
                }
                
                const container = document.getElementById('trading-chart-container');
                if (!container) {
                    console.error('Chart container not found!');
                    return;
                }
                
                chart = LightweightCharts.createChart(container, chartOptions);
                console.log('Chart created successfully');

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
                console.log('Candlestick series added');

                loadHistoricalData();
                
                if (IS_ZYPHER) {
                    setupZypherConnection();
                } else {
                    setupBinanceConnection();
                }
                
                // Infinite scroll with safeguards (works for both Zypher and Binance)
                chart.timeScale().subscribeVisibleLogicalRangeChange(() => {
                    const now = Date.now();
                    const logicalRange = chart.timeScale().getVisibleLogicalRange();
                    
                    // Add multiple safeguards to prevent infinite loading:
                    // 1. Check if we're not already loading
                    // 2. Check if more data is available
                    // 3. Ensure at least 1 second has passed since last load
                    // 4. Check if user scrolled to the beginning
                    if (logicalRange !== null && 
                        logicalRange.from < 10 && 
                        !isLoadingMore && 
                        hasMoreData &&
                        (now - lastLoadTime) > 1000) {
                        
                        if (IS_ZYPHER) {
                            loadMoreZypherData();
                        } else {
                            loadMoreBinanceData();
                        }
                    }
                });

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
                // Load INITIAL data - REDUCED for better API compatibility
                let timeRange = 3 * 3600; // Start with only 3 hours for 1m (180 candles) - more reliable
                if (currentInterval === '5m') timeRange = 12 * 3600; // 12 hours for 5m
                else if (currentInterval === '15m') timeRange = 2 * 24 * 3600; // 2 days for 15m
                else if (currentInterval === '1h') timeRange = 5 * 24 * 3600; // 5 days for 1h
                else if (currentInterval === '1d') timeRange = 30 * 24 * 3600; // 30 days for 1d
                
                const fromTimestamp = toTimestamp - timeRange;
                // Always fetch 1-minute data from Zypher (only resolution they support)
                const url = `${ZYPHER_API_URL}/tradingview/history?symbol=ZPHUSD&resolution=1&from=${fromTimestamp}&to=${toTimestamp}`;
                
                console.log('Loading Zypher data:', { 
                    url, 
                    interval: currentInterval,
                    timeRange: timeRange / 3600 + ' hours',
                    from: new Date(fromTimestamp * 1000), 
                    to: new Date(toTimestamp * 1000) 
                });
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    console.error('API returned error status:', response.status, response.statusText);
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Zypher API response:', { 
                    status: data.s, 
                    candleCount: data.t ? data.t.length : 0,
                    hasData: !!data.t,
                    firstCandle: data.t ? new Date(data.t[0] * 1000) : null,
                    lastCandle: data.t ? new Date(data.t[data.t.length - 1] * 1000) : null
                });
                
                if (data.s && data.s === 'ok' && data.t && data.t.length > 0) {
                    const oneMinuteCandles = data.t.map((time, i) => ({
                        time: time,
                        open: parseFloat(data.o[i]),
                        high: parseFloat(data.h[i]),
                        low: parseFloat(data.l[i]),
                        close: parseFloat(data.c[i])
                    })).filter(candle => {
                        // Filter out invalid candles - LightweightCharts requires all values > 0
                        return candle.time > 0 &&
                               candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                               candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                               candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                               candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                    });
                    
                    // Remove duplicate timestamps (Zypher API bug in older data)
                    const uniqueCandles = [];
                    const seenTimes = new Set();
                    for (const candle of oneMinuteCandles) {
                        if (!seenTimes.has(candle.time)) {
                            seenTimes.add(candle.time);
                            uniqueCandles.push(candle);
                        }
                    }
                    const finalCandles = uniqueCandles;
                    
                    if (uniqueCandles.length < oneMinuteCandles.length) {
                        console.warn('⚠️ Removed', oneMinuteCandles.length - uniqueCandles.length, 'duplicate timestamps');
                    }
                    
                    console.log('Parsed 1-minute candles:', finalCandles.length, 'valid candles (filtered from', data.t.length, ')');
                    
                    if (finalCandles.length > 0) {
                        // Aggregate candles if interval is not 1m
                        const candles = currentInterval === '1m' 
                            ? finalCandles 
                            : aggregateCandles(finalCandles, getIntervalSeconds());
                        
                        console.log('Final candles:', {
                            count: candles.length,
                            interval: currentInterval,
                            firstCandle: candles[0],
                            lastCandle: candles[candles.length - 1]
                        });
                        
                        if (candles.length === 0) {
                            console.error('No candles after aggregation!');
                            return;
                        }
                        
                        // FINAL validation before setting chart data
                        const validCandles = candles.filter(candle => {
                            return candle && candle.time > 0 &&
                                   candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                                   candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                                   candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                                   candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                        });
                        
                        if (validCandles.length < candles.length) {
                            console.warn('⚠️ Filtered out', candles.length - validCandles.length, 'invalid candles before chart init');
                        }
                        
                        allCandles = validCandles;
                        oldestTimestamp = validCandles[0].time; // Track oldest for pagination
                        candlestickSeries.setData(validCandles);
                        lastPrice = validCandles[validCandles.length - 1].close;
                        chart.timeScale().fitContent();
                        console.log('✅ Chart data set successfully with', validCandles.length, 'candles');
                    }
                } else if (data.s === 'no_data') {
                    console.warn('⚠️ Zypher API returned no_data');
                } else {
                    console.warn('⚠️ Invalid Zypher response:', data);
                }
            } catch (error) {
                console.error('❌ Zypher error:', error.message, error);
            }
        }
        
        async function loadMoreZypherData() {
            if (isLoadingMore || !oldestTimestamp || !hasMoreData) return;
            
            isLoadingMore = true;
            lastLoadTime = Date.now();
            
            try {
                // Load MORE data in smaller optimized chunks for better performance
                let timeRange = 2 * 3600; // Load only 2 hours at a time for 1m (120 candles)
                if (currentInterval === '5m') timeRange = 6 * 3600; // 6 hours for 5m
                else if (currentInterval === '15m') timeRange = 24 * 3600; // 1 day for 15m
                else if (currentInterval === '1h') timeRange = 3 * 24 * 3600; // 3 days for 1h
                else if (currentInterval === '1d') timeRange = 15 * 24 * 3600; // 15 days for 1d
                
                const toTimestamp = oldestTimestamp - 1;
                const fromTimestamp = toTimestamp - timeRange;
                
                // Always fetch 1-minute data from Zypher
                const url = `${ZYPHER_API_URL}/tradingview/history?symbol=ZPHUSD&resolution=1&from=${fromTimestamp}&to=${toTimestamp}`;
                
                console.log('📥 Loading MORE Zypher data:', { 
                    timeRange: timeRange / 3600 + ' hours',
                    from: new Date(fromTimestamp * 1000), 
                    to: new Date(toTimestamp * 1000) 
                });
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    console.warn('⚠️ Zypher API error:', response.status);
                    hasMoreData = false;
                    return;
                }
                
                const data = await response.json();
                console.log('📥 More data response:', { 
                    status: data.s, 
                    candleCount: data.t ? data.t.length : 0 
                });
                
                if (data.s === 'ok' && data.t && data.t.length > 0) {
                    const oneMinuteCandles = data.t.map((time, i) => ({
                        time: time,
                        open: parseFloat(data.o[i]),
                        high: parseFloat(data.h[i]),
                        low: parseFloat(data.l[i]),
                        close: parseFloat(data.c[i])
                    })).filter(candle => {
                        // Filter out invalid candles - LightweightCharts requires all values > 0
                        return candle.time > 0 &&
                               candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                               candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                               candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                               candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                    });
                    
                    // Remove duplicate timestamps (critical for loadMore!)
                    const uniqueOneMinCandles = [];
                    const seenTimes = new Set();
                    for (const candle of oneMinuteCandles) {
                        if (!seenTimes.has(candle.time)) {
                            seenTimes.add(candle.time);
                            uniqueOneMinCandles.push(candle);
                        }
                    }
                    
                    if (uniqueOneMinCandles.length < oneMinuteCandles.length) {
                        console.warn('⚠️ Removed', oneMinuteCandles.length - uniqueOneMinCandles.length, 'duplicate timestamps from loadMore');
                    }
                    
                    console.log('📥 Filtered', uniqueOneMinCandles.length, 'valid candles from', data.t.length, 'total');
                    
                    if (uniqueOneMinCandles.length > 0) {
                        // Aggregate candles if interval is not 1m
                        const newCandles = currentInterval === '1m' 
                            ? uniqueOneMinCandles 
                            : aggregateCandles(uniqueOneMinCandles, getIntervalSeconds());
                        
                        console.log('✅ Loaded', newCandles.length, 'more candles');
                        
                        // If we got very few candles, we might be near the end
                        if (newCandles.length < 5) {
                            hasMoreData = false;
                            console.log('⚠️ Reached end of data (< 5 candles)');
                        }
                        
                        // Merge arrays
                        const mergedCandles = [...newCandles, ...allCandles];
                        
                        // Remove duplicates from merged array (can happen at boundaries)
                        const uniqueMerged = [];
                        const seenMergedTimes = new Set();
                        for (const candle of mergedCandles) {
                            if (!seenMergedTimes.has(candle.time)) {
                                seenMergedTimes.add(candle.time);
                                uniqueMerged.push(candle);
                            }
                        }
                        
                        if (uniqueMerged.length < mergedCandles.length) {
                            console.warn('⚠️ Removed', mergedCandles.length - uniqueMerged.length, 'duplicate timestamps from merged array');
                        }
                        
                        // FINAL validation - remove ANY invalid candles
                        const validCandles = uniqueMerged.filter(candle => {
                            return candle && candle.time > 0 &&
                                   candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                                   candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                                   candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                                   candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                        });
                        
                        if (validCandles.length < uniqueMerged.length) {
                            console.warn('⚠️ Removed', uniqueMerged.length - validCandles.length, 'invalid candles from final array');
                        }
                        
                        allCandles = validCandles;
                        oldestTimestamp = validCandles[0].time;
                        candlestickSeries.setData(validCandles);
                        console.log('📊 Total candles now:', validCandles.length);
                    } else {
                        hasMoreData = false;
                        console.log('⚠️ No valid candles after filtering');
                    }
                } else if (data.s === 'no_data') {
                    // No more historical data available
                    hasMoreData = false;
                    console.log('⚠️ API says no_data');
                } else {
                    // Invalid response
                    hasMoreData = false;
                    console.log('⚠️ Invalid API response');
                }
            } catch (error) {
                console.error('❌ Error loading more Zypher data:', error);
                hasMoreData = false; // Stop trying on error
            } finally {
                isLoadingMore = false;
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
                    })).filter(candle => {
                        // Validate Binance candles
                        return candle && candle.time > 0 &&
                               candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                               candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                               candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                               candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                    });
                    
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
            if (isLoadingMore || !oldestTimestamp || !hasMoreData) return;
            
            isLoadingMore = true;
            lastLoadTime = Date.now();
            
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
                    })).filter(candle => {
                        // Validate Binance candles
                        return candle && candle.time > 0 &&
                               candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                               candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                               candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                               candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                    });
                    
                    // If we got less than requested, we've reached the end
                    if (newCandles.length < 500) {
                        hasMoreData = false;
                    }
                    
                    allCandles = [...newCandles, ...allCandles];
                    oldestTimestamp = newCandles[0].time;
                    
                    // Final validation of merged array
                    const validAllCandles = allCandles.filter(candle => {
                        return candle && candle.time > 0 &&
                               candle.open > 0 && !isNaN(candle.open) && isFinite(candle.open) &&
                               candle.high > 0 && !isNaN(candle.high) && isFinite(candle.high) &&
                               candle.low > 0 && !isNaN(candle.low) && isFinite(candle.low) &&
                               candle.close > 0 && !isNaN(candle.close) && isFinite(candle.close);
                    });
                    
                    candlestickSeries.setData(validAllCandles);
                    allCandles = validAllCandles;
                } else {
                    // No more data available
                    hasMoreData = false;
                }
            } catch (error) {
                console.error('Error loading more data:', error);
                hasMoreData = false; // Stop trying on error
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
                    // Validate incoming real-time data before using it
                    const close = parseFloat(data.close);
                    const open = parseFloat(data.open);
                    const high = parseFloat(data.high);
                    const low = parseFloat(data.low);
                    
                    // Skip invalid real-time updates
                    if (isNaN(close) || !isFinite(close) || close <= 0 ||
                        isNaN(open) || !isFinite(open) || open <= 0 ||
                        isNaN(high) || !isFinite(high) || high <= 0 ||
                        isNaN(low) || !isFinite(low) || low <= 0) {
                        console.warn('⚠️ Skipping invalid live_ohlc data:', data);
                        return;
                    }
                    
                    // Update the chart with validated data
                    updateCandleFromOHLC(data);
                    updatePriceDisplays(close);
                }
            });
            
            socket.on('price_update', (data) => {
                if (data.symbol === 'ZPHUSD') {
                    const price = parseFloat(data.price);
                    
                    // Skip invalid price updates
                    if (isNaN(price) || !isFinite(price) || price <= 0) {
                        console.warn('⚠️ Skipping invalid price_update:', data);
                        return;
                    }
                    
                    // Update candle with validated price
                    updateCandle(price);
                    updatePriceDisplays(price);
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
            
            // Validate all OHLC values - must be positive numbers
            const close = parseFloat(data.close);
            const open = parseFloat(data.open);
            const high = parseFloat(data.high);
            const low = parseFloat(data.low);
            
            // Strict validation - reject ANY invalid values
            if (!close || !open || !high || !low ||
                isNaN(close) || !isFinite(close) || close <= 0 ||
                isNaN(open) || !isFinite(open) || open <= 0 ||
                isNaN(high) || !isFinite(high) || high <= 0 ||
                isNaN(low) || !isFinite(low) || low <= 0 ||
                close === null || open === null || high === null || low === null) {
                console.warn('⚠️ Invalid OHLC data rejected:', { open, high, low, close, raw: data });
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
            
            // Triple-check all values before updating chart
            if (currentCandle && 
                currentCandle.time > 0 &&
                currentCandle.open > 0 && !isNaN(currentCandle.open) && isFinite(currentCandle.open) &&
                currentCandle.high > 0 && !isNaN(currentCandle.high) && isFinite(currentCandle.high) &&
                currentCandle.low > 0 && !isNaN(currentCandle.low) && isFinite(currentCandle.low) &&
                currentCandle.close > 0 && !isNaN(currentCandle.close) && isFinite(currentCandle.close)) {
                try {
                    candlestickSeries.update(currentCandle);
                } catch (error) {
                    console.error('❌ Chart update failed (OHLC):', error.message, currentCandle);
                }
            } else {
                console.warn('⚠️ Invalid currentCandle rejected (OHLC):', currentCandle);
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
            
            // Triple-check all values before updating chart
            if (currentCandle && 
                currentCandle.time > 0 &&
                currentCandle.open > 0 && !isNaN(currentCandle.open) && isFinite(currentCandle.open) &&
                currentCandle.high > 0 && !isNaN(currentCandle.high) && isFinite(currentCandle.high) &&
                currentCandle.low > 0 && !isNaN(currentCandle.low) && isFinite(currentCandle.low) &&
                currentCandle.close > 0 && !isNaN(currentCandle.close) && isFinite(currentCandle.close)) {
                try {
                    candlestickSeries.update(currentCandle);
                } catch (error) {
                    console.error('❌ Chart update failed (price):', error.message, currentCandle);
                }
            } else {
                console.warn('⚠️ Invalid currentCandle rejected (price):', currentCandle);
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
            
            // Reset candle and loading states
            currentCandle = null;
            hasMoreData = true; // Reset for new interval
            lastLoadTime = 0;
            oldestTimestamp = null;
            
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
