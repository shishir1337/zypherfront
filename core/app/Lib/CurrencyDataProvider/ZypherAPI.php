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
            'base_url' => $this->provider->configuration->base_url->value ?? 'http://localhost:3001/api'
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

            // Update currency price
            $zypherCurrency->rate = $priceData['price'];
            $zypherCurrency->last_update = time();
            $zypherCurrency->save();

            // Update market data for both currency and pair
            // First update currency market data
            $marketData = $zypherCurrency->marketData;
            
            // Also find and update PAIR market data (ZPH_USD pair)
            $zphPair = \App\Models\CoinPair::where('coin_id', $zypherCurrency->id)->first();
            $pairMarketData = $zphPair ? $zphPair->marketData : null;
            
            if ($marketData) {
                // Store previous values
                $oldPrice = $marketData->price;
                $newPrice = $priceData['price'];
                
                // Calculate price change percentage
                $priceChange = 0;
                if ($oldPrice > 0) {
                    $priceChange = (($newPrice - $oldPrice) / $oldPrice) * 100;
                }
                
                // Update last values before changing current
                $marketData->last_price = $oldPrice;
                $marketData->last_percent_change_1h = $marketData->percent_change_1h;
                $marketData->last_percent_change_24h = $marketData->percent_change_24h;
                
                // Update current values
                $marketData->price = $newPrice;
                $marketData->percent_change_1h = $priceChange; // Real-time change
                $marketData->percent_change_24h = $priceChange; // Will be more accurate with historical data
                
                // Update HTML classes for up/down indicators
                $htmlClasses = [
                    'price_change' => $newPrice >= $oldPrice ? 'up text--success' : 'down text--danger',
                    'percent_change_1h' => $priceChange >= 0 ? 'text--success' : 'text--danger',
                    'percent_change_24h' => $priceChange >= 0 ? 'text--success' : 'text--danger',
                ];
                $marketData->html_classes = $htmlClasses;
                
                // You can add market cap calculation if needed
                // For now, set a placeholder or calculate based on supply
                if ($marketData->market_cap == 0) {
                    $marketData->market_cap = $newPrice * 1000000; // Example: 1M ZPH supply
                }
                
                $marketData->save();
            } else {
                // Create market data if doesn't exist
                $marketData = MarketData::create([
                    'currency_id' => $zypherCurrency->id,
                    'symbol' => 'ZPH',
                    'price' => $priceData['price'],
                    'last_price' => $priceData['price'],
                    'percent_change_1h' => 0,
                    'percent_change_24h' => 0,
                    'market_cap' => $priceData['price'] * 1000000,
                    'pair_id' => 0,
                    'html_classes' => [
                        'price_change' => 'text--base',
                        'percent_change_1h' => 'text--base',
                        'percent_change_24h' => 'text--base',
                    ]
                ]);
            }
            
            // IMPORTANT: Also update PAIR market data (what shows on trade page!)
            if ($pairMarketData) {
                $oldPairPrice = $pairMarketData->price;
                $newPairPrice = $priceData['price'];
                
                // Calculate change
                $pairPriceChange = 0;
                if ($oldPairPrice > 0) {
                    $pairPriceChange = (($newPairPrice - $oldPairPrice) / $oldPairPrice) * 100;
                }
                
                $pairMarketData->last_price = $oldPairPrice;
                $pairMarketData->price = $newPairPrice;
                $pairMarketData->percent_change_1h = $pairPriceChange;
                $pairMarketData->percent_change_24h = $pairPriceChange;
                $pairMarketData->market_cap = $newPairPrice * 1000000; // 1M supply example
                $pairMarketData->html_classes = [
                    'price_change' => $newPairPrice >= $oldPairPrice ? 'up text--success' : 'down text--danger',
                    'percent_change_1h' => $pairPriceChange >= 0 ? 'text--success' : 'text--danger',
                    'percent_change_24h' => $pairPriceChange >= 0 ? 'text--success' : 'text--danger',
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
}

