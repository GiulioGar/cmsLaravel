<?php

namespace App\Services;

use Illuminate\Support\Str;

class UidGeneratorService
{
    public function generateBatch(string $panelName, int $count): array
    {
        $uids = [];

        $prefix = 'IDEX' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $panelName), 0, 3));

        while (count($uids) < $count) {
            $random = strtoupper(Str::random(8));
            $uid = $prefix . $random;

            // evita duplicati nello stesso batch, senza query DB
            if (!isset($uids[$uid])) {
                $uids[$uid] = $uid;
            }
        }

        return array_values($uids);
    }
}
