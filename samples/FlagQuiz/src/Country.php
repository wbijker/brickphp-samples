<?php

namespace Samples\FlagQuiz;

/**
 * A single country: its ISO-2 code, canonical name, capital(s) and accepted
 * aliases. Owns the flag-image URLs and the guess-matching logic. The full
 * catalogue lives in {@see Country::all()}; the game stores only indices into it.
 */
final class Country
{
    /**
     * @param string[] $capitals One entry for most countries; more where the
     *   roles are genuinely split — Bolivia's constitutional Sucre and
     *   governing La Paz, South Africa's three. Listed in the order the
     *   country itself leads with.
     * @param string[] $aliases
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly Continent $continent,
        public readonly array $capitals = [],
        public readonly array $aliases = [],
    ) {}

    /** The capitals as one readable string: "Sucre / La Paz". */
    public function capitalLabel(): string
    {
        return implode(' / ', $this->capitals);
    }

    public function bigUrl(): string
    {
        return 'https://flagcdn.com/w320/' . $this->code . '.png';
    }

    public function thumbUrl(): string
    {
        return 'https://flagcdn.com/w160/' . $this->code . '.png';
    }

    /**
     * The smallest of the three, for flags drawn at postage-stamp size and in
     * quantity — a screen showing all 197 at once pays for every one of them.
     */
    public function tinyUrl(): string
    {
        return 'https://flagcdn.com/w80/' . $this->code . '.png';
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

    /**
     * True when $guess names any of this country's capitals. Where the roles
     * are split — Sucre and La Paz, Pretoria and Cape Town and Bloemfontein —
     * each one on its own is a right answer; nobody should have to know which
     * of the three the list leads with.
     */
    public function matchesCapital(string $guess): bool
    {
        $needle = self::normalize($guess);
        if ($needle === '') {
            return false;
        }
        foreach ($this->capitals as $capital) {
            if ($needle === self::normalize($capital)) {
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
        // glance. Each row is [code, name, capitals] or
        // [code, name, capitals, aliases]. The list is flattened and sorted by
        // name below (the game shuffles regardless).
        //
        // Where a country splits the role across cities, every seat is listed:
        // Bolivia, South Africa, Eswatini, Benin, Ivory Coast, the Netherlands,
        // Sri Lanka, Malaysia and Burundi. Israel and Palestine are given the
        // seats their governments name; the contest over them is real and this
        // list takes no position on it.
        $groups = [
            [Continent::Africa, [
                ['dz', 'Algeria', ['Algiers']], ['ao', 'Angola', ['Luanda']],
                ['bj', 'Benin', ['Porto-Novo', 'Cotonou']], ['bw', 'Botswana', ['Gaborone']],
                ['bf', 'Burkina Faso', ['Ouagadougou']], ['bi', 'Burundi', ['Gitega', 'Bujumbura']],
                ['cv', 'Cabo Verde', ['Praia'], ['cape verde']], ['cm', 'Cameroon', ['Yaoundé']],
                ['cf', 'Central African Republic', ['Bangui'], ['car']],
                ['td', 'Chad', ["N'Djamena"]], ['km', 'Comoros', ['Moroni']],
                ['cg', 'Republic of the Congo', ['Brazzaville'], ['congo', 'congo brazzaville', 'republic of congo']],
                ['cd', 'DR Congo', ['Kinshasa'], ['dr congo', 'drc', 'democratic republic of the congo', 'democratic republic of congo', 'congo kinshasa', 'zaire']],
                ['dj', 'Djibouti', ['Djibouti']], ['eg', 'Egypt', ['Cairo']], ['gq', 'Equatorial Guinea', ['Malabo']],
                ['er', 'Eritrea', ['Asmara']], ['sz', 'Eswatini', ['Mbabane', 'Lobamba'], ['swaziland']],
                ['et', 'Ethiopia', ['Addis Ababa']], ['ga', 'Gabon', ['Libreville']], ['gm', 'Gambia', ['Banjul']],
                ['gh', 'Ghana', ['Accra']], ['gn', 'Guinea', ['Conakry']], ['gw', 'Guinea-Bissau', ['Bissau']],
                ['ci', 'Ivory Coast', ['Yamoussoukro', 'Abidjan'], ['cote divoire', "cote d'ivoire", 'cote d ivoire']],
                ['ke', 'Kenya', ['Nairobi']], ['ls', 'Lesotho', ['Maseru']], ['lr', 'Liberia', ['Monrovia']],
                ['ly', 'Libya', ['Tripoli']], ['mg', 'Madagascar', ['Antananarivo']], ['mw', 'Malawi', ['Lilongwe']],
                ['ml', 'Mali', ['Bamako']], ['mr', 'Mauritania', ['Nouakchott']], ['mu', 'Mauritius', ['Port Louis']],
                ['ma', 'Morocco', ['Rabat']], ['mz', 'Mozambique', ['Maputo']], ['na', 'Namibia', ['Windhoek']],
                ['ne', 'Niger', ['Niamey']], ['ng', 'Nigeria', ['Abuja']], ['rw', 'Rwanda', ['Kigali']],
                ['st', 'Sao Tome and Principe', ['São Tomé']], ['sn', 'Senegal', ['Dakar']],
                ['sc', 'Seychelles', ['Victoria']], ['sl', 'Sierra Leone', ['Freetown']], ['so', 'Somalia', ['Mogadishu']],
                ['za', 'South Africa', ['Pretoria', 'Cape Town', 'Bloemfontein']], ['ss', 'South Sudan', ['Juba']],
                ['sd', 'Sudan', ['Khartoum']], ['tz', 'Tanzania', ['Dodoma']], ['tg', 'Togo', ['Lomé']],
                ['tn', 'Tunisia', ['Tunis']], ['ug', 'Uganda', ['Kampala']], ['zm', 'Zambia', ['Lusaka']],
                ['zw', 'Zimbabwe', ['Harare']],
            ]],
            [Continent::Asia, [
                ['af', 'Afghanistan', ['Kabul']], ['am', 'Armenia', ['Yerevan']], ['az', 'Azerbaijan', ['Baku']],
                ['bh', 'Bahrain', ['Manama']], ['bd', 'Bangladesh', ['Dhaka']], ['bt', 'Bhutan', ['Thimphu']],
                ['bn', 'Brunei', ['Bandar Seri Begawan']], ['kh', 'Cambodia', ['Phnom Penh']], ['cn', 'China', ['Beijing']],
                ['ge', 'Georgia', ['Tbilisi']], ['in', 'India', ['New Delhi']], ['id', 'Indonesia', ['Jakarta']],
                ['ir', 'Iran', ['Tehran']], ['iq', 'Iraq', ['Baghdad']], ['il', 'Israel', ['Jerusalem']],
                ['jp', 'Japan', ['Tokyo']], ['jo', 'Jordan', ['Amman']], ['kz', 'Kazakhstan', ['Astana']],
                ['kw', 'Kuwait', ['Kuwait City']], ['kg', 'Kyrgyzstan', ['Bishkek']], ['la', 'Laos', ['Vientiane']],
                ['lb', 'Lebanon', ['Beirut']], ['my', 'Malaysia', ['Kuala Lumpur', 'Putrajaya']], ['mv', 'Maldives', ['Malé']],
                ['mn', 'Mongolia', ['Ulaanbaatar']], ['mm', 'Myanmar', ['Naypyidaw'], ['burma']], ['np', 'Nepal', ['Kathmandu']],
                ['kp', 'North Korea', ['Pyongyang']], ['om', 'Oman', ['Muscat']], ['pk', 'Pakistan', ['Islamabad']],
                ['ps', 'Palestine', ['Ramallah']], ['ph', 'Philippines', ['Manila']], ['qa', 'Qatar', ['Doha']],
                ['sa', 'Saudi Arabia', ['Riyadh']], ['sg', 'Singapore', ['Singapore']], ['kr', 'South Korea', ['Seoul']],
                ['lk', 'Sri Lanka', ['Sri Jayawardenepura Kotte', 'Colombo']], ['sy', 'Syria', ['Damascus']],
                ['tw', 'Taiwan', ['Taipei']], ['tj', 'Tajikistan', ['Dushanbe']], ['th', 'Thailand', ['Bangkok']],
                ['tl', 'Timor-Leste', ['Dili'], ['east timor']], ['tr', 'Turkey', ['Ankara'], ['turkiye']],
                ['tm', 'Turkmenistan', ['Ashgabat']],
                ['ae', 'United Arab Emirates', ['Abu Dhabi'], ['uae', 'emirates']], ['uz', 'Uzbekistan', ['Tashkent']],
                ['vn', 'Vietnam', ['Hanoi']], ['ye', 'Yemen', ["Sana'a"]],
            ]],
            [Continent::Europe, [
                ['al', 'Albania', ['Tirana']], ['ad', 'Andorra', ['Andorra la Vella']], ['at', 'Austria', ['Vienna']],
                ['by', 'Belarus', ['Minsk']], ['be', 'Belgium', ['Brussels']],
                ['ba', 'Bosnia and Herzegovina', ['Sarajevo'], ['bosnia']], ['bg', 'Bulgaria', ['Sofia']],
                ['hr', 'Croatia', ['Zagreb']], ['cy', 'Cyprus', ['Nicosia']],
                ['cz', 'Czechia', ['Prague'], ['czech republic']], ['dk', 'Denmark', ['Copenhagen']],
                ['ee', 'Estonia', ['Tallinn']], ['fi', 'Finland', ['Helsinki']], ['fr', 'France', ['Paris']],
                ['de', 'Germany', ['Berlin']], ['gr', 'Greece', ['Athens']], ['hu', 'Hungary', ['Budapest']],
                ['is', 'Iceland', ['Reykjavík']], ['ie', 'Ireland', ['Dublin']], ['it', 'Italy', ['Rome']],
                ['xk', 'Kosovo', ['Pristina']], ['lv', 'Latvia', ['Riga']], ['li', 'Liechtenstein', ['Vaduz']],
                ['lt', 'Lithuania', ['Vilnius']], ['lu', 'Luxembourg', ['Luxembourg']], ['mt', 'Malta', ['Valletta']],
                ['md', 'Moldova', ['Chișinău']], ['mc', 'Monaco', ['Monaco']], ['me', 'Montenegro', ['Podgorica']],
                ['nl', 'Netherlands', ['Amsterdam', 'The Hague'], ['holland']],
                ['mk', 'North Macedonia', ['Skopje'], ['macedonia']], ['no', 'Norway', ['Oslo']], ['pl', 'Poland', ['Warsaw']],
                ['pt', 'Portugal', ['Lisbon']], ['ro', 'Romania', ['Bucharest']],
                ['ru', 'Russia', ['Moscow'], ['russian federation']], ['sm', 'San Marino', ['San Marino']],
                ['rs', 'Serbia', ['Belgrade']], ['sk', 'Slovakia', ['Bratislava']], ['si', 'Slovenia', ['Ljubljana']],
                ['es', 'Spain', ['Madrid']], ['se', 'Sweden', ['Stockholm']], ['ch', 'Switzerland', ['Bern']],
                ['ua', 'Ukraine', ['Kyiv']],
                ['gb', 'United Kingdom', ['London'], ['uk', 'britain', 'great britain']],
                ['va', 'Vatican City', ['Vatican City'], ['vatican', 'holy see']],
            ]],
            [Continent::NorthAmerica, [
                ['ag', 'Antigua and Barbuda', ["Saint John's"], ['antigua']], ['bs', 'Bahamas', ['Nassau']],
                ['bb', 'Barbados', ['Bridgetown']], ['bz', 'Belize', ['Belmopan']], ['ca', 'Canada', ['Ottawa']],
                ['cr', 'Costa Rica', ['San José']], ['cu', 'Cuba', ['Havana']], ['dm', 'Dominica', ['Roseau']],
                ['do', 'Dominican Republic', ['Santo Domingo']], ['sv', 'El Salvador', ['San Salvador']],
                ['gd', 'Grenada', ["Saint George's"]], ['gt', 'Guatemala', ['Guatemala City']],
                ['ht', 'Haiti', ['Port-au-Prince']], ['hn', 'Honduras', ['Tegucigalpa']], ['jm', 'Jamaica', ['Kingston']],
                ['mx', 'Mexico', ['Mexico City']], ['ni', 'Nicaragua', ['Managua']], ['pa', 'Panama', ['Panama City']],
                ['kn', 'Saint Kitts and Nevis', ['Basseterre'], ['st kitts and nevis', 'st kitts']],
                ['lc', 'Saint Lucia', ['Castries'], ['st lucia']],
                ['vc', 'Saint Vincent and the Grenadines', ['Kingstown'], ['st vincent and the grenadines', 'st vincent']],
                ['tt', 'Trinidad and Tobago', ['Port of Spain'], ['trinidad']],
                ['us', 'United States', ['Washington, D.C.'], ['usa', 'us', 'america', 'united states of america']],
            ]],
            [Continent::SouthAmerica, [
                ['ar', 'Argentina', ['Buenos Aires']], ['bo', 'Bolivia', ['Sucre', 'La Paz']], ['br', 'Brazil', ['Brasília']],
                ['cl', 'Chile', ['Santiago']], ['co', 'Colombia', ['Bogotá']], ['ec', 'Ecuador', ['Quito']],
                ['gy', 'Guyana', ['Georgetown']], ['py', 'Paraguay', ['Asunción']], ['pe', 'Peru', ['Lima']],
                ['sr', 'Suriname', ['Paramaribo']], ['uy', 'Uruguay', ['Montevideo']], ['ve', 'Venezuela', ['Caracas']],
            ]],
            [Continent::Oceania, [
                ['au', 'Australia', ['Canberra']], ['fj', 'Fiji', ['Suva']], ['ki', 'Kiribati', ['South Tarawa']],
                ['mh', 'Marshall Islands', ['Majuro']], ['fm', 'Micronesia', ['Palikir']],
                // Nauru names no capital in law; parliament and the ministries
                // sit in Yaren, which is what every atlas prints.
                ['nr', 'Nauru', ['Yaren']], ['nz', 'New Zealand', ['Wellington']], ['pw', 'Palau', ['Ngerulmud']],
                ['pg', 'Papua New Guinea', ['Port Moresby']], ['ws', 'Samoa', ['Apia']],
                ['sb', 'Solomon Islands', ['Honiara']], ['to', 'Tonga', ["Nuku'alofa"]], ['tv', 'Tuvalu', ['Funafuti']],
                ['vu', 'Vanuatu', ['Port Vila']],
            ]],
        ];

        $cache = [];
        foreach ($groups as [$continent, $rows]) {
            foreach ($rows as $c) {
                $cache[] = new self($c[0], $c[1], $continent, $c[2] ?? [], $c[3] ?? []);
            }
        }
        usort($cache, static fn(self $a, self $b) => strcmp($a->name, $b->name));
        return $cache;
    }
}
