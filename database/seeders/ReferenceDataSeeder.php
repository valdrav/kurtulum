<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $ports = [
            ['code' => 'TRIST', 'name' => 'İstanbul Ambarlı', 'country' => 'TR', 'type' => 'sea'],
            ['code' => 'TRIZM', 'name' => 'İzmir Limanı', 'country' => 'TR', 'type' => 'sea'],
            ['code' => 'TRMER', 'name' => 'Mersin Limanı', 'country' => 'TR', 'type' => 'sea'],
            ['code' => 'TRLYM', 'name' => 'Limra Limanı', 'country' => 'TR', 'type' => 'sea', 'latitude' => 36.645, 'longitude' => 29.115],
            ['code' => 'DEHAM', 'name' => 'Hamburg Limanı', 'country' => 'DE', 'type' => 'sea'],
            ['code' => 'NLRTM', 'name' => 'Rotterdam Limanı', 'country' => 'NL', 'type' => 'sea'],
            ['code' => 'CNSHA', 'name' => 'Şanghay Limanı', 'country' => 'CN', 'type' => 'sea'],
            ['code' => 'AEJEA', 'name' => 'Jebel Ali Limanı', 'country' => 'AE', 'type' => 'sea'],
            ['code' => 'SAJED', 'name' => 'Cidde Limanı', 'country' => 'SA', 'type' => 'sea'],
            ['code' => 'SARUH', 'name' => 'Riyad Havalimanı', 'country' => 'SA', 'type' => 'air'],
            ['code' => 'TRIST_AIR', 'name' => 'İstanbul Havalimanı', 'country' => 'TR', 'type' => 'air'],
            ['code' => 'TRSAW', 'name' => 'Sabiha Gökçen Havalimanı', 'country' => 'TR', 'type' => 'air'],
            ['code' => 'DEFRA', 'name' => 'Frankfurt Havalimanı', 'country' => 'DE', 'type' => 'air'],
            ['code' => 'TRCIL', 'name' => 'Cilvegözü Sınır Kapısı', 'country' => 'TR', 'type' => 'land'],
            ['code' => 'TRKAP', 'name' => 'Kapıkule Sınır Kapısı', 'country' => 'TR', 'type' => 'land'],
            ['code' => 'TRHAB', 'name' => 'Habur Sınır Kapısı', 'country' => 'TR', 'type' => 'land'],
            ['code' => 'TRIPS', 'name' => 'İpsala Sınır Kapısı', 'country' => 'TR', 'type' => 'land'],
            ['code' => 'TRHAM', 'name' => 'Hamzabeyli Sınır Kapısı', 'country' => 'TR', 'type' => 'land'],
            ['code' => 'TRKAPIKULE', 'name' => 'Kapıkule (eski kayıt)', 'country' => 'TR', 'type' => 'land'],
        ];

        foreach ($ports as $port) {
            Port::updateOrCreate(['code' => $port['code']], $port);
        }
    }
}
