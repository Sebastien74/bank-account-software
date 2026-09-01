<?php

declare(strict_types=1);

namespace App\Service\Wallet;

/**
 * BeneficiaryResolver.
 *
 * Ramène un libellé bancaire au tiers qu'il désigne réellement.
 *
 * Un relevé ne nomme pas deux fois le même commerçant de la même façon : la
 * référence d'échéance d'un prélèvement change tous les mois, le numéro de
 * magasin varie d'une enseigne à l'autre, et les deux générations d'export du
 * Crédit Agricole ne produisent pas le même libellé. Rapprocher sur la chaîne
 * entière fabrique donc un tiers par ligne. La résolution se fait ici par
 * fragments de texte stables.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class BeneficiaryResolver
{
    /**
     * Fragments identifiant un tiers, quel que soit le bruit qui les entoure.
     *
     * Recherchés dans le libellé nettoyé, du plus long au plus court : le
     * fragment le plus spécifique gagne. « CA DES SAVOIE CREDIT AGRICOLE
     * ASSURANCE HABITATION » doit primer sur « CREDIT AGRICOLE ».
     *
     * @var array<string, array{0: string, 1: string|null}> fragment => [nom du tiers, sous-catégorie]
     */
    private const array SIGNATURES = [
        // Prélèvements d'assurance : la référence en tête change à chaque échéance.
        'CREDIT AGRICOLE ASSURANCE HABITATION' => ['Crédit Agricole - Assurance habitation', 'expenses-housing-home-insurance'],
        'CREDIT AGRICOLE ASSURANCE AUTOMOBILE' => ['Crédit Agricole - Assurance automobile', 'expenses-automobile-car-insurance-and-taxes'],
        'ASSURANCE HABITATION' => ['Crédit Agricole - Assurance habitation', 'expenses-housing-home-insurance'],
        'ASSURANCE AUTOMOBILE' => ['Crédit Agricole - Assurance automobile', 'expenses-automobile-car-insurance-and-taxes'],
        'CONTRAT PACIFICA' => ['Crédit Agricole - Assurance', 'expenses-housing-home-insurance'],

        // Opérateurs et fournisseurs : mandat et référence client varient.
        'BOUYGUES TELECOM' => ['Bouygues Telecom', 'expenses-phone-phone-internet'],
        'BOUYGUESTEL' => ['Bouygues Telecom', 'expenses-phone-phone-internet'],
        'ENGIE' => ['ENGIE', 'expenses-housing-energy'],

        // Frais bancaires.
        'OFFRE COMPTE A COMPOSER' => ['Frais de tenue de compte', 'expenses-various-banking'],
        'COTISATION CARTE' => ['Cotisation carte bancaire', 'expenses-various-banking'],
        'FRAIS CARTE' => ['Frais carte à l\'étranger', 'expenses-various-banking'],
        'INTERETS DEBITEURS' => ['Intérêts débiteurs', 'expenses-various-banking'],
        'INTERETS CREDITEURS' => ['Intérêts créditeurs', 'incomes-income-financial-income'],
        'FRANCHISE INTERETS' => ['Intérêts créditeurs', 'incomes-income-financial-income'],
        'FOURNITURE D UNE CARTE' => ['Cotisation carte bancaire', 'expenses-various-banking'],

        // Revenus et virements réguliers.
        'FELIX MULTIMEDIA' => ['Felix Multimedia', 'incomes-income-remuneration'],
        'FELIX CREATION' => ['SARL Felix Creation', 'incomes-income-remuneration'],
        'FELIX ANIMATION' => ['Felix Multimedia', 'incomes-income-remuneration'],
        'DGFIP' => ['DGFIP Finances publiques', 'incomes-refunds-miscellaneous-refunds'],
        'COMBEPINE' => ['Nathalie Combepine', 'expenses-housing-rent'],

        // Agrégateurs de paiement : le commerçant réel suit l'astérisque, traité
        // avant d'arriver ici, mais le libellé résiduel doit rester rattachable.
        'PAIEMENT 4X' => ['PayPal - Paiement 4X', null],

        // Enseignes déclinées par magasin : le suffixe change, l'enseigne non.
        // Le fragment le plus long gagnant, « AUCHAN CARBU » prime sur « AUCHAN ».
        'AUCHAN CARBU' => ['Auchan Carburant', 'expenses-automobile-fuel'],
        'AUCHAN DAC' => ['Auchan Carburant', 'expenses-automobile-fuel'],
        'DAC AUCHAN' => ['Auchan Carburant', 'expenses-automobile-fuel'],
        'AUCHAN' => ['Auchan', 'expenses-life-food'],
        'CARREFOUR DAC' => ['Carrefour Carburant', 'expenses-automobile-fuel'],
        'CARREFOURCITY' => ['Carrefour City', 'expenses-life-food'],
        'CARREFOUR CITY' => ['Carrefour City', 'expenses-life-food'],
        'CARREFOUR MARKET' => ['Carrefour Market', 'expenses-life-food'],
        'MONOPRIX' => ['Monoprix', 'expenses-life-food'],
        'MONOP' => ['Monoprix', 'expenses-life-food'],
        'MAXICOFFEE' => ['MaxiCoffee', 'expenses-life-food'],
        'HEMA' => ['Hema', 'expenses-various-supplies'],
        'LIDL' => ['Lidl', 'expenses-life-food'],
        'DECATHLON' => ['Decathlon', 'expenses-leisure-activities-sport'],
        'DECAT ' => ['Decathlon', 'expenses-leisure-activities-sport'],
        'RELAY' => ['Relay', 'expenses-various-tobacco'],
        'PATHE' => ['Pathé', 'expenses-cinema'],
        'PECLET POLSET' => ['Peclet Polset', 'expenses-restaurants'],
        'SOCIAL HUB' => ['The Social Hub', 'expenses-leisure-activities-vacation'],
        'SOCIALHUB' => ['The Social Hub', 'expenses-leisure-activities-vacation'],
        'SYMPHONIE' => ['La Symphonie Des Saveurs', 'expenses-restaurants'],
        'PEAGE AUTOROUTE' => ['APRR', 'expenses-motorway'],

        // Concessionnaires d'autoroute : une barrière de péage par libellé, un
        // seul exploitant derrière. « AREAS PARIS » est une gare, pas une barrière :
        // le fragment le plus long le protège du rapprochement.
        'AREAS PARIS' => ['Concessions Gares France', 'expenses-various-travel'],
        'APRR' => ['APRR', 'expenses-motorway'],
        'AREA S' => ['APRR', 'expenses-motorway'],
        'ATMB' => ['ATMB', 'expenses-motorway'],

        // Enseignes en ligne facturées via un agrégateur.
        'BOOKING' => ['Booking.com', 'expenses-leisure-activities-vacation'],
        'OVH' => ['OVH', 'expenses-leisure-activities-computer'],
        'RESUMAKER' => ['Resumaker', 'expenses-various-subscriptions'],

        // Virements entre particuliers : le motif est accolé au nom du tiers.
        'MAGALI BELMONTE' => ['Magali Belmonte', null],
        'FOURNIER SEBAST' => ['Fournier Sébastien', null],
        'FOURNIER CHANTAL' => ['Fournier Chantal', null],
    ];

    /**
     * Correspondance apprise de l'export bancaire lui-même.
     *
     * L'export récent porte, pour chaque opération par carte, le libellé brut du
     * terminal et le nom du commerçant tel que la banque le normalise. Ces couples
     * sont relevés tels quels : ils permettent de rattacher les libellés bruts des
     * relevés antérieurs, qui ne portaient pas ce nom normalisé.
     *
     * @var array<string, string>
     */
    private const array LEARNED = [
        '2M ANNECY' => '2m',
        'A R E A NPV SQF3' => 'APRR',
        'ADVENA ST MAURICE DE' => 'Neno',
        'ALDI FRLYO064 SEVRIE' => 'Aldi',
        'ALLOPNEUS AIX EN PRO' => 'Allopneus',
        'ANNECY HOSTEL' => 'Annecy Hostel',
        'APRR AUTOROUTE' => 'APRR',
        'APRR CLERMONT BARRIE' => 'APRR',
        'AREA ANNECY CENTRE B' => 'APRR',
        'AREA ANNECY NORD BRO' => 'APRR',
        'AREA ST MARTIN BELLE' => 'APRR',
        'AREAS PARIS NOR' => 'Concessions Gares France',
        'ASF MUSSIDAN B VEDEN' => 'VINCI Autoroutes',
        'ASF ST ROMAIN POPEY' => 'VINCI Autoroutes',
        'ASF THENON VEDENE CE' => 'VINCI Autoroutes',
        'ASOFRANCE BOULOGNE B' => 'A.S.O',
        'ATMOSPHAIR ANNECY' => 'Atmosphair',
        'AU BAVAROIS ANNECY' => 'Au Bavarois',
        'AU PAIN D ANTAN ANNE' => 'Le Fournil Des Pommaries Au Pain D\'Antan',
        'AUCHAN ANNECY EPAGNY' => 'Auchan',
        'AUCHAN DAC 253 ANNEC' => 'Auchan Carburant',
        'AVIA JUVINCOURT ET D' => 'Avia',
        'AVIA MERLINES' => 'Avia',
        'BAR DE L ESPLANA MER' => 'L\'Esplanade',
        'BAR DES HALLES BOURG' => 'Bar Des Halles',
        'BELLEVUE 69 BRON CED' => 'APRR',
        'BILTOKI ANNECY ANGLE' => 'Biltoki Annecy',
        'BOUCHERIE DE NOVEL A' => 'Boucherie De Novel',
        'BOUYGUES TELECO EPAG' => 'Bouygues Telecom',
        'BOVAMI BOURG ST MAUR' => 'Intermarché',
        'BRASSERIE DU PA ANNE' => 'Brasserie Du Parc',
        'BUBBLE WASH EPAGNY M' => 'Thalia',
        'CANAL PLUS FR ISSY L' => 'CANAL+',
        'CARREFOUR MARKET SEV' => 'Carrefour Market',
        'CASA TABACPRESS ANNE' => 'Casa',
        'CENTRE NAUTIQUE 73 B' => 'Les Arcs Bourg Saint Maurice Tourisme (abt)',
        'CHACLEMA ANNECY' => 'Carrefour City',
        'CHALET DE ROSELE BEA' => 'Alpk',
        'CHEZ JEAN ANNECY' => 'Chez Jean Annecy Sncf',
        'CHEZ PEN ANNECY' => 'Chez Pen',
        'CLASS \' CROUTE ANNEC' => 'Class\'Croute',
        'CRUSEILLES BRON CEDE' => 'APRR',
        'DAB DISTRIBUTION BOU' => 'Dab Distribution',
        'DAC AUCHAN CARBU EPA' => 'Auchan Carburant',
        'DANGREAUX ANNECY' => 'Sarl Dangreaux',
        'DECAT ANNECY' => 'Decathlon',
        'DECATHLON 03424BOURG' => 'Decathlon',
        'DECATHLON ANNECY 253' => 'Decathlon',
        'DECATHLON BORDEA ANN' => 'Decathlon',
        'DECATHLON EPAGNY 005' => 'Decathlon',
        'E LECLERC VITRAC SUR' => 'E.Leclerc',
        'EKOSPORT EPAGNY METZ' => 'Ekosport',
        'EPICERIE LA GARG BRI' => 'Sherpa',
        'FF ATHLETISME PARIS' => 'Federation Francaise D\'Athletisme',
        'FNAC ANNECY' => 'Fnac',
        'FRANKOS ANNECY' => 'Frankos',
        'GARRIGAE CASERNE BRI' => 'Garrigae',
        'GARRIGAE CASERNE DE' => 'Garrigae',
        'GOLF MINIATURE ANNEC' => 'Soc Golf Miniature Imperial',
        'GRASS ROYALE ANNECY' => 'Grass Royale',
        'HORODATEURS BSM 73 B' => 'Commune De Bourg Saint Maurice',
        'HORODATEURS CB 2 74' => 'Ville d\'Annecy',
        'HOSTELLERIE PEROUGES' => 'Hostellerie De Perouges',
        'HOTEL AMAYA AMBARES' => 'Hotel Amaya',
        'INTERM CALAO 136 74' => 'Intermarché',
        'INTERMARCHE ANNECY' => 'Intermarché',
        'INTERMARCHE BRIANCON' => 'Intermarché',
        'INTERMARCHE S S LA T' => 'Intermarché',
        'L\'ANGIVAL BOURG SAIN' => 'L\'Angival',
        'L\'OASIS SEVRIER' => 'Oasis',
        'LA CAVE ANNECY' => 'Wines & Vibes',
        'LA SYMPHONIE DES SA' => 'La Symphonie Des Saveurs',
        'LDJ74 ANNECY' => 'L.D.J.74',
        'LE BATAVIA ANNECY' => 'Le Batavia',
        'LE BIVOUAC BRIANCON' => 'Le Bivouac',
        'LE BON LIEU ANNECY' => 'F.C.G.F.',
        'LE CAVANON ANNECY LE' => 'Zihuatanejo',
        'LE CHATILLON VIUZ LA' => 'Le Chatillon',
        'LE CLOCHER ANNECY' => 'Restaurant Du Clocher',
        'LE COMPTOIR DU PAIN' => 'Le Comptoir Du Pain',
        'LE GRAND CAFE ANNECY' => 'Le Grand Cafe',
        'LE MELCHRISTO ANNECY' => 'Le Melchristo',
        'LE PAS SAGE ANNECY' => 'Le Repere D\'Albigny',
        'LE PETIT CASINO ANNE' => 'Le Petit Casino',
        'LE SAPAUDIA ANNECY' => 'Sainte Claire',
        'LE TABAC D ELYA ANNE' => 'Le Tabac D\'Elya',
        'LEETCHI SA 75 PARIS' => 'Leetchi',
        'LEROY MERLIN EPAGNY' => 'Leroy Merlin',
        'LES ARTISTES POISY' => 'Les Artistes',
        'LIBRAIRIE ALPINE BRI' => 'Librairie Alpine Maison Heritier',
        'LIDL SEYNOD' => 'Lidl',
        'LIDL4364 EPAGNY METZ' => 'Lidl',
        'LPVS ANNECY' => 'Le Petit Vapoteur',
        'MAKO BASSENS' => 'Mako',
        'MAXICOFFEE ARA NEYRO' => 'MaxiCoffee',
        'MEVLANA ANNECY' => 'Mevlana Ii',
        'MILES REPUBLIC' => 'Peyce',
        'MONOP ANNECY' => 'Monop\'',
        'MONOPRIX ANNEC2' => 'Monoprix',
        'MONT BLANC ANNECY' => 'Chevallier',
        'MP CARREFOUR ANNECY' => 'Carrefour Carburant',
        'MP CARREFOUR DAC VL' => 'Carrefour Carburant',
        'MUSIQUES AMPLIFI ANN' => 'Ass Musique Amplifiee Marquisats Annecy',
        'NETFLIX COM AMSTERDA' => 'Netflix',
        'NETFLIX COM PARIS' => 'Netflix',
        'NEWREST WAGONS LITS' => 'Newrest',
        'O GALETTES DE SOPHI' => 'O Galettes De Sophie',
        'PATHE ANNECY' => 'Pathé',
        'PEAGE AUTOROUTE RUMI' => 'APRR',
        'PHARMACIE CHORUS CRA' => 'Pharmacie Chorus',
        'PHIE CARNOT SC ANNEC' => 'Pharmacie Carnot',
        'PICARD ANNECY' => 'Picard',
        'PICARD SURGELES SEYN' => 'Picard',
        'PIZZERIA DES ALPES A' => 'Pizzeria Des Alpes',
        'REF BLANCHE SAINT VE' => 'Refuge De La Blanche',
        'REGIE BPNL 69 CALUIR' => 'Régie BPNL',
        'RELAY PARIS' => 'Relay',
        'RESO SOCIAL MARK ANN' => 'Vival',
        'SARL CHEVALLIER ANNE' => 'Maison Chevallier',
        'SARL EDEN BAR BRIANC' => 'Eden Bar',
        'SARL SHADOW ANNECY' => 'Le Comptoir Du Palais',
        'SAVOY LAV\'AUTO ANNEC' => 'Lav\'Auto',
        'SERVICE NAVIGO PARIS' => 'Navigo',
        'SHERPA BOURG SAINT M' => 'Sherpa',
        'SIBRA BUS ANNEC' => 'Sibra',
        'SIVYA ANNECY' => 'La Civette De La Gare',
        'SNC CAF\'INN ANNECY' => 'Cafe\'Inn',
        'SNCF VOYAGEURS PARIS' => 'SNCF',
        'SOCIETY ANNECY' => 'Frerots',
        'SPORT TICKETING BIDA' => 'njuko',
        'STATION AVIA NERONDE' => 'Avia',
        'SUPER U ANNECY' => 'Super U',
        'SUSHI LAC ANNECY' => 'Sushi Lac',
        'TABAC 2J ANNECY' => 'Tabac Le Central',
        'TABAC DE LOVERC ANNE' => 'Tabac De Loverchy',
        'TABAC PRESSE DES CL' => 'Tabac Presse Des Clarines',
        'TERRASSES PEROUGES' => 'Les Terrasses De Perouges',
        'TICKET WEEZEVENT DIJ' => 'Weezevent',
        'TOQUE CUIVREE ARTIGU' => 'La Toque Cuivrée',
        'TRIB S BOUTIQUE BOUR' => 'Relais H',
        'UBER EATS HELP UBER' => 'Uber Eats',
        'UEP SUPER U BOURG SA' => 'Super U',
        'VIVAL ANNECY' => 'Vival',
        'WHIP ET VIKKY ANNECY' => 'Whip Et Vikky',
        'WWW PLANITY COM' => 'Planity',
        'WWW PLANITY COM PARI' => 'Planity',
    ];

    /**
     * Jetons de localité retirés en queue de libellé.
     *
     * Relevés sur les couples ci-dessus : la banque les supprime elle-même de ses
     * noms normalisés.
     */
    private const array TRAILING_NOISE = [
        'ANNECY', 'ANNE', 'ANNEC', 'EPAGNY', 'METZ', 'SEYNOD', 'POISY', 'CRAN', 'GEVRIER',
        'MEYTHET', 'SEVRIER', 'SEVRIE', 'PRINGY', 'BORDEAUX', 'BORDEA', 'PARIS', 'LYON',
        'BRIANCON', 'BRI', 'BOURG', 'BSM', 'PORTO', 'LISBOA', 'FARO', 'LAGOS', 'GENEVE',
        'CASERNE', 'LE', 'LA', 'SA', 'SC', 'EV', 'COM', 'FR', 'S', 'A', 'D', 'ET', 'EN',
    ];

    /**
     * Préfixes d'agrégateurs de paiement : le commerçant suit le séparateur.
     */
    private const array AGGREGATORS = ['PAYPAL', 'SUMUP', 'UEP', 'MOL', 'NYX', 'MP', 'MS', 'UBER'];

    /**
     * Nom canonique d'un tiers à partir du libellé complet de l'opération.
     *
     * @param string      $rawLabel  libellé bancaire intégral, pour les signatures
     * @param string      $labelCore segment portant le nom du commerçant
     * @param string|null $bankName  nom normalisé fourni par la banque, s'il existe
     * @param string      $fallback  nom retenu si aucune règle ne s'applique
     *
     * @return array{name: string, subCategory: string|null}
     */
    public function resolve(string $rawLabel, string $labelCore, ?string $bankName, string $fallback): array
    {
        $scrubbed = $this->scrub($rawLabel);

        // 1. Un fragment connu identifie le tiers sans ambiguïté.
        foreach ($this->signaturesByLength() as $fragment => $target) {
            if (str_contains($scrubbed, $fragment)) {
                return ['name' => $target[0], 'subCategory' => $target[1]];
            }
        }

        // 2. La banque a normalisé le nom : il fait foi.
        if ($bankName && '' !== trim($bankName)) {
            return ['name' => trim($bankName), 'subCategory' => null];
        }

        // 3. Le libellé brut a déjà été vu associé à un nom normalisé.
        $core = $this->core($labelCore);
        if (isset(self::LEARNED[$core])) {
            return ['name' => self::LEARNED[$core], 'subCategory' => null];
        }

        // 4. À défaut, le libellé nettoyé de son bruit, remis en casse lisible.
        $trimmed = $this->trimNoise($core);

        return ['name' => '' !== $trimmed ? $this->humanize($trimmed) : $fallback, 'subCategory' => null];
    }

    /**
     * Fragment de libellé porteur du nom, agrégateur de paiement résolu.
     */
    private function core(string $rawLabel): string
    {
        $segment = trim(preg_replace('/\s+/u', ' ', $rawLabel));

        // « PAYPAL *ZWIFT LUXEMB » désigne Zwift, pas PayPal.
        if (preg_match('/\b('.implode('|', self::AGGREGATORS).')\s*\*\s*(.+)$/iu', $segment, $matches)) {
            $segment = $matches[2];
        }

        return $this->scrub($segment);
    }

    /**
     * Retire références de carte, dates, heures et numéros de contrat.
     */
    public function scrub(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');
        $value = strtr($value, [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'Î' => 'I', 'Ï' => 'I', 'Ô' => 'O', 'Ö' => 'O', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ç' => 'C',
        ]);
        $value = preg_replace('/\bX\d{3,}\b/', ' ', $value);
        $value = preg_replace('/\b\d{1,2}\/\d{1,2}(\/\d{2,4})?\b/', ' ', $value);
        $value = preg_replace('/\b\d{1,2}H\d{2}\b/', ' ', $value);
        $value = preg_replace('/\b\d{4,}\b/', ' ', $value);
        $value = preg_replace('/[^A-Z0-9&\' ]+/', ' ', (string) $value);

        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    /**
     * Supprime les jetons de localité en fin de libellé.
     */
    private function trimNoise(string $value): string
    {
        $tokens = array_filter(explode(' ', $value));

        while (count($tokens) > 1) {
            $last = (string) end($tokens);
            if (!in_array($last, self::TRAILING_NOISE, true) && !preg_match('/^\d{1,3}$/', $last)) {
                break;
            }
            array_pop($tokens);
        }

        return implode(' ', $tokens);
    }

    /**
     * Remet un libellé tout en capitales dans une casse lisible.
     */
    private function humanize(string $value): string
    {
        $words = array_map(
            static fn (string $word): string => mb_strlen($word) <= 3 && $word === mb_strtoupper($word, 'UTF-8') && !preg_match('/^(LE|LA|LES|DE|DU|DES|AU|AUX|ET)$/', $word)
                ? $word
                : mb_convert_case(mb_strtolower($word, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'),
            explode(' ', $value)
        );

        return implode(' ', $words);
    }

    /**
     * Signatures triées du fragment le plus long au plus court.
     *
     * @return array<string, array{0: string, 1: string|null}>
     */
    private function signaturesByLength(): array
    {
        static $sorted = null;

        if (null === $sorted) {
            $sorted = self::SIGNATURES;
            uksort($sorted, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        }

        return $sorted;
    }
}
