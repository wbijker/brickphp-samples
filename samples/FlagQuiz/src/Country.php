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
    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $raw = [
            ['af', 'Afghanistan'], ['al', 'Albania'], ['dz', 'Algeria'], ['ad', 'Andorra'], ['ao', 'Angola'],
            ['ag', 'Antigua and Barbuda', ['antigua']], ['ar', 'Argentina'], ['am', 'Armenia'], ['au', 'Australia'], ['at', 'Austria'],
            ['az', 'Azerbaijan'], ['bs', 'Bahamas'], ['bh', 'Bahrain'], ['bd', 'Bangladesh'], ['bb', 'Barbados'],
            ['by', 'Belarus'], ['be', 'Belgium'], ['bz', 'Belize'], ['bj', 'Benin'], ['bt', 'Bhutan'],
            ['bo', 'Bolivia'], ['ba', 'Bosnia and Herzegovina', ['bosnia']], ['bw', 'Botswana'], ['br', 'Brazil'], ['bn', 'Brunei'],
            ['bg', 'Bulgaria'], ['bf', 'Burkina Faso'], ['bi', 'Burundi'], ['cv', 'Cabo Verde', ['cape verde']], ['kh', 'Cambodia'],
            ['cm', 'Cameroon'], ['ca', 'Canada'], ['cf', 'Central African Republic', ['car']], ['td', 'Chad'], ['cl', 'Chile'],
            ['cn', 'China'], ['co', 'Colombia'], ['km', 'Comoros'], ['cg', 'Republic of the Congo', ['congo', 'congo brazzaville', 'republic of congo']],
            ['cd', 'DR Congo', ['dr congo', 'drc', 'democratic republic of the congo', 'democratic republic of congo', 'congo kinshasa', 'zaire']],
            ['cr', 'Costa Rica'], ['hr', 'Croatia'], ['cu', 'Cuba'], ['cy', 'Cyprus'], ['cz', 'Czechia', ['czech republic']],
            ['dk', 'Denmark'], ['dj', 'Djibouti'], ['dm', 'Dominica'], ['do', 'Dominican Republic'], ['ec', 'Ecuador'],
            ['eg', 'Egypt'], ['sv', 'El Salvador'], ['gq', 'Equatorial Guinea'], ['er', 'Eritrea'], ['ee', 'Estonia'],
            ['sz', 'Eswatini', ['swaziland']], ['et', 'Ethiopia'], ['fj', 'Fiji'], ['fi', 'Finland'], ['fr', 'France'],
            ['ga', 'Gabon'], ['gm', 'Gambia'], ['ge', 'Georgia'], ['de', 'Germany'], ['gh', 'Ghana'],
            ['gr', 'Greece'], ['gd', 'Grenada'], ['gt', 'Guatemala'], ['gn', 'Guinea'], ['gw', 'Guinea-Bissau'],
            ['gy', 'Guyana'], ['ht', 'Haiti'], ['hn', 'Honduras'], ['hu', 'Hungary'], ['is', 'Iceland'],
            ['in', 'India'], ['id', 'Indonesia'], ['ir', 'Iran'], ['iq', 'Iraq'], ['ie', 'Ireland'],
            ['il', 'Israel'], ['it', 'Italy'], ['jm', 'Jamaica'], ['jp', 'Japan'], ['jo', 'Jordan'],
            ['kz', 'Kazakhstan'], ['ke', 'Kenya'], ['ki', 'Kiribati'], ['xk', 'Kosovo'], ['kw', 'Kuwait'],
            ['kg', 'Kyrgyzstan'], ['la', 'Laos'], ['lv', 'Latvia'], ['lb', 'Lebanon'], ['ls', 'Lesotho'],
            ['lr', 'Liberia'], ['ly', 'Libya'], ['li', 'Liechtenstein'], ['lt', 'Lithuania'], ['lu', 'Luxembourg'],
            ['mg', 'Madagascar'], ['mw', 'Malawi'], ['my', 'Malaysia'], ['mv', 'Maldives'], ['ml', 'Mali'],
            ['mt', 'Malta'], ['mh', 'Marshall Islands'], ['mr', 'Mauritania'], ['mu', 'Mauritius'], ['mx', 'Mexico'],
            ['fm', 'Micronesia'], ['md', 'Moldova'], ['mc', 'Monaco'], ['mn', 'Mongolia'], ['me', 'Montenegro'],
            ['ma', 'Morocco'], ['mz', 'Mozambique'], ['mm', 'Myanmar', ['burma']], ['na', 'Namibia'], ['nr', 'Nauru'],
            ['np', 'Nepal'], ['nl', 'Netherlands', ['holland']], ['nz', 'New Zealand'], ['ni', 'Nicaragua'], ['ne', 'Niger'],
            ['ng', 'Nigeria'], ['kp', 'North Korea'], ['mk', 'North Macedonia', ['macedonia']], ['no', 'Norway'], ['om', 'Oman'],
            ['pk', 'Pakistan'], ['pw', 'Palau'], ['ps', 'Palestine'], ['pa', 'Panama'], ['pg', 'Papua New Guinea'],
            ['py', 'Paraguay'], ['pe', 'Peru'], ['ph', 'Philippines'], ['pl', 'Poland'], ['pt', 'Portugal'],
            ['qa', 'Qatar'], ['ro', 'Romania'], ['ru', 'Russia', ['russian federation']], ['rw', 'Rwanda'],
            ['kn', 'Saint Kitts and Nevis', ['st kitts and nevis', 'st kitts']], ['lc', 'Saint Lucia', ['st lucia']],
            ['vc', 'Saint Vincent and the Grenadines', ['st vincent and the grenadines', 'st vincent']], ['ws', 'Samoa'], ['sm', 'San Marino'],
            ['st', 'Sao Tome and Principe'], ['sa', 'Saudi Arabia'], ['sn', 'Senegal'], ['rs', 'Serbia'], ['sc', 'Seychelles'],
            ['sl', 'Sierra Leone'], ['sg', 'Singapore'], ['sk', 'Slovakia'], ['si', 'Slovenia'], ['sb', 'Solomon Islands'],
            ['so', 'Somalia'], ['za', 'South Africa'], ['kr', 'South Korea'], ['ss', 'South Sudan'], ['es', 'Spain'],
            ['lk', 'Sri Lanka'], ['sd', 'Sudan'], ['sr', 'Suriname'], ['se', 'Sweden'], ['ch', 'Switzerland'],
            ['sy', 'Syria'], ['tw', 'Taiwan'], ['tj', 'Tajikistan'], ['tz', 'Tanzania'], ['th', 'Thailand'],
            ['tl', 'Timor-Leste', ['east timor']], ['tg', 'Togo'], ['to', 'Tonga'], ['tt', 'Trinidad and Tobago', ['trinidad']], ['tn', 'Tunisia'],
            ['tr', 'Turkey', ['turkiye']], ['tm', 'Turkmenistan'], ['tv', 'Tuvalu'], ['ug', 'Uganda'], ['ua', 'Ukraine'],
            ['ae', 'United Arab Emirates', ['uae', 'emirates']], ['gb', 'United Kingdom', ['uk', 'britain', 'great britain']],
            ['us', 'United States', ['usa', 'us', 'america', 'united states of america']], ['uy', 'Uruguay'], ['uz', 'Uzbekistan'],
            ['vu', 'Vanuatu'], ['va', 'Vatican City', ['vatican', 'holy see']], ['ve', 'Venezuela'], ['vn', 'Vietnam'], ['ye', 'Yemen'],
            ['zm', 'Zambia'], ['zw', 'Zimbabwe'],
        ];

        $cache = array_map(
            static fn(array $c) => new self($c[0], $c[1], $c[2] ?? []),
            $raw,
        );
        return $cache;
    }
}
