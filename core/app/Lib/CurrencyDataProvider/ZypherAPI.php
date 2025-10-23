<?php

namespace App\Lib\CurrencyDataProvider;

use App\Constants\Status;
use App\Lib\CurlRequest;
use App\Models\Currency;
use App\Models\MarketData;
use Exception;

class ZypherAPI extends CurrencyDataProvider
{
    /*
    |--------------------------------------------------------------------------
    | ZypherAPI
    |--------------------------------------------------------------------------
    |
    | This class extends the `CurrencyDataProvider` class and serves as a data provider for
    | retrieving cryptocurrency data from the Zypher Trading API. It implements the necessary
    | methods to fetch cryptocurrency prices and update market data specific to Zypher.
    |
    */

    protected $apiBaseUrl;

    /**
     * Configure the Zypher API settings
     *
     * @return array
     */
    protected function configuration()
    {
        return [
            'base_url' => $this->provider->configuration->base_url->value ?? env('ZYPHER_API_URL', 'https://zypher.bigbuller.com/api')
        ];
    }

    /**
     * Update cryptocurrency prices from Zypher API
     *
     * @return void
     * @throws Exception if there is an error with the API call or data processing.
     */
    public function updateCryptoPrice()
    {
        $config = $this->configuration();
        $this->apiBaseUrl = $config['base_url'];

        // Get Zypher currency from database
        $zypherCurrency = Currency::where('symbol', 'ZPH')->first();
        
        if (!$zypherCurrency) {
            $this->setException('Zypher (ZPH) currency not found in database');
        }

        try {
            // Fetch current price from Zypher API
            $priceData = $this->getCurrentPrice();
            
            if (!$priceData) {
                $this->setException('Failed to fetch price from Zypher API');
            }

            $currentPrice = $priceData['price'];
            
            // Calculate 1H and 24H changes using historical data
            $oneHourChange = $this->calculatePriceChange(3600); // 1 hour in seconds
            $twentyFourHourChange = $this->calculatePriceChange(86400); // 24 hours in seconds

            // Update currency price
            $zypherCurrency->rate = $currentPrice;
            $zypherCurrency->last_update = time();
            $zypherCurrency->save();

            // Update market data for both currency and pair
            $marketData = $zypherCurrency->marketData;
            
            // Also find and update PAIR market data (ZPH_USD pair)
            $zphPair = \App\Models\CoinPair::where('coin_id', $zypherCurrency->id)->first();
            $pairMarketData = $zphPair ? $zphPair->marketData : null;
            
            if ($marketData) {
                // Store previous values
                $oldPrice = $marketData->price;
                $newPrice = $currentPrice;
                
                // Calculate real-time change (price difference from last price)
                $priceChange = 0;
                if ($oldPrice > 0) {
                    $priceChange = (($newPrice - $oldPrice) / $oldPrice) * 100;
                }
                
                // Update last values before changing current
                $marketData->last_price = $oldPrice;
                $marketData->last_percent_change_1h = $marketData->percent_change_1h;
                $marketData->last_percent_change_24h = $marketData->percent_change_24h;
                
                // Update current values with historical calculations
                $marketData->price = $newPrice;
                $marketData->percent_change_1h = $oneHourChange; // Accurate 1H change
                $marketData->percent_change_24h = $twentyFourHourChange; // Accurate 24H change
                
                // Update HTML classes for up/down indicators
                $htmlClasses = [
                    'price_change' => $newPrice >= $oldPrice ? 'up text--success' : 'down text--danger',
                    'percent_change_1h' => $oneHourChange >= 0 ? 'text--success' : 'text--danger',
                    'percent_change_24h' => $twentyFourHourChange >= 0 ? 'text--success' : 'text--danger',
                ];
                $marketData->html_classes = $htmlClasses;
                
                // Calculate marketcap more accurately
                // Try to get supply info if available, otherwise use conservative estimate
                $marketcap = $this->calculateMarketCap($newPrice);
                $marketData->market_cap = $marketcap;
                
                $marketData->save();
            } else {
                // Create market data if doesn't exist
                $marketcap = $this->calculateMarketCap($currentPrice);
                
                $marketData = MarketData::create([
                    'currency_id' => $zypherCurrency->id,
                    'symbol' => 'ZPH',
                    'price' => $currentPrice,
                    'last_price' => $currentPrice,
                    'percent_change_1h' => $oneHourChange,
                    'percent_change_24h' => $twentyFourHourChange,
                    'market_cap' => $marketcap,
                    'pair_id' => 0,
                    'html_classes' => [
                        'price_change' => 'text--base',
                        'percent_change_1h' => $oneHourChange >= 0 ? 'text--success' : 'text--danger',
                        'percent_change_24h' => $twentyFourHourChange >= 0 ? 'text--success' : 'text--danger',
                    ]
                ]);
            }
            
            // IMPORTANT: Also update PAIR market data (what shows on trade page!)
            if ($pairMarketData) {
                $oldPairPrice = $pairMarketData->price;
                $newPairPrice = $currentPrice;
                
                $pairMarketData->last_price = $oldPairPrice;
                $pairMarketData->price = $newPairPrice;
                $pairMarketData->percent_change_1h = $oneHourChange;
                $pairMarketData->percent_change_24h = $twentyFourHourChange;
                $marketcap = $this->calculateMarketCap($newPairPrice);
                $pairMarketData->market_cap = $marketcap;
                $pairMarketData->html_classes = [
                    'price_change' => $newPairPrice >= $oldPairPrice ? 'up text--success' : 'down text--danger',
                    'percent_change_1h' => $oneHourChange >= 0 ? 'text--success' : 'text--danger',
                    'percent_change_24h' => $twentyFourHourChange >= 0 ? 'text--success' : 'text--danger',
                ];
                $pairMarketData->save();
            }

        } catch (Exception $ex) {
            $this->setException($ex->getMessage());
        }
    }

    /**
     * Get current price from Zypher API
     *
     * @return array|null
     */
    protected function getCurrentPrice()
    {
        $url = $this->apiBaseUrl . '/tradingview/price';
        $response = CurlRequest::curlContent($url);
        $data = json_decode($response, true);

        if (isset($data['success']) && $data['success'] && isset($data['data']['price'])) {
            return [
                'price' => $data['data']['price'],
                'timestamp' => $data['data']['timestamp'] ?? time() * 1000
            ];
        }

        return null;
    }

    /**
     * Make an API call to the Zypher API
     *
     * @param array $parameters
     * @param mixed $configuration
     * @param string $endPoint
     * @return mixed
     */
    public function apiCall($parameters = null, $configuration = null, $endPoint = "tradingview/price")
    {
        if (!$configuration) {
            $configuration = $this->configuration();
        }

        $url = $configuration['base_url'] . '/' . $endPoint;
        $qs = $parameters ? '?' . http_build_query($parameters) : "";
        
        $response = CurlRequest::curlContent("{$url}{$qs}");
        return json_decode($response);
    }

    /**
     * Update the market data (not used for Zypher as it's a single currency)
     *
     * @param \App\Models\MarketData $systemMarketData
     * @param mixed $providerMarketData
     * @param string $convertTo
     * @return string
     */
    protected function updateMarketData($systemMarketData, $providerMarketData, $convertTo)
    {
        // Zypher is a standalone currency, no market pairs to update
        return 'ZPHUSD';
    }

    /**
     * Import currencies (not applicable for Zypher single currency)
     *
     * @param array $parameters
     * @param int $type
     * @return int
     */
    public function import($parameters, $type)
    {
        // Check if ZPH already exists
        $exists = Currency::where('symbol', 'ZPH')->exists();
        
        if ($exists) {
            return 0; // Already imported
        }

        // Get current price from API
        $priceData = $this->getCurrentPrice();
        $price = $priceData ? $priceData['price'] : 10.00;

        // Insert Zypher currency
        $currency = Currency::create([
            'type' => Status::CRYPTO_CURRENCY,
            'name' => 'Zypher',
            'symbol' => 'ZPH',
            'sign' => '$',
            'ranking' => 999,
            'rate' => $price,
        ]);

        // Create market data
        MarketData::create([
            'currency_id' => $currency->id,
            'symbol' => 'ZPH',
            'price' => $price,
            'pair_id' => 0,
        ]);

        return 1;
    }

    /**
     * Calculate price change over a time period using historical candle data
     *
     * @param int $secondsAgo Number of seconds to look back
     * @return float Percentage change
     */
    protected function calculatePriceChange($secondsAgo)
    {
        try {
            $currentTime = time();
            $pastTime = $currentTime - $secondsAgo;
            
            // Fetch current price
            $priceData = $this->getCurrentPrice();
            $currentPrice = $priceData ? floatval($priceData['price']) : 0;
            
            if ($currentPrice <= 0) {
                \Log::warning('Invalid current price for Zypher: ' . $currentPrice);
                return 0;
            }
            
            // Fetch 1-minute candle data from past time period
            $url = $this->apiBaseUrl . '/tradingview/history?symbol=ZPHUSD&resolution=1&from=' . $pastTime . '&to=' . $currentTime;
            \Log::info('Fetching Zypher history: ' . $url);
            
            $response = CurlRequest::curlContent($url);
            $data = json_decode($response, true);
            
            \Log::info('Zypher history response status: ' . ($data['s'] ?? 'unknown'));
            
            if (isset($data['s']) && $data['s'] === 'ok' && isset($data['o']) && isset($data['c'])) {
                $candleCount = count($data['o']);
                \Log::info('Zypher candles received: ' . $candleCount);
                
                if ($candleCount > 0) {
                    // Get oldest candle opening price and newest closing price
                    $oldPrice = floatval($data['o'][0]); // Opening price from oldest candle
                    $newPrice = floatval($data['c'][$candleCount - 1]); // Closing price from newest candle
                    
                    \Log::info("Zypher price change: Old=$oldPrice, New=$newPrice, Current=$currentPrice");
                    
                    // Validate prices make sense
                    if ($oldPrice > 0 && $newPrice > 0) {
                        // Check if old price is unreasonably far from current price (suspicious data)
                        $priceDiffPercent = abs(($currentPrice - $oldPrice) / $oldPrice) * 100;
                        
                        if ($priceDiffPercent > 50) {
                            // Price movement > 50% in this period is suspicious
                            \Log::warning("Suspicious price movement detected: $priceDiffPercent%, oldPrice=$oldPrice, newPrice=$newPrice");
                            // Use current price as reference instead
                            $oldPrice = $currentPrice;
                        }
                        
                        $change = (($newPrice - $oldPrice) / $oldPrice) * 100;
                        
                        // Safety check: if change is still unreasonable (>500%), use fallback
                        if (abs($change) > 500) {
                            \Log::warning("Unreasonable price change detected: $change%, using fallback");
                            return $this->calculatePriceChangeFallback($secondsAgo);
                        }
                        
                        \Log::info("Zypher $secondsAgo sec change: " . $change . '%');
                        return $change;
                    }
                }
            } else {
                \Log::warning('Invalid Zypher history response: ' . json_encode($data));
            }
            
            // Fallback to database last price
            return $this->calculatePriceChangeFallback($secondsAgo);
            
        } catch (Exception $ex) {
            \Log::error('Error calculating price change: ' . $ex->getMessage());
            return $this->calculatePriceChangeFallback($secondsAgo);
        }
    }

    /**
     * Calculate price change using stored database values (fallback method)
     *
     * @param int $secondsAgo Number of seconds to look back
     * @return float Percentage change
     */
    protected function calculatePriceChangeFallback($secondsAgo)
    {
        try {
            $zypherCurrency = Currency::where('symbol', 'ZPH')->first();
            
            if (!$zypherCurrency) {
                return 0;
            }
            
            $marketData = $zypherCurrency->marketData;
            
            if (!$marketData) {
                return 0;
            }
            
            $currentPrice = floatval($marketData->price);
            $lastPrice = floatval($marketData->last_price);
            
            // Use appropriate historical field based on time period
            if ($secondsAgo <= 3600) {
                // For 1H, use last_percent_change_1h if available
                $storedChange = floatval($marketData->last_percent_change_1h ?? 0);
            } else {
                // For 24H, use last_percent_change_24h if available
                $storedChange = floatval($marketData->last_percent_change_24h ?? 0);
            }
            
            // If we have a stored previous change, average it with current change
            if ($lastPrice > 0 && $currentPrice > 0) {
                $currentChange = (($currentPrice - $lastPrice) / $lastPrice) * 100;
                // Return stored change (more reliable) or current change if no stored value
                return $storedChange !== 0 ? $storedChange : $currentChange;
            }
            
            return 0;
        } catch (Exception $ex) {
            \Log::error('Error in fallback price calculation: ' . $ex->getMessage());
            return 0;
        }
    }

    /**
     * Calculate marketcap for ZPH
     *
     * @param float $price Current price
     * @return float Calculated marketcap
     */
    protected function calculateMarketCap($price)
    {
        try {
            // Try to fetch supply info from Zypher API if available
            $url = $this->apiBaseUrl . '/supply';
            $response = CurlRequest::curlContent($url);
            $data = json_decode($response, true);
            
            if (isset($data['success']) && $data['success'] && isset($data['data']['circulating_supply'])) {
                $supply = floatval($data['data']['circulating_supply']);
                return $price * $supply;
            }
        } catch (Exception $ex) {
            \Log::warning('Could not fetch ZPH supply info: ' . $ex->getMessage());
        }
        
        // Fallback: Use a reasonable estimate
        // Assuming 10 million ZPH in circulation (adjust as needed)
        $defaultSupply = 10000000;
        return $price * $defaultSupply;
    }
}

