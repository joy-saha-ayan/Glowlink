<?php
/**
 * GlowLink Price Scraper + Cache System
 * ─────────────────────────────────────
 * Searches Daraz BD, Shajgoj, Chaldal, Othoba for a product name
 * and caches the result in `price_cache` table for 24 hours.
 *
 * HOW TO USE:
 *   include 'price_scraper.php';
 *   $prices = GlowPriceScraper::getPrices("COSRX Snail 96 Mucin 100ml");
 *   // returns array: ['Daraz'=>1850, 'Shajgoj'=>1900, 'Chaldal'=>0, 'Othoba'=>1780]
 *   // 0 means not found on that site
 *
 * SETUP (run once):
 *   GlowPriceScraper::createCacheTable($conn);
 */

class GlowPriceScraper {

    // ── Cache duration in seconds (24 hours) ───────────────────────────────
    const CACHE_TTL = 86400;

    // ── Retailer search URL templates ──────────────────────────────────────
    // {q} will be replaced with urlencode(product name)
    private static $retailers = [
        'Daraz'   => [
            'search_url' => 'https://www.daraz.com.bd/catalog/?q={q}',
            'color'      => '#f85606',
            'icon'       => '🛒',
        ],
        'Shajgoj' => [
            'search_url' => 'https://shajgoj.com/?s={q}',
            'color'      => '#e91e8c',
            'icon'       => '💄',
        ],
        'Chaldal' => [
            'search_url' => 'https://chaldal.com/search/{q}',
            'color'      => '#00a650',
            'icon'       => '🌿',
        ],
        'Othoba'  => [
            'search_url' => 'https://www.othoba.com/search?keyword={q}',
            'color'      => '#2563eb',
            'icon'       => '🏪',
        ],
    ];

    // ── Create cache table ─────────────────────────────────────────────────
    public static function createCacheTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS `price_cache` (
            `id`            INT AUTO_INCREMENT PRIMARY KEY,
            `product_name`  VARCHAR(500) NOT NULL,
            `retailer`      VARCHAR(100) NOT NULL,
            `price`         DECIMAL(10,2) DEFAULT 0,
            `product_url`   VARCHAR(1000) DEFAULT '',
            `found_title`   VARCHAR(500)  DEFAULT '',
            `cached_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_product_retailer` (`product_name`(200), `retailer`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->query($sql);
    }

    // ── Main entry point ───────────────────────────────────────────────────
    public static function getPrices($product_name, $conn) {
        self::createCacheTable($conn);

        $result = [];

        foreach (self::$retailers as $name => $cfg) {
            $cached = self::getCache($conn, $product_name, $name);
            if ($cached !== null) {
                $result[$name] = $cached;
            } else {
                $scraped = self::scrape($name, $product_name, $cfg['search_url']);
                self::setCache($conn, $product_name, $name, $scraped['price'], $scraped['url'], $scraped['title']);
                $result[$name] = $scraped;
            }
        }

        return $result;
    }

    // ── Get from cache ─────────────────────────────────────────────────────
    private static function getCache($conn, $product_name, $retailer) {
        $pn = $conn->real_escape_string($product_name);
        $rt = $conn->real_escape_string($retailer);
        $ttl = self::CACHE_TTL;

        $sql = "SELECT price, product_url, found_title, cached_at
                FROM price_cache
                WHERE product_name = '$pn'
                  AND retailer = '$rt'
                  AND cached_at > NOW() - INTERVAL $ttl SECOND
                LIMIT 1";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return [
                'price'      => floatval($row['price']),
                'url'        => $row['product_url'],
                'title'      => $row['found_title'],
                'from_cache' => true,
            ];
        }
        return null;
    }

    // ── Save to cache ──────────────────────────────────────────────────────
    private static function setCache($conn, $product_name, $retailer, $price, $url, $title) {
        $pn = $conn->real_escape_string($product_name);
        $rt = $conn->real_escape_string($retailer);
        $pr = floatval($price);
        $ur = $conn->real_escape_string($url);
        $ti = $conn->real_escape_string(mb_substr($title, 0, 490));

        $sql = "INSERT INTO price_cache (product_name, retailer, price, product_url, found_title, cached_at)
                VALUES ('$pn', '$rt', $pr, '$ur', '$ti', NOW())
                ON DUPLICATE KEY UPDATE
                    price        = $pr,
                    product_url  = '$ur',
                    found_title  = '$ti',
                    cached_at    = NOW()";
        $conn->query($sql);
    }

    // ── Scraper dispatcher ─────────────────────────────────────────────────
    private static function scrape($retailer, $product_name, $search_url_template) {
        $q   = urlencode($product_name);
        $url = str_replace('{q}', $q, $search_url_template);
        $html = self::fetchHTML($url);

        if (!$html) return ['price'=>0,'url'=>'','title'=>''];

        switch ($retailer) {
            case 'Daraz':   return self::parseDaraz($html, $url);
            case 'Shajgoj': return self::parseShajgoj($html, $url);
            case 'Chaldal': return self::parseChaldal($html, $url);
            case 'Othoba':  return self::parseOthoba($html, $url);
        }
        return ['price'=>0,'url'=>'','title'=>''];
    }

    // ── HTTP Fetch ─────────────────────────────────────────────────────────
    private static function fetchHTML($url) {
        if (!function_exists('curl_init')) return false;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
            CURLOPT_ENCODING       => '', // auto decompress gzip
        ]);

        $html = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || !$html) return false;
        return $html;
    }

    // ── Extract first number from regex match ──────────────────────────────
    private static function extractPrice($text) {
        // Remove commas, extract digits
        $text = str_replace([',', '৳', 'BDT', 'Tk', 'tk'], '', $text);
        preg_match('/(\d{2,6}(?:\.\d{1,2})?)/', $text, $m);
        return isset($m[1]) ? floatval($m[1]) : 0;
    }

    // ─────────────────────────────────────────────────────────────────────
    // DARAZ BD Parser
    // Daraz renders product listings with class="currency--GShUP" or data-price
    // ─────────────────────────────────────────────────────────────────────
    private static function parseDaraz($html, $base_url) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        // Try: data-price attribute on product boxes
        $nodes = $xpath->query('//*[@data-price]');
        if ($nodes && $nodes->length > 0) {
            $price = floatval($nodes->item(0)->getAttribute('data-price'));
            if ($price > 0) {
                $link = $xpath->query('//a[contains(@class,"item-title") or contains(@class,"product-card")]');
                $url  = ($link && $link->length > 0) ? 'https://www.daraz.com.bd' . $link->item(0)->getAttribute('href') : '';
                $title = ($link && $link->length > 0) ? trim($link->item(0)->textContent) : '';
                return ['price'=>$price, 'url'=>$url, 'title'=>$title];
            }
        }

        // Try: span with class containing "currency" or "price"
        $priceNodes = $xpath->query('//*[contains(@class,"currency") or contains(@class,"price")]');
        foreach ($priceNodes as $node) {
            $text  = trim($node->textContent);
            $price = self::extractPrice($text);
            if ($price >= 50 && $price <= 99999) {
                return ['price'=>$price, 'url'=>$base_url, 'title'=>''];
            }
        }

        return ['price'=>0, 'url'=>$base_url, 'title'=>''];
    }

    // ─────────────────────────────────────────────────────────────────────
    // SHAJGOJ Parser
    // WooCommerce-based: .woocommerce-Price-amount bdi
    // ─────────────────────────────────────────────────────────────────────
    private static function parseShajgoj($html, $base_url) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        // WooCommerce price amount
        $nodes = $xpath->query('//bdi[ancestor::*[contains(@class,"woocommerce-Price-amount")]]');
        if (!$nodes || $nodes->length === 0) {
            $nodes = $xpath->query('//*[contains(@class,"woocommerce-Price-amount")]');
        }

        foreach ($nodes as $node) {
            $price = self::extractPrice($node->textContent);
            if ($price >= 50 && $price <= 99999) {
                // Get product link
                $links = $xpath->query('//a[contains(@class,"woocommerce-LoopProduct-link")]');
                $url   = ($links && $links->length > 0) ? $links->item(0)->getAttribute('href') : $base_url;
                $titles = $xpath->query('//h2[contains(@class,"woocommerce-loop-product__title")]');
                $title  = ($titles && $titles->length > 0) ? trim($titles->item(0)->textContent) : '';
                return ['price'=>$price, 'url'=>$url, 'title'=>$title];
            }
        }

        return ['price'=>0, 'url'=>$base_url, 'title'=>''];
    }

    // ─────────────────────────────────────────────────────────────────────
    // CHALDAL Parser
    // React app — prices in JSON inside <script id="__NEXT_DATA__">
    // ─────────────────────────────────────────────────────────────────────
    private static function parseChaldal($html, $base_url) {
        // Try NEXT_DATA JSON
        if (preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            $json = json_decode($m[1], true);
            // Navigate to products array
            $products = self::deepFind($json, 'products') ?? self::deepFind($json, 'items') ?? [];
            if (!empty($products)) {
                $first = is_array(reset($products)) ? reset($products) : $products;
                $price = floatval($first['price'] ?? $first['Price'] ?? $first['discountedPrice'] ?? 0);
                $title = $first['name'] ?? $first['Name'] ?? '';
                $slug  = $first['slug'] ?? $first['Slug'] ?? '';
                $url   = $slug ? 'https://chaldal.com/' . $slug : $base_url;
                if ($price > 0) return ['price'=>$price, 'url'=>$url, 'title'=>$title];
            }
        }

        // Fallback: regex price patterns in HTML
        if (preg_match_all('/"price"\s*:\s*(\d+(?:\.\d+)?)/', $html, $m)) {
            foreach ($m[1] as $p) {
                $price = floatval($p);
                if ($price >= 50 && $price <= 99999) {
                    return ['price'=>$price, 'url'=>$base_url, 'title'=>''];
                }
            }
        }

        return ['price'=>0, 'url'=>$base_url, 'title'=>''];
    }

    // ─────────────────────────────────────────────────────────────────────
    // OTHOBA Parser
    // Standard HTML with class="price" or "regular-price"
    // ─────────────────────────────────────────────────────────────────────
    private static function parseOthoba($html, $base_url) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        $nodes = $xpath->query(
            '//*[contains(@class,"price") or contains(@class,"Price") or contains(@class,"product-price")]'
        );

        foreach ($nodes as $node) {
            $text  = trim($node->textContent);
            $price = self::extractPrice($text);
            if ($price >= 50 && $price <= 99999) {
                $links  = $xpath->query('//a[contains(@class,"product-item-link") or contains(@class,"product-name")]');
                $url    = ($links && $links->length > 0) ? $links->item(0)->getAttribute('href') : $base_url;
                $titles = $xpath->query('//strong[contains(@class,"product-item-name")]//a');
                $title  = ($titles && $titles->length > 0) ? trim($titles->item(0)->textContent) : '';
                return ['price'=>$price, 'url'=>$url, 'title'=>$title];
            }
        }

        return ['price'=>0, 'url'=>$base_url, 'title'=>''];
    }

    // ── Deep array search helper ───────────────────────────────────────────
    private static function deepFind($array, $key, $depth = 0) {
        if (!is_array($array) || $depth > 6) return null;
        if (isset($array[$key])) return $array[$key];
        foreach ($array as $v) {
            $found = self::deepFind($v, $key, $depth + 1);
            if ($found !== null) return $found;
        }
        return null;
    }

    // ── Get retailer config (for UI) ───────────────────────────────────────
    public static function getRetailerConfig() {
        return self::$retailers;
    }

    // ── Force refresh cache for one product ───────────────────────────────
    public static function refreshPrices($product_name, $conn) {
        $pn  = $conn->real_escape_string($product_name);
        $conn->query("DELETE FROM price_cache WHERE product_name = '$pn'");
        return self::getPrices($product_name, $conn);
    }

    // ── Admin: clear all expired cache ────────────────────────────────────
    public static function pruneCache($conn) {
        $ttl = self::CACHE_TTL;
        $conn->query("DELETE FROM price_cache WHERE cached_at < NOW() - INTERVAL $ttl SECOND");
    }
}
