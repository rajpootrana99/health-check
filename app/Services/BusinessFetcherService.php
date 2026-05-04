<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusinessFetcherService
{
    private string $apiKey;
    private string $baseUrl;

    // Fields requested from Place Details — keep lean to stay within quota
    private const DETAIL_FIELDS = [
        'name',
        'formatted_address',
        'formatted_phone_number',
        'website',
        'rating',
        'user_ratings_total',
        'geometry',
        'url',
    ];

    public function __construct()
    {
        $this->apiKey  = config('audit.google_places.api_key');
        $this->baseUrl = config('audit.google_places.base_url');
    }

    // -----------------------------------------------------------------------
    // Public entry point
    // -----------------------------------------------------------------------

    /**
     * Fetch businesses for the given campaign and persist them.
     *
     * Returns the number of new businesses stored.
     */
    public function fetch(Campaign $campaign): int
    {
        $campaign->markAs('fetching');

        $placeIds = $this->searchPlaceIds(
            query: "{$campaign->business_type} in {$campaign->location}"
        );

        $stored = 0;

        foreach ($placeIds as $placeId) {
            // Skip if already in DB (re-run safety)
            if (Business::where('google_place_id', $placeId)->exists()) {
                continue;
            }

            $detail = $this->fetchPlaceDetail($placeId);

            if ($detail === null) {
                continue;
            }

            Business::create(
                $this->mapDetailToBusiness($detail, $campaign, $placeId)
            );

            $stored++;
        }

        $campaign->refreshCounts();
        $campaign->markAs($stored > 0 ? 'processing' : 'completed');

        Log::info("BusinessFetcherService: stored {$stored} businesses", [
            'campaign_id' => $campaign->id,
            'query'       => "{$campaign->business_type} in {$campaign->location}",
        ]);

        return $stored;
    }

    // -----------------------------------------------------------------------
    // Step 1 — Text Search (all pages)
    // -----------------------------------------------------------------------

    /**
     * Run a Text Search and collect all place_ids across pages.
     * Google returns up to 20 results per page, max 3 pages (60 results).
     *
     * @return array<string>
     */
    private function searchPlaceIds(string $query): array
    {
        $placeIds  = [];
        $nextToken = null;

        do {
            $params = [
                'query' => $query,
                'key'   => $this->apiKey,
            ];

            if ($nextToken !== null) {
                // Google requires a small delay before using a next_page_token
                sleep(2);
                $params = ['pagetoken' => $nextToken, 'key' => $this->apiKey];
            }

            $response = Http::timeout(15)
                ->get("{$this->baseUrl}/textsearch/json", $params);

            if ($response->failed()) {
                Log::error('BusinessFetcherService: Text Search API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                break;
            }

            $body   = $response->json();
            $status = $body['status'] ?? 'UNKNOWN';

            if (! in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
                Log::warning("BusinessFetcherService: API returned status [{$status}]", [
                    'query' => $query,
                ]);
                break;
            }

            foreach ($body['results'] ?? [] as $result) {
                if (isset($result['place_id'])) {
                    $placeIds[] = $result['place_id'];
                }
            }

            $nextToken = $body['next_page_token'] ?? null;

        } while ($nextToken !== null);

        return array_unique($placeIds);
    }

    // -----------------------------------------------------------------------
    // Step 2 — Place Details
    // -----------------------------------------------------------------------

    /**
     * Fetch rich detail for a single place_id.
     * Returns null on API error so the caller can skip gracefully.
     */
    private function fetchPlaceDetail(string $placeId): ?array
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/details/json", [
            'place_id' => $placeId,
            'fields'   => implode(',', self::DETAIL_FIELDS),
            'key'      => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('BusinessFetcherService: Place Details API error', [
                'place_id' => $placeId,
                'status'   => $response->status(),
            ]);
            return null;
        }

        $body   = $response->json();
        $status = $body['status'] ?? 'UNKNOWN';

        if ($status !== 'OK') {
            Log::warning("BusinessFetcherService: Place Details status [{$status}]", [
                'place_id' => $placeId,
            ]);
            return null;
        }

        return $body['result'] ?? null;
    }

    // -----------------------------------------------------------------------
    // Step 3 — Map API response → Business fields
    // -----------------------------------------------------------------------

    private function mapDetailToBusiness(array $detail, Campaign $campaign, string $placeId): array
    {
        $location = $detail['geometry']['location'] ?? [];

        // Parse city/country from formatted_address ("123 Street, City, Country")
        [$city, $country] = $this->parseAddress($detail['formatted_address'] ?? '');

        return [
            'campaign_id'          => $campaign->id,
            'name'                 => $detail['name'] ?? 'Unknown',
            'business_type'        => $campaign->business_type,
            'location'             => $campaign->location,
            'email'                => null, // extracted by ScraperService (Step 4)
            'phone'                => $detail['formatted_phone_number'] ?? null,
            'website'              => isset($detail['website'])
                                        ? rtrim($detail['website'], '/')
                                        : null,
            'address'              => $detail['formatted_address'] ?? null,
            'city'                 => $city,
            'country'              => $country,
            'latitude'             => $location['lat'] ?? null,
            'longitude'            => $location['lng'] ?? null,
            'google_place_id'      => $placeId,
            'google_rating'        => $detail['rating'] ?? null,
            'google_reviews_count' => $detail['user_ratings_total'] ?? 0,
            'status'               => 'fetched',
        ];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Naively extracts city and country from a formatted_address string.
     * Google's format: "Street, City, PostCode, Country"
     */
    private function parseAddress(string $formatted): array
    {
        if (empty($formatted)) {
            return [null, null];
        }

        $parts   = array_map('trim', explode(',', $formatted));
        $total   = count($parts);
        $country = $total >= 1 ? $parts[$total - 1] : null;
        $city    = $total >= 3 ? $parts[$total - 2] : null; // skip postcode if present

        // Strip UK/US-style postcodes from city field
        if ($city && preg_match('/^\w{1,4}\d[\d\w]?\s\d\w{2}$/', $city)) {
            $city = $total >= 4 ? $parts[$total - 3] : null;
        }

        return [$city, $country];
    }
}
