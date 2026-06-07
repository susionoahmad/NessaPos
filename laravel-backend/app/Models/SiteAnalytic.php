<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteAnalytic extends Model
{
    protected $fillable = [
        'event_type',
        'ip_address',
        'user_agent',
    ];
    public static function isBot($userAgent): bool
    {
        $userAgent = strtolower($userAgent ?? '');
        $bots = [
            'bot', 'spider', 'crawler', 'googlebot', 'bingbot', 'slurp',
            'duckduckbot', 'baiduspider', 'yandexbot', 'sogou', 'exabot',
            'facebookexternalhit', 'ia_archiver', 'twitterbot', 'linkedinbot',
            'slackbot', 'discordbot', 'telegrambot', 'whatsapp'
        ];

        foreach ($bots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }
}
