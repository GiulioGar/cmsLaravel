<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UidGeneratorService
{
    public function generateBatch(string $panelName, int $count): array
    {
        $uids = [];
        $prefix = 'IDEX' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $panelName), 0, 3));

        for ($i = 1; $i <= $count; $i++) {
            do {
                $random = strtoupper(Str::random(5));
                $uid = $prefix . $random . $i;
                $exists = DB::table('t_respint')->where('uid', $uid)->exists();
            } while ($exists);

            $uids[] = $uid;
        }

        return $uids;
    }
}
