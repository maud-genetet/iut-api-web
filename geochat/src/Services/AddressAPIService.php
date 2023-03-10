<?php
// src/Service/AddressAPIService
namespace App\Services;

use GuzzleHttp\Client;

class AddressAPIService
{
    public function getLngLat(string $address): ?array
    {
        $url = "https://api-adresse.data.gouv.fr/search/?q=" . urlencode($address) . "&limit=1";
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data && isset($data['features']) && count($data['features']) > 0) {
            $longitude = $data['features'][0]['geometry']['coordinates'][0];
            $latitude = $data['features'][0]['geometry']['coordinates'][1];
            return ['longitude' => $longitude, 'latitude' => $latitude];
        } else {
            return null;
        }
    }

    public function getAddresses(array $lnglat): ?string
    {
        $data = "public/reverse.csv";
        $lat = $lnglat['lat'];
        $lon = $lnglat['lon'];

        $url = "https://api-adresse.data.gouv.fr/reverse/csv/";
        $params = [
            'lat' => $lat,
            'lon' => $lon,
            'data' => $data
        ];
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response !== false) {
            $data = str_getcsv($response, ";");
            if (count($data) >= 8 && $data[0] != "adresse") {
                return $data[2] . " " . $data[4] . " " . $data[6] . " " . $data[7];
            }
        }
        return null;
    }
}
