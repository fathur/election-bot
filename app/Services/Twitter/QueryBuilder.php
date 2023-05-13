<?php

namespace App\Services\Twitter;

use App\Exceptions\PollBotException;
use Illuminate\Support\Facades\Cache;
use App\Services\Twitter\Twitter;
use App\Enums\AccountType;

class QueryBuilder
{
    public const CURRENT_USER_CACHE_KEY = 'current-twitter-user';

    public const KEYWORDS = [
        "anies",
        "baswedan",
        "ganjar",
        "pranowo",
        "prabowo",
        "subianto",
        "capres",
        "cawapres",
        "pilpres",
        "election",
        "pemilu",
    ];


    public const USERNAMES = [
        "aniesbaswedan" => AccountType::CANDIDATE,
        "prabowo" => AccountType::CANDIDATE,
        "ganjarpranowo" => AccountType::CANDIDATE,
        "tvOneNews" => AccountType::MEDIA,
        "NasDem" => AccountType::PARTY,
        "PDemokrat" => AccountType::PARTY,
        "MataNajwa" => AccountType::MEDIA,
        "TirtoID" => AccountType::MEDIA,
        "VIVAcoid" => AccountType::MEDIA,
        "CNNIndonesia" => AccountType::MEDIA,
        # "korantempo" => AccountType::MEDIA,
        # "temponewsroom" => AccountType::MEDIA,
        "tempodotco" => AccountType::MEDIA,
        "PKSejahtera" => AccountType::PARTY,
        "PDI_Perjuangan" => AccountType::PARTY,
        "DPP_PPP" => AccountType::PARTY,
        "psi_id" => AccountType::PARTY,
        "hanura_official" => AccountType::PARTY,
        "Gerindra" => AccountType::PARTY,
        "Official_PAN" => AccountType::PARTY,
        "PartaiPerindo" => AccountType::PARTY,
        "OfficialDPP_PBB" => AccountType::PARTY,
        "kumparan" => AccountType::MEDIA,
        "kompascom" => AccountType::MEDIA,
        # "jawapos" => AccountType::MEDIA,
        "tribunnews" => AccountType::MEDIA,
        "liputan6dotcom" => AccountType::MEDIA,
        "Beritasatu" => AccountType::MEDIA,
        "okezonenews" => AccountType::MEDIA,
        "antaranews" => AccountType::MEDIA,
        "BBCIndonesia" => AccountType::MEDIA,
        "voaindonesia" => AccountType::MEDIA,
        "cnbcindonesia" => AccountType::MEDIA,
        "detikcom" => AccountType::MEDIA,
    ];

    public static function for(string $target)
    {
        $instance = new self();
        return $instance->generateQueryFor($target);
    }

    public function generateQueryFor(string $target)
    {
        if ($target == 'me') {
            $query = $this->queryForMe();
        } elseif ($target == AccountType::CANDIDATE->text()) {
            $query = $this->queryForCandidate();
        } elseif ($target == AccountType::MEDIA->text()) {
            $query = $this->queryForMedia();
        } elseif ($target == AccountType::PARTY->text()) {
            $query = $this->queryForParty();
        } elseif ($target == AccountType::INFLUENCER->text()) {
            $query = $this->queryForInfluencer();
        } else {
            throw new PollBotException("No match account type");

        }

        if (strlen($query) >= 512) {
            throw new PollBotException("Query too long");
        }

        return $query;
    }

    protected function targetStatement(AccountType $accountType)
    {
        $usernames = [];
        foreach (self::USERNAMES as $username => $type) {
            if ($type == $accountType) {
                array_push($usernames, "from:{$username}");
            }
        }
        $string = implode(' OR ', $usernames);
        return "({$string})";
    }

    protected function keywordsStatement()
    {
        $string = implode(' OR ', self::KEYWORDS);
        return "({$string})";
    }

    private function queryForMe()
    {
        $me = Cache::remember(self::CURRENT_USER_CACHE_KEY, now()->addMonth(), function () {
            $data = (new Twitter())->getMe()->data;
            $exists = Account::where('twitter_id', $data->id)->exists();
            if (!$exists) {
                Account::create([
                    'twitter_id'    => $data->id,
                    'username'    => $data->username,
                    'name'    => $data->name,
                ]);
            }
            return $data;
        });
        return "from:{$me->username}";
    }

    private function queryForCandidate()
    {
        $targetStatement = $this->targetStatement(AccountType::CANDIDATE);
        return "{$targetStatement} -is:retweet -is:reply";
    }


    private function queryForMedia()
    {
        $targetStatement = $this->targetStatement(AccountType::MEDIA);
        return "{$this->keywordsStatement()} {$targetStatement} -is:retweet -is:reply -is:quote";
    }


    private function queryForParty()
    {
        $targetStatement = $this->targetStatement(AccountType::PARTY);
        return "{$this->keywordsStatement()} {$targetStatement} -is:retweet -is:reply -is:quote";
    }


    private function queryForInfluencer()
    {
        throw new PollBotException("Query not yet ready for influencer");

    }
}
