<?php

namespace Samples\FlagQuiz;

/**
 * A single country: its ISO-2 code, canonical name, and accepted aliases.
 * Owns the flag-image URLs and the guess-matching logic. The full catalogue
 * lives in {@see Country::all()}; the game stores only indices into it.
 */
final class Country
{
    /** @param string[] $aliases */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly Continent $continent,
        public readonly array $aliases = [],
    ) {}

    public function bigUrl(): string
    {
        return 'https://flagcdn.com/w320/' . $this->code . '.png';
    }

    public function thumbUrl(): string
    {
        return 'https://flagcdn.com/w160/' . $this->code . '.png';
    }

    /** True when $guess matches this country's name or any accepted alias. */
    public function matches(string $guess): bool
    {
        $needle = self::normalize($guess);
        if ($needle === '') {
            return false;
        }
        if ($needle === self::normalize($this->name)) {
            return true;
        }
        foreach ($this->aliases as $alias) {
            if ($needle === self::normalize($alias)) {
                return true;
            }
        }
        return false;
    }

    /** Lowercase, strip diacritics/punctuation and the filler words the/and/of. */
    public static function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) {
            $s = $t;
        }
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        $s = preg_replace('/\b(the|and|of)\b/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /**
     * The full catalogue, built once. Order is the natural (alphabetical)
     * order; the game shuffles indices into this list.
     *
     * @return Country[]
     */
    public static function byCode(string $code): ?self
    {
        $code = strtolower($code);
        foreach (self::all() as $country) {
            if ($country->code === $code) {
                return $country;
            }
        }
        return null;
    }

    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // Grouped by continent so each country's region is auditable at a
        // glance. Each row is [code, name] or [code, name, aliases]. The list
        // is flattened and sorted by name below (the game shuffles regardless).
        $groups = [
            [Continent::Africa, [
                ['dz', 'Algeria'], ['ao', 'Angola'], ['bj', 'Benin'], ['bw', 'Botswana'], ['bf', 'Burkina Faso'],
                ['bi', 'Burundi'], ['cv', 'Cabo Verde', ['cape verde']], ['cm', 'Cameroon'], ['cf', 'Central African Republic', ['car']],
                ['td', 'Chad'], ['km', 'Comoros'], ['cg', 'Republic of the Congo', ['congo', 'congo brazzaville', 'republic of congo']],
                ['cd', 'DR Congo', ['dr congo', 'drc', 'democratic republic of the congo', 'democratic republic of congo', 'congo kinshasa', 'zaire']],
                ['dj', 'Djibouti'], ['eg', 'Egypt'], ['gq', 'Equatorial Guinea'], ['er', 'Eritrea'], ['sz', 'Eswatini', ['swaziland']],
                ['et', 'Ethiopia'], ['ga', 'Gabon'], ['gm', 'Gambia'], ['gh', 'Ghana'], ['gn', 'Guinea'], ['gw', 'Guinea-Bissau'],
                ['ci', 'Ivory Coast', ['cote divoire', "cote d'ivoire", 'cote d ivoire']],
                ['ke', 'Kenya'], ['ls', 'Lesotho'], ['lr', 'Liberia'], ['ly', 'Libya'], ['mg', 'Madagascar'], ['mw', 'Malawi'],
                ['ml', 'Mali'], ['mr', 'Mauritania'], ['mu', 'Mauritius'], ['ma', 'Morocco'], ['mz', 'Mozambique'], ['na', 'Namibia'],
                ['ne', 'Niger'], ['ng', 'Nigeria'], ['rw', 'Rwanda'], ['st', 'Sao Tome and Principe'], ['sn', 'Senegal'],
                ['sc', 'Seychelles'], ['sl', 'Sierra Leone'], ['so', 'Somalia'], ['za', 'South Africa'], ['ss', 'South Sudan'],
                ['sd', 'Sudan'], ['tz', 'Tanzania'], ['tg', 'Togo'], ['tn', 'Tunisia'], ['ug', 'Uganda'], ['zm', 'Zambia'], ['zw', 'Zimbabwe'],
            ]],
            [Continent::Asia, [
                ['af', 'Afghanistan'], ['am', 'Armenia'], ['az', 'Azerbaijan'], ['bh', 'Bahrain'], ['bd', 'Bangladesh'],
                ['bt', 'Bhutan'], ['bn', 'Brunei'], ['kh', 'Cambodia'], ['cn', 'China'], ['ge', 'Georgia'], ['in', 'India'],
                ['id', 'Indonesia'], ['ir', 'Iran'], ['iq', 'Iraq'], ['il', 'Israel'], ['jp', 'Japan'], ['jo', 'Jordan'],
                ['kz', 'Kazakhstan'], ['kw', 'Kuwait'], ['kg', 'Kyrgyzstan'], ['la', 'Laos'], ['lb', 'Lebanon'], ['my', 'Malaysia'],
                ['mv', 'Maldives'], ['mn', 'Mongolia'], ['mm', 'Myanmar', ['burma']], ['np', 'Nepal'], ['kp', 'North Korea'],
                ['om', 'Oman'], ['pk', 'Pakistan'], ['ps', 'Palestine'], ['ph', 'Philippines'], ['qa', 'Qatar'], ['sa', 'Saudi Arabia'],
                ['sg', 'Singapore'], ['kr', 'South Korea'], ['lk', 'Sri Lanka'], ['sy', 'Syria'], ['tw', 'Taiwan'], ['tj', 'Tajikistan'],
                ['th', 'Thailand'], ['tl', 'Timor-Leste', ['east timor']], ['tr', 'Turkey', ['turkiye']], ['tm', 'Turkmenistan'],
                ['ae', 'United Arab Emirates', ['uae', 'emirates']], ['uz', 'Uzbekistan'], ['vn', 'Vietnam'], ['ye', 'Yemen'],
            ]],
            [Continent::Europe, [
                ['al', 'Albania'], ['ad', 'Andorra'], ['at', 'Austria'], ['by', 'Belarus'], ['be', 'Belgium'],
                ['ba', 'Bosnia and Herzegovina', ['bosnia']], ['bg', 'Bulgaria'], ['hr', 'Croatia'], ['cy', 'Cyprus'],
                ['cz', 'Czechia', ['czech republic']], ['dk', 'Denmark'], ['ee', 'Estonia'], ['fi', 'Finland'], ['fr', 'France'],
                ['de', 'Germany'], ['gr', 'Greece'], ['hu', 'Hungary'], ['is', 'Iceland'], ['ie', 'Ireland'], ['it', 'Italy'],
                ['xk', 'Kosovo'], ['lv', 'Latvia'], ['li', 'Liechtenstein'], ['lt', 'Lithuania'], ['lu', 'Luxembourg'], ['mt', 'Malta'],
                ['md', 'Moldova'], ['mc', 'Monaco'], ['me', 'Montenegro'], ['nl', 'Netherlands', ['holland']],
                ['mk', 'North Macedonia', ['macedonia']], ['no', 'Norway'], ['pl', 'Poland'], ['pt', 'Portugal'], ['ro', 'Romania'],
                ['ru', 'Russia', ['russian federation']], ['sm', 'San Marino'], ['rs', 'Serbia'], ['sk', 'Slovakia'], ['si', 'Slovenia'],
                ['es', 'Spain'], ['se', 'Sweden'], ['ch', 'Switzerland'], ['ua', 'Ukraine'],
                ['gb', 'United Kingdom', ['uk', 'britain', 'great britain']], ['va', 'Vatican City', ['vatican', 'holy see']],
            ]],
            [Continent::NorthAmerica, [
                ['ag', 'Antigua and Barbuda', ['antigua']], ['bs', 'Bahamas'], ['bb', 'Barbados'], ['bz', 'Belize'], ['ca', 'Canada'],
                ['cr', 'Costa Rica'], ['cu', 'Cuba'], ['dm', 'Dominica'], ['do', 'Dominican Republic'], ['sv', 'El Salvador'],
                ['gd', 'Grenada'], ['gt', 'Guatemala'], ['ht', 'Haiti'], ['hn', 'Honduras'], ['jm', 'Jamaica'], ['mx', 'Mexico'],
                ['ni', 'Nicaragua'], ['pa', 'Panama'], ['kn', 'Saint Kitts and Nevis', ['st kitts and nevis', 'st kitts']],
                ['lc', 'Saint Lucia', ['st lucia']], ['vc', 'Saint Vincent and the Grenadines', ['st vincent and the grenadines', 'st vincent']],
                ['tt', 'Trinidad and Tobago', ['trinidad']], ['us', 'United States', ['usa', 'us', 'america', 'united states of america']],
            ]],
            [Continent::SouthAmerica, [
                ['ar', 'Argentina'], ['bo', 'Bolivia'], ['br', 'Brazil'], ['cl', 'Chile'], ['co', 'Colombia'], ['ec', 'Ecuador'],
                ['gy', 'Guyana'], ['py', 'Paraguay'], ['pe', 'Peru'], ['sr', 'Suriname'], ['uy', 'Uruguay'], ['ve', 'Venezuela'],
            ]],
            [Continent::Oceania, [
                ['au', 'Australia'], ['fj', 'Fiji'], ['ki', 'Kiribati'], ['mh', 'Marshall Islands'], ['fm', 'Micronesia'],
                ['nr', 'Nauru'], ['nz', 'New Zealand'], ['pw', 'Palau'], ['pg', 'Papua New Guinea'], ['ws', 'Samoa'],
                ['sb', 'Solomon Islands'], ['to', 'Tonga'], ['tv', 'Tuvalu'], ['vu', 'Vanuatu'],
            ]],
        ];

        $cache = [];
        foreach ($groups as [$continent, $rows]) {
            foreach ($rows as $c) {
                $cache[] = new self($c[0], $c[1], $continent, $c[2] ?? []);
            }
        }
        usort($cache, static fn(self $a, self $b) => strcmp($a->name, $b->name));
        return $cache;
    }
}
