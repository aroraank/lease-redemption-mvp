<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LeaseApiService
 * -----------------------------------------------------------------------------
 * Single integration point with the external "lease" provider.
 *
 * Until the client supplies the real API, this ships with a MOCK that uses a
 * fixed catalog of demo dealers and PERSISTS redemptions to a JSON file, so a
 * redeemed lease no longer appears on the next lookup (and the list empties out
 * once every lease for a dealer is redeemed).
 *
 * To go live: set LEASE_API_MOCK=false plus LEASE_API_BASE_URL / LEASE_API_KEY
 * in .env and adjust fromRealApiLease(). Nothing else changes.
 */
class LeaseApiService
{
    private bool $useMock;
    private string $baseUrl;
    private ?string $apiKey;

    public function __construct()
    {
        $this->useMock = (bool) config('lease.mock', true);
        $this->baseUrl = (string) config('lease.base_url', '');
        $this->apiKey  = config('lease.api_key');
    }

    public function getLeases(string $dealerCode, string $zip): array
    {
        if ($this->useMock) {
            return $this->mockGetLeases($dealerCode, $zip);
        }

        try {
            $response = Http::withToken($this->apiKey)->acceptJson()->timeout(15)
                ->get("{$this->baseUrl}/leases", [
                    'dealer_code' => $dealerCode,
                    'zip'         => $zip,
                ]);

            if (!$response->successful()) {
                return ['ok' => false, 'message' => 'The lease provider rejected the request (' . $response->status() . ').', 'leases' => []];
            }

            $leases = collect($response->json('data', []))->map(fn ($row) => $this->fromRealApiLease($row))->all();
            return ['ok' => true, 'message' => 'Leases retrieved successfully.', 'leases' => $leases];
        } catch (\Throwable $e) {
            Log::error('Lease lookup failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Could not reach the lease provider. Please try again.', 'leases' => []];
        }
    }

    public function redeem(array $leaseIds): array
    {
        $leaseIds = array_values(array_unique(array_filter($leaseIds)));

        if (empty($leaseIds)) {
            return ['ok' => false, 'message' => 'No leases were selected for redemption.', 'redeemed' => 0];
        }

        if ($this->useMock) {
            return $this->mockRedeem($leaseIds);
        }

        try {
            $response = Http::withToken($this->apiKey)->acceptJson()->timeout(20)
                ->post("{$this->baseUrl}/leases/redeem", ['lease_ids' => $leaseIds]);

            if (!$response->successful()) {
                return ['ok' => false, 'message' => 'Redemption failed at the provider (' . $response->status() . ').', 'redeemed' => 0];
            }

            return ['ok' => true, 'message' => 'Redemption completed successfully.', 'redeemed' => (int) $response->json('redeemed', count($leaseIds))];
        } catch (\Throwable $e) {
            Log::error('Lease redemption failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Could not reach the lease provider. Please try again.', 'redeemed' => 0];
        }
    }

    /** Clear all persisted redemptions (demo reset). */
    public function resetRedemptions(): void
    {
        $this->saveRedeemed([]);
    }

    /** Valid demo dealer codes, for hints/messages. */
    public static function demoDealerCodes(): array
    {
        return ['DLR1024', 'DLR2048', 'DLR3072', 'DLR4096'];
    }

    // -------------------------------------------------------------------------
    // Real-API payload mapper — adjust to match the real provider's fields.
    // -------------------------------------------------------------------------

    private function fromRealApiLease(array $row): array
    {
        return [
            'id'       => (string) ($row['id'] ?? $row['lease_id'] ?? ''),
            'customer' => (string) ($row['customer_name'] ?? 'Unknown'),
            'vehicle'  => (string) ($row['vehicle'] ?? ''),
            'vin'      => (string) ($row['vin'] ?? ''),
            'maturity' => (string) ($row['maturity_date'] ?? ''),
            'payoff'   => (float)  ($row['payoff_amount'] ?? 0),
            'status'   => (string) ($row['status'] ?? 'Eligible'),
        ];
    }

    // -------------------------------------------------------------------------
    // MOCK implementation
    // -------------------------------------------------------------------------

    private function mockGetLeases(string $dealerCode, string $zip): array
    {
        $code    = strtoupper(trim($dealerCode));
        $catalog = $this->catalog();

        if (!isset($catalog[$code])) {
            return [
                'ok'      => false,
                'message' => "No dealer found for code \"{$dealerCode}\". Valid demo codes: "
                    . implode(', ', self::demoDealerCodes()) . '.',
                'leases'  => [],
            ];
        }

        $redeemed  = $this->loadRedeemed();
        $available = array_values(array_filter(
            $catalog[$code],
            fn ($lease) => !in_array($lease['id'], $redeemed, true)
        ));

        if (empty($available)) {
            return [
                'ok'      => false,
                'message' => "All leases for dealer {$code} have already been redeemed. "
                    . 'Use “Reset demo data” to start over.',
                'leases'  => [],
            ];
        }

        return [
            'ok'      => true,
            'message' => count($available) . ' eligible lease(s) found for dealer ' . $code . '.',
            'leases'  => $available,
        ];
    }

    private function mockRedeem(array $leaseIds): array
    {
        $redeemed = $this->loadRedeemed();
        $redeemed = array_values(array_unique(array_merge($redeemed, $leaseIds)));
        $this->saveRedeemed($redeemed);

        $count = count($leaseIds);
        return [
            'ok'       => true,
            'message'  => $count === 1
                ? 'Lease redeemed successfully.'
                : "{$count} leases redeemed successfully.",
            'redeemed' => $count,
        ];
    }

    // --- persistence ---------------------------------------------------------

    private function storeFile(): string
    {
        return storage_path('app/lease_redeemed.json');
    }

    /** @return array<int, string> */
    private function loadRedeemed(): array
    {
        $file = $this->storeFile();
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /** @param array<int, string> $ids */
    private function saveRedeemed(array $ids): void
    {
        $dir = dirname($this->storeFile());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->storeFile(), json_encode(array_values($ids)));
    }

    // --- fixed demo catalog --------------------------------------------------

    private function catalog(): array
    {
        $make = function (string $id, string $customer, string $vehicle, string $maturity, float $payoff): array {
            return [
                'id'       => $id,
                'customer' => $customer,
                'vehicle'  => $vehicle,
                'vin'      => strtoupper(substr(md5($id), 0, 17)),
                'maturity' => $maturity,
                'payoff'   => $payoff,
                'status'   => 'Eligible',
            ];
        };

        return [
            'DLR1024' => [
                $make('LSE-50012', 'Amelia Brooks',   'Toyota Camry SE',      '2026-09-14', 18450.00),
                $make('LSE-50019', 'Marcus Lee',      'Honda CR-V EX',        '2026-11-02', 24990.50),
                $make('LSE-50027', 'Priya Nair',      'Tesla Model 3',        '2027-01-20', 33120.00),
                $make('LSE-50033', 'David Okafor',    'Ford F-150 XLT',       '2026-12-08', 29870.25),
                $make('LSE-50041', 'Sofia Romano',    'Mazda CX-5 Touring',   '2026-10-19', 21500.00),
            ],
            'DLR2048' => [
                $make('LSE-51002', 'Liam Carter',     'BMW 330i',             '2027-02-11', 31240.00),
                $make('LSE-51010', 'Hana Suzuki',     'Hyundai Tucson SEL',   '2026-10-30', 22680.75),
                $make('LSE-51018', 'Noah Bennett',    'Chevrolet Equinox LT', '2026-12-22', 19980.00),
                $make('LSE-51026', 'Elena Petrova',   'Nissan Rogue SV',      '2027-03-05', 20450.50),
            ],
            'DLR3072' => [
                $make('LSE-52004', 'Jamal Washington','Jeep Grand Cherokee',  '2027-01-09', 35990.00),
                $make('LSE-52012', 'Amelia Brooks',   'Tesla Model 3',        '2026-11-27', 33880.00),
                $make('LSE-52020', 'Marcus Lee',      'Toyota Camry SE',      '2026-09-30', 17990.25),
                $make('LSE-52028', 'Priya Nair',      'Honda CR-V EX',        '2027-02-18', 25120.00),
            ],
            'DLR4096' => [
                $make('LSE-53006', 'David Okafor',    'Ford F-150 XLT',       '2026-12-15', 30210.00),
                $make('LSE-53014', 'Sofia Romano',    'BMW 330i',             '2027-01-31', 32990.50),
                $make('LSE-53022', 'Liam Carter',     'Mazda CX-5 Touring',   '2026-10-07', 21200.00),
            ],
        ];
    }
}
