<?php

declare(strict_types=1);

namespace App\Form\Manager\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\OperationType;
use App\Entity\Wallet\Outsider;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Form\FormInterface;

/**
 * OperationManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class OperationManager implements OperationInterface
{
    private const PARTIAL_MATCH_KEYS = [
        'combepine',
        'assurance-habitation',
        'bouygues-telecom',
        'engie',
        'tabac',
        'pathe-cinepass-pathe-cinepass',
        'interets-debiteurs',
    ];

    private const CORRESPONDENCE = [
        '2m-annecy' => 'expenses-various-tobacco', // 2M ANNECY
        'a-r-e-a-npv-sqf3' => 'expenses-motorway', // A.R.E.A. NPV-SQF3
        'airportparking-swis' => 'expenses-motorway', // AIRPORTPARKING (SWIS
        'ale-hop-porto' => 'expenses-various-tobacco', // ALE-HOP PORTO
        'apple-store' => 'expenses-leisure-activities-computer',
        'aprr-autoroute-ville' => 'expenses-motorway', // APRR AUTOROUTE VILLE
        'aprr-la-boisse-st-ap' => 'expenses-motorway', // APRR LA BOISSE ST AP
        'aprr-st-martin-du-fr' => 'expenses-motorway', // APRR ST MARTIN-DU-FR
        'area-seynod-sud-bron' => 'expenses-motorway', // AREA SEYNOD SUD BRON
        'area-st-martin-belle' => 'expenses-motorway', // AREA ST MARTIN BELLE
        'area-st-quentin-fal' => 'expenses-motorway', // AREA ST QUENTIN FAL.
        'area-ste-helene-barr' => 'expenses-motorway', // AREA STE HELENE BARR
        'artic-annecy' => 'expenses-leisure-activities-outings', // ARTIC ANNECY
        'atmb-bonneville-oues' => 'expenses-motorway', // ATMB BONNEVILLE OUES
        'atmb-cluses-bonnevil' => 'expenses-motorway', // ATMB CLUSES BONNEVIL
        'atmosphair-annecy' => 'expenses-life-hairdressers', // ATMOSPHAIR ANNECY
        'au-bavarois-annecy' => 'expenses-leisure-activities-outings', // AU BAVAROIS ANNECY
        'auchan-annecy-epagny' => 'expenses-life-food', // AUCHAN ANNECY EPAGNY
        'auchan-dac-253-annec' => 'expenses-life-food', // AUCHAN DAC 253 ANNEC
        'auchan-dac-epagny-me' => 'expenses-life-food', // AUCHAN DAC EPAGNY ME
        'auguste-pralognan-la' => 'expenses-leisure-activities-vacation', // AUGUSTE PRALOGNAN LA
        'autoroute-blanche-74' => 'expenses-motorway', // AUTOROUTE BLANCHE 74
        'avoir-carte-le-cabanon' => 'expenses-leisure-activities-outings', // AVOIR CARTE LE CABANON
        'avoir-carte-paypal-ovh' => 'expenses-various-subscriptions', // AVOIR CARTE PAYPAL *OVH
        'avoir-carte-peclet-polset' => 'expenses-leisure-activities-vacation', // AVOIR CARTE PECLET POLSET
        'back-market' => 'expenses-phone-cell-phone',
        'barber-papa-annecy' => 'expenses-life-hairdressers', // BARBER PAPA ANNECY
        'bazar-gift-shopping' => 'expenses-various-gifts', // BAZAR GIFT SHOPPING
        'beer-e-co-lagos' => 'expenses-leisure-activities-vacation', // BEER E CO LAGOS
        'bellevue-69-bron-ced' => 'expenses-other', // BELLEVUE 69 BRON CED
        'bestfood-74-annecy' => 'expenses-restaurants', // BESTFOOD 74 ANNECY
        'bhv-bordeaux' => 'expenses-leisure-activities-vacation', // BHV BORDEAUX
        'billabong-store-anne' => 'expenses-life-clothing', // BILLABONG STORE ANNE
        'boulanger' => 'expenses-leisure-activities-computer',
        'boulangerie-patisse' => 'expenses-life-food', // BOULANGERIE PATISSE
        'bouygues-teleco-epag' => 'expenses-phone-cell-phone', // BOUYGUES TELECO EPAG
        'brasserie-du-pa-anne' => 'expenses-leisure-activities-outings', // BRASSERIE DU PA ANNE
        'brin-de-terroir-vaun' => 'expenses-leisure-activities-vacation', // BRIN DE TERROIR VAUN
        'buffalo-grill-epagny' => 'expenses-restaurants', // BUFFALO GRILL EPAGNY
        'c-p-annecy' => 'C&P ANNECY', // C&P ANNECY
        'cabaia-annecy' => 'expenses-various-gifts', // CABAIA ANNECY
        'cafe-de-la-ville-ann' => 'expenses-leisure-activities-outings', // CAFE DE LA VILLE ANN
        'camp-beauregard-mor' => 'expenses-leisure-activities-vacation', // CAMP. BEAUREGARD MOR
        'canal-plus-fr-issy-l' => 'expenses-various-subscriptions', // CANAL PLUS FR ISSY L
        'carrefour-city-annec' => 'expenses-life-food', // CARREFOUR CITY ANNEC
        'carrefour-market-mor' => 'expenses-life-food', // CARREFOUR MARKET MOR
        'casa-deolinda-porto' => 'expenses-leisure-activities-vacation', // CASA DEOLINDA PORTO
        'casa-raphaela-lyon-6' => 'expenses-leisure-activities-vacation', // CASA RAPHAELA LYON 6
        'casa-tabacpress-anne' => 'expenses-various-tobacco', // CASA TABACPRESS ANNE
        'tabac' => 'expenses-various-tobacco', // Tabac
        'cave-de-la-poste-la' => 'expenses-leisure-activities-vacation', // CAVE DE LA POSTE LA
        'certas-essof100-anne' => 'CERTAS ESSOF100 ANNE', // CERTAS ESSOF100 ANNE
        'chaclema-annecy' => 'expenses-life-food', // CHACLEMA ANNECY
        'cheque-emis-665370000000000' => '', // CHEQUE EMIS 665370000000000
        'chevallier-annecy' => 'expenses-life-food', // CHEVALLIER ANNECY
        'chez-ingalls-annecy' => 'expenses-restaurants', // CHEZ INGALLS ANNECY
        'chez-jean-annecy' => 'expenses-various-tobacco', // CHEZ JEAN ANNECY
        'chez-pen-annecy' => 'expenses-leisure-activities-outings',
        'class-croute-annec' => 'expenses-life-food', // CLASS ' CROUTE ANNEC
        'clic-and-walk' => 'expenses-other',
        'continente-lagos-far' => 'expenses-leisure-activities-vacation', // CONTINENTE LAGOS FAR
        'cotisation-fourniture-carte-debit' => 'expenses-various-banking', // COTISATION FOURNITURE CARTE DEBIT
        'cotisation-offre-compte-a-composer' => 'expenses-various-banking', // COTISATION OFFRE COMPTE A COMPOSER
        'd-part-france-geneve' => 'expenses-leisure-activities-vacation', // D{PART FRANCE GENEVE
        'dac-agip-9094-annecy' => 'expenses-automobile-fuel', // DAC AGIP 9094 ANNECY
        'dangreaux-annecy' => 'expenses-life-food', // DANGREAUX ANNECY
        'decat-2531-annecy' => 'expenses-leisure-activities-sport', // DECAT 2531 ANNECY
        'decathlon-bordea-035' => 'expenses-leisure-activities-sport', // DECATHLON BORDEA 035
        'decathlon-bordea-340' => 'expenses-leisure-activities-sport', // DECATHLON BORDEA 340
        'decathlon-epagny-005' => 'expenses-leisure-activities-sport', // DECATHLON EPAGNY 005
        'decitre-annecy' => 'expenses-various-gifts', // DECITRE ANNECY
        'dmb-concept-annecy' => 'expenses-restaurants', // DMB CONCEPT ANNECY
        'dola-dyana-souvenirs' => 'expenses-leisure-activities-vacation', // DOLA DYANA SOUVENIRS
        'easyjet-i-manchester' => 'expenses-leisure-activities-vacation', // EASYJET I MANCHESTER
        'easyjet000k9b2fxx-ge' => 'expenses-leisure-activities-vacation', // EASYJET000K9B2FXX GE
        'ekosport-epagny-metz' => 'expenses-leisure-activities-sport', // EKOSPORT EPAGNY METZ
        'enidac9543-mionnay' => 'expenses-leisure-activities-vacation', // ENIDAC9543 MIONNAY
        'ernest-la-ciotat' => 'expenses-leisure-activities-vacation', // ERNEST LA CIOTAT
        'faguo-annecy' => 'expenses-life-clothing', // FAGUO ANNECY
        'fao-8970-lisboa' => 'expenses-leisure-activities-vacation', // FAO 8970 LISBOA
        'fnac-annecy' => 'expenses-other', // FNAC ANNECY
        'frankos-annecy' => 'expenses-life-food', // FRANKOS ANNECY
        'fruit-augustin-annec' => 'expenses-life-food', // FRUIT.AUGUSTIN ANNEC
        'golf-miniature-annec' => 'expenses-leisure-activities-outings', // GOLF MINIATURE ANNEC
        'gran-baita-hotel-c' => 'expenses-leisure-activities-vacation', // GRAN BAITA HOTEL # C
        'grass-royale-annecy' => 'expenses-leisure-activities-outings', // GRASS ROYALE ANNECY
        'grd-annecy-eau-74-an' => 'expenses-housing-water-consumption', // GRD ANNECY EAU 74 AN
        'grepon-vl-ca-74-cham' => 'expenses-leisure-activities-vacation', // GREPON VL CA 74 CHAM
        'havana-boutique-anne' => 'expenses-various-gifts', // HAVANA BOUTIQUE ANNE
        'hema-bordeaux-ste-ca' => 'expenses-other', // HEMA BORDEAUX STE CA
        'hema-ev2102-annecy' => 'expenses-other', // HEMA EV2102 ANNECY
        'hetm023-annecy' => 'expenses-life-clothing', // HETM023 ANNECY
        'higalik-telemark-les' => 'expenses-leisure-activities-vacation', // HIGALIK TELEMARK LES
        'horodateurs-cb-2-74' => 'expenses-parking', // HORODATEURS CB 2 74
        'indigo-130202-la-ci0' => 'expenses-leisure-activities-vacation', // INDIGO 130202 LA CI0
        'intermarche' => 'expenses-life-food',
        'interets-crediteurs-meythet' => '', // INTERETS CREDITEURS MEYTHET
        'interm-calao-136-74' => 'expenses-life-food', // INTERM CALAO 136 74
        'interm-calao-138-74' => 'expenses-life-food', // INTERM CALAO 138 74
        'l-agora-cran-gevrier' => 'expenses-leisure-activities-outings', // L'AGORA CRAN GEVRIER
        'l-atelier-cafe-chamo' => 'expenses-leisure-activities-vacation', // L ATELIER CAFE CHAMO
        'l-etage-annecy' => 'expenses-restaurants', // L' ETAGE ANNECY
        'la-boutique-de-m-la' => 'expenses-other', // LA BOUTIQUE DE M LA
        'la-maison-du-tabac-a' => 'expenses-various-tobacco', // LA MAISON DU TABAC A
        'la-paniere-aix-les-b' => 'expenses-life-food', // LA PANIERE AIX-LES-B
        'la-petite-maison-ann' => 'expenses-restaurants', // LA PETITE MAISON ANN
        'la-poste' => 'expenses-other',
        'la-remise-bordeaux' => 'expenses-leisure-activities-vacation', // LA REMISE BORDEAUX
        'la-sarrazine-annecy' => 'expenses-leisure-activities-outings', // LA SARRAZINE ANNECY
        'la-source-aix-les-ba' => 'expenses-leisure-activities-sport', // LA SOURCE AIX-LES-BA
        'la-symphonie-des-sa' => 'expenses-leisure-activities-outings', // LA SYMPHONIE DES SA
        'la-voglia-annecy' => 'expenses-restaurants', // LA VOGLIA ANNECY
        'le-batavia-annecy' => 'expenses-various-tobacco', // LE BATAVIA ANNECY
        'le-bon-coin' => 'expenses-other',
        'le-cabanon-annecy-le' => 'expenses-leisure-activities-outings', // LE CABANON ANNECY LE
        'le-cafe-de-la-place' => 'expenses-leisure-activities-outings', // LE CAFE DE LA PLACE
        'le-cafe-des-arts-ann' => 'expenses-leisure-activities-outings', // LE CAFE DES ARTS ANN
        'le-cellier-gen-ve-15' => 'expenses-leisure-activities-outings', // LE CELLIER GEN}VE 15
        'le-clocher-annecy' => 'expenses-leisure-activities-outings', // LE CLOCHER ANNECY
        'le-comptoir-du-pain' => 'expenses-life-food', // LE COMPTOIR DU PAIN
        'le-france-annecy' => 'expenses-various-tobacco', // LE FRANCE ANNECY
        'le-havane-annecy' => 'expenses-various-tobacco', // LE HAVANE ANNECY
        'le-melchristo-annecy' => 'expenses-various-tobacco', // LE MELCHRISTO ANNECY
        'le-millesime-duingt' => 'expenses-various-tobacco', // LE MILLESIME DUINGT
        'le-munich-annecy' => 'expenses-leisure-activities-outings', // LE MUNICH ANNECY
        'le-narval-annecy' => 'expenses-various-tobacco', // LE NARVAL. ANNECY
        'le-pacha-bordeaux' => 'expenses-various-tobacco', // LE PACHA BORDEAUX
        'le-petit-mousse-la-c' => 'expenses-leisure-activities-vacation', // LE PETIT MOUSSE LA C
        'le-relais-d-epagny' => 'expenses-restaurants', // LE RELAIS D'EPAGNY
        'le-repere-d-albi-ann' => 'expenses-restaurants', // LE REPERE D'ALBI ANN
        'le-setor-les-bellevi' => 'expenses-leisure-activities-vacation', // LE SETOR LES BELLEVI
        'le-st-georges-annecy' => 'expenses-leisure-activities-outings', // LE ST GEORGES ANNECY
        'le-stalingrad-bordea' => 'expenses-various-tobacco', // LE STALINGRAD BORDEA
        'le-tabac-d-elya-anne' => 'expenses-various-tobacco', // LE TABAC D ELYA ANNE
        'le-venitien-annecy' => 'expenses-various-tobacco', // LE VENITIEN ANNECY
        'leroy-merlin' => 'expenses-housing-do-it-yourself',
        'les-artistes-poisy' => 'expenses-leisure-activities-outings', // LES ARTISTES POISY
        'les-dugs-veyrier-du' => 'expenses-various-tobacco', // LES DUGS VEYRIER-DU-
        'les-eaux-bleues-anne' => 'expenses-automobile-vehicle-maintenance', // LES EAUX BLEUES ANNE
        'les-nemours-annecy' => 'expenses-cinema', // LES NEMOURS ANNECY
        'lidl-3853-seynod' => 'expenses-life-food', // LIDL 3853 SEYNOD
        'lidl-agradece-faro' => 'expenses-leisure-activities-vacation', // LIDL AGRADECE FARO
        'loj-bolinh-angelicai' => 'expenses-leisure-activities-vacation', // LOJ BOLINH ANGELICAI
        'lpvs-annecy' => 'expenses-housing-do-it-yourself', // LPVS ANNECY
        'ls-amnesie-annecy-an' => 'expenses-leisure-activities-outings', // LS AMNESIE ANNECY AN
        'ls-la-cave-annecy' => 'expenses-leisure-activities-outings', // LS LA CAVE ANNECY
        'lw-sncf-connect-pari' => 'expenses-leisure-activities-vacation', // LW-SNCF CONNECT PARI
        'mag-presse-meythet' => 'expenses-various-tobacco', // MAG PRESSE MEYTHET
        'maison-boho-talloire' => 'expenses-leisure-activities-outings', // MAISON BOHO TALLOIRE
        'masson-marie-la-clus' => 'expenses-leisure-activities-outings', // MASSON MARIE LA CLUS
        'maxicoffee-gc-daumes' => 'expenses-leisure-activities-vacation', // MAXICOFFEE GC DAUMES
        'melba-annecy-vins' => 'expenses-restaurants', // MELBA ANNECY - VINS
        'mevlana-annecy' => 'expenses-restaurants', // MEVLANA ANNECY
        'midget-annecy' => 'expenses-restaurants', // MIDGET ANNECY
        'millenium-faro' => 'expenses-leisure-activities-vacation', // MILLENIUM FARO
        'minipreco-expr-faro' => 'expenses-leisure-activities-vacation', // MINIPRECO EXPR FARO
        'mol-vignetteswitzerl' => 'expenses-motorway', // MOL*VIGNETTESWITZERL
        'monoprix-0332-annec2' => 'expenses-life-food', // MONOPRIX 0332 ANNEC2
        'monoprix-2814-annec2' => 'expenses-life-food', // MONOPRIX 2814 ANNEC2
        'montelimar-ouest-all' => 'expenses-leisure-activities-vacation', // MONTELIMAR OUEST ALL
        'montempo-lyon-ci' => 'expenses-leisure-activities-vacation', // MONTEMPO LYON CI
        'moxy-la-ciotat-foh' => 'expenses-leisure-activities-vacation', // MOXY LA CIOTAT FOH
        'mp-carrefour-dac-vl' => 'expenses-life-food', // MP*CARREFOUR DAC VL
        'ms-thesocialhub-por' => 'expenses-leisure-activities-vacation', // MS* THESOCIALHUB-POR
        'musiques-amplifi-ann' => 'expenses-concerts', // MUSIQUES AMPLIFI ANN
        'netflix-com-amsterda' => 'expenses-various-subscriptions', // NETFLIX.COM AMSTERDA
        'netflix-com-paris' => 'expenses-various-subscriptions', // NETFLIX COM PARIS
        'nyx-airservfrance-an' => 'expenses-automobile-vehicle-maintenance', // NYX*AIRSERVFRANCE AN
        'papelaria-america-86' => 'expenses-other', // PAPELARIA AMERICA 86
        'park-4-coperto-delle' => 'expenses-leisure-activities-vacation', // PARK 4 COPERTO DELLE
        'paul-ar-faro' => 'expenses-leisure-activities-vacation', // PAUL AR FARO
        'paypal-booking-book' => 'expenses-leisure-activities-vacation', // PAYPAL *BOOKING BOOK
        'paypal-booking-hote' => 'expenses-leisure-activities-vacation', // PAYPAL *BOOKING HOTE
        'paypal-bouyguestel' => 'expenses-phone-cell-phone', // PAYPAL *BOUYGUESTEL
        'paypal-deezer-luxem' => 'expenses-various-subscriptions', // PAYPAL *DEEZER LUXEM
        'paypal-github-inc-l' => 'expenses-various-subscriptions', // PAYPAL *GITHUB INC L
        'paypal-google-strav' => 'expenses-various-subscriptions', // PAYPAL *GOOGLE STRAV
        'paypal-jetbrainssr' => 'expenses-various-subscriptions', // PAYPAL *JETBRAINSSR
        'paypal-microsoft-lu' => 'expenses-various-subscriptions', // PAYPAL *MICROSOFT LU
        'paypal-ophelie-trav' => 'expenses-other', // PAYPAL *OPHELIE.TRAV
        'paypal-ovh-luxembou' => 'expenses-various-subscriptions', // PAYPAL *OVH LUXEMBOU
        'paypal-paiement-4x' => 'expenses-various-gifts', // PAYPAL *PAIEMENT 4X
        'paypal-resumaker-lu' => 'expenses-other', // PAYPAL *RESUMAKER LU
        'paypal-resumaker-re' => 'expenses-other', // PAYPAL *RESUMAKER RE
        'paypal-zwift-luxemb' => 'expenses-various-subscriptions', // PAYPAL *ZWIFT LUXEMB
        'pbb-annecy' => 'expenses-restaurants', // PBB ANNECY
        'peage-autoroute-allo' => 'expenses-motorway', // PEAGE AUTOROUTE ALLO
        'peage-autoroute-anne' => 'expenses-motorway', // PEAGE AUTOROUTE ANNE
        'peage-autoroute-rumi' => 'expenses-motorway', // PEAGE AUTOROUTE RUMI
        'peclet-polset-pralog' => 'expenses-leisure-activities-vacation', // PECLET POLSET PRALOG
        'pessey-magnifique-et' => 'expenses-life-food', // PESSEY MAGNIFIQUE ET
        'petit-coin-parad-vil' => 'expenses-leisure-activities-vacation', // PETIT COIN PARAD VIL
        'petro-belmont-annecy' => 'expenses-life-food', // PETRO BELMONT ANNECY
        'pharmacie-amandine-e' => 'expenses-pharmacy', // PHARMACIE AMANDINE E
        'pharmacie-chorus-cra' => 'expenses-pharmacy', // PHARMACIE CHORUS CRA
        'phie-carnot-sc-annec' => 'expenses-pharmacy', // PHIE CARNOT SC ANNEC
        'photomaton-echirolle' => 'expenses-other', // PHOTOMATON ECHIROLLE
        'picard-1101-annecy' => 'expenses-life-food', // PICARD 1101 ANNECY
        'picard-surgeles-seyn' => 'expenses-life-food', // PICARD SURGELES SEYN
        'pitaya-annecy' => 'expenses-restaurants', // PITAYA ANNECY
        'pizzeria-des-alpes-a' => 'expenses-restaurants', // PIZZERIA DES ALPES A
        'pk-bo-c-auto-sc-74-a' => 'expenses-automobile-vehicle-maintenance', // PK BO C.AUTO SC 74 A
        'plan-b-pralognan-la' => 'expenses-leisure-activities-vacation', // PLAN B PRALOGNAN LA
        'pleins-feux-bonnevil' => 'expenses-concerts', // PLEINS FEUX BONNEVIL
        'pmu' => 'expenses-various-tobacco',
        'porto-duty-free-walk' => 'expenses-leisure-activities-vacation', // PORTO DUTY FREE WALK
        'pousada-palaci-estoi' => 'expenses-leisure-activities-vacation', // POUSADA PALACI ESTOI
        'ppg-passion-sport-o' => 'expenses-leisure-activities-sport', // PPG*PASSION SPORT O
        'pralo-sports' => 'expenses-leisure-activities-sport', // PRALO SPORTS
        'combepine' => 'expenses-housing-rent', // VIREMENT EMIS VIR INST VERS NATHALIE COMBEPINE
        'assurance-habitation' => 'expenses-housing-home-insurance', // PRELEVEMENT CA DES SAVOIE CREDIT AGRICOLE ASSURANCE HABITATION
        'bouygues-telecom' => 'expenses-phone-phone-internet', // PRELEVEMENT BOUYGUES TELECOM
        'engie' => 'expenses-housing-energy', // PRELEVEMENT ENGIE S.A. MANDAT 00S017296083 00S017296083 FR03SYM002381 50004591297700051361141520250422
        'prelevement-frais-carte-etranger-hors-ue' => 'expenses-leisure-activities-vacation', // PRELEVEMENT FRAIS CARTE ETRANGER HORS UE
        'prelevement-frais-carte-ue-hors-zone-euro' => 'expenses-leisure-activities-vacation', // PRELEVEMENT FRAIS CARTE UE HORS ZONE EURO
        'prelevement-interets-debiteurs' => 'expenses-various-banking', // PRELEVEMENT INTERETS DEBITEURS
        'prelevement-izi-prlvfrx-ywfpb2jt514q5dmd-be39zzzcitd000000037-str16natep51ro5hsapphran1mamtj6nms' => 'expenses-other', // PRELEVEMENT IZI PRLVFRX YWFPB2JT514Q5DMD BE39ZZZCITD000000037 STR16NATEP51RO5HSAPPHRAN1MAMTJ6NMS
        'pathe-cinepass-pathe-cinepass' => 'expenses-various-subscriptions', // PRELEVEMENT PATHE CINEPASS PATHE CINEPASS
        'protiming-orleans' => 'expenses-leisure-activities-sport', // PROTIMING ORLEANS
        'queue-du-coq-annecy' => 'expenses-leisure-activities-outings', // QUEUE DU COQ ANNECY
        'quickmart-lagos-faro' => 'expenses-leisure-activities-vacation', // QUICKMART LAGOS FARO
        'quiosq-port-portugal' => 'expenses-leisure-activities-vacation', // QUIOSQ PORT PORTUGAL
        'ref-blanche-saint-ve' => 'expenses-leisure-activities-vacation', // REF BLANCHE SAINT VE
        'refuge-col-vanoise-p' => 'expenses-leisure-activities-vacation', // REFUGE COL VANOISE P
        'refuge-de-l-arpo-val' => 'expenses-leisure-activities-vacation', // REFUGE DE L ARPO VAL
        'refuge-fournache-aus' => 'expenses-leisure-activities-vacation', // REFUGE FOURNACHE AUS
        'refuge-lac-lou-les-b' => 'expenses-leisure-activities-vacation', // REFUGE LAC LOU LES B
        'relais-des-allueges' => 'expenses-leisure-activities-vacation', // RELAIS DES ALLUEGES
        'relax-hotel-maillat' => 'expenses-leisure-activities-vacation', // RELAX HOTEL MAILLAT
        'relay-merignac' => 'expenses-leisure-activities-vacation', // RELAY MERIGNAC
        'remise-de-cheque-0225596' => '', // REMISE DE CHEQUE 0225596
        'remise-de-cheque-1130284' => '', // REMISE DE CHEQUE 1130284
        'remise-de-cheque-1139186' => '', // REMISE DE CHEQUE 1139186
        'remise-de-cheque-5910099' => '', // REMISE DE CHEQUE 5910099
        'remise-de-cheque-5911117' => '', // REMISE DE CHEQUE 5911117
        'remise-de-cheque-9640573' => '', // REMISE DE CHEQUE 9640573
        'reso-social-mark-ann' => 'expenses-life-food', // RESO SOCIAL MARK ANN
        'rest-du-clocher-anne' => 'expenses-leisure-activities-outings', // REST DU CLOCHER ANNE
        'retrait-au-distributeur-annecy-16h50' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR ANNECY 16H50
        'retrait-au-distributeur-annecy-18h19' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR ANNECY 18H19
        'retrait-au-distributeur-annecy-22h00' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR ANNECY 22H00
        'retrait-au-distributeur-annecy-novel-1-09h55' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR ANNECY NOVEL 1 09H55
        'retrait-au-distributeur-cashzone-lisboa' => 'expenses-leisure-activities-vacation', // RETRAIT AU DISTRIBUTEUR CASHZONE LISBOA
        'retrait-au-distributeur-cran-gevrier-1-08h13' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR CRAN GEVRIER 1 08H13
        'retrait-au-distributeur-cran-gevrier-3-18h10' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR CRAN GEVRIER 3 18H10
        'retrait-au-distributeur-gab-2-annecy-n-19h18' => 'expenses-other-withdrawals', // RETRAIT AU DISTRIBUTEUR GAB 2 ANNECY N 19H18
        'retrait-au-distributeur-quiosque-infante-sag' => 'expenses-leisure-activities-vacation', // RETRAIT AU DISTRIBUTEUR QUIOSQUE INFANTE SAG
        'retrait-au-distributeur-r-comendador-vilarin' => 'expenses-leisure-activities-vacation', // RETRAIT AU DISTRIBUTEUR R COMENDADOR VILARIN
        'retrait-au-distributeur-r-sa-da-bandeira-226' => 'expenses-leisure-activities-vacation', // RETRAIT AU DISTRIBUTEUR R SA DA BANDEIRA,226
        'rose-dupont-chamonix' => 'expenses-leisure-activities-vacation', // ROSE DUPONT CHAMONIX
        'saint-charles-annecy' => 'expenses-leisure-activities-outings', // SAINT CHARLES ANNECY
        'sarl-boblafon-annecy' => 'expenses-restaurants', // SARL BOBLAFON ANNECY
        'sarl-bullit-annecy' => 'expenses-leisure-activities-outings', // SARL BULLIT ANNECY
        'sarl-chevallier-anne' => 'expenses-life-food', // SARL CHEVALLIER ANNE
        'sete-pedras-faro' => 'expenses-leisure-activities-vacation', // SETE PEDRAS FARO
        'sibra-bus-annec' => 'expenses-bus', // SIBRA BUS ANNEC
        'sivya-annecy' => 'expenses-various-tobacco', // SIVYA ANNECY
        'sklamp-annecy' => 'expenses-automobile-vehicle-maintenance', // SKLAMP ANNECY
        'smile-p-sarl-fleurs' => 'expenses-various-gifts', // SMILE&P*SARL FLEURS
        'snc-caf-inn-annecy' => 'expenses-various-tobacco', // SNC CAF'INN ANNECY
        'snc-suzanne-la-ciota' => 'expenses-leisure-activities-vacation', // SNC SUZANNE LA CIOTA
        'snp-ludocortex-annec' => 'expenses-various-gifts', // SNP*LUDOCORTEX ANNEC
        'snp-sarl-fleurs-et-j' => 'expenses-various-gifts', // SNP*SARL FLEURS ET J
        'spar-gd-bornand-le-g' => 'expenses-life-food', // SPAR GD BORNAND LE G
        'sport-ticketing-bida' => 'expenses-leisure-activities-sport', // SPORT TICKETING BIDA
        'sport-zone-lagos-far' => 'expenses-leisure-activities-sport', // SPORT ZONE LAGOS FAR
        'springfield-1781-ann' => 'expenses-leisure-activities-sport', // SPRINGFIELD 1781 ANN
        'starbucks-81186-rua' => 'expenses-leisure-activities-vacation', // STARBUCKS 81186 RUA
        'sumup-outdoor-01-ev' => 'expenses-leisure-activities-sport', // SUMUP *OUTDOOR 01 EV
        'sumup-seml-la-clusa' => 'expenses-leisure-activities-sport', // SUMUP *SEML LA CLUSA
        'sumup-tps-flowersa' => 'expenses-leisure-activities-sport', // SUMUP *TPS FLOWERSA
        'sumup-ultra-beaujol' => 'expenses-leisure-activities-sport', // SUMUP *ULTRA BEAUJOL
        'sushi-lac-annecy' => 'expenses-restaurants', // SUSHI LAC ANNECY
        'tabac-2j-annecy' => 'expenses-various-tobacco', // TABAC 2J ANNECY
        'tabac-de-barral-anne' => 'expenses-various-tobacco', // TABAC DE BARRAL ANNE
        'tabac-la-forteresse' => 'expenses-various-tobacco', // TABAC LA FORTERESSE
        'tabac-presse-talloi' => 'expenses-various-tobacco', // TABAC PRESSE TALLOI
        'tabac-teppes-annecy' => 'expenses-various-tobacco', // TABAC TEPPES ANNECY
        'tabacaria-natal-faro' => 'expenses-various-tobacco', // TABACARIA NATAL FARO
        'tabacchi-edicola-bov' => 'expenses-various-tobacco', // TABACCHI&EDICOLA BOV
        'total' => 'expenses-automobile-fuel',
        'terre-bleue-annecy' => 'expenses-leisure-activities-vacation', // TERRE BLEUE ANNECY
        'th-courmayeur' => 'expenses-leisure-activities-vacation', // TH COURMAYEUR
        'the-social-hub-porto' => 'expenses-leisure-activities-vacation', // THE SOCIAL HUB PORTO
        'ts-lyon-grolee' => 'expenses-other', // TS LYON GROLEE
        'uber-eats-help-uber' => 'expenses-restaurants', // UBER *EATS HELP.UBER
        'uep-utile-la-ciotat' => 'expenses-leisure-activities-vacation', // UEP*UTILE LA CIOTAT
        'virement-emis-vir-inst-vers-coinbase-ireland-l' => '', // VIREMENT EMIS VIR INST VERS COINBASE IRELAND L
        'virement-emis-vir-inst-vers-comune-di-gallipol-amende-gallipoli-ref-2dlj8z-z-2411114-2dlj8z-z-2411114' => 'expenses-leisure-activities-vacation', // VIREMENT EMIS VIR INST VERS COMUNE DI GALLIPOL AMENDE GALLIPOLI REF : 2DLJ8Z Z/2411114 2DLJ8Z Z/2411114
        'virement-emis-vir-inst-vers-guillaume-robert-portugal-a-la-bourre' => 'expenses-leisure-activities-vacation', // VIREMENT EMIS VIR INST VERS GUILLAUME ROBERT PORTUGAL A LA BOURRE
        'virement-emis-vir-inst-vers-laureen-gillet-anniversaire-mat-anniversaire-mat' => 'expenses-various-gifts', // VIREMENT EMIS VIR INST VERS LAUREEN GILLET ANNIVERSAIRE MAT ANNIVERSAIRE MAT
        'virement-emis-vir-inst-vers-m-bruyere-nicolas-remboursement-evg-ju' => 'expenses-leisure-activities-vacation', // VIREMENT EMIS VIR INST VERS M BRUYERE NICOLAS REMBOURSEMENT EVG JU
        'virement-emis-vir-inst-vers-magali-belmonte' => 'expenses-leisure-activities-vacation', // VIREMENT EMIS VIR INST VERS MAGALI BELMONTE
        'virement-emis-vir-inst-vers-magali-belmonte-billets-avion' => 'expenses-leisure-activities-vacation', // VIREMENT EMIS VIR INST VERS MAGALI BELMONTE BILLETS AVION
        'virement-emis-vir-inst-vers-magali-belmonte-vacances' => 'expenses-leisure-activities-vacation', // VIREMENT EMIS VIR INST VERS MAGALI BELMONTE VACANCES
        'virement-emis-web-bruyere-nicolas-mariage-mariage' => 'expenses-other', // VIREMENT EMIS WEB BRUYERE NICOLAS MARIAGE MARIAGE
        'virement-emis-web-fournier-chantal' => '', // VIREMENT EMIS WEB FOURNIER CHANTAL
        'virement-emis-web-fournier-sebastien' => '', // VIREMENT EMIS WEB FOURNIER SEBASTIEN
        'virement-emis-web-monsieur-fournier-sebast' => '', // VIREMENT EMIS WEB MONSIEUR FOURNIER SEBAST
        'virement-en-votre-faveur-de-fournier-chantal' => '', // VIREMENT EN VOTRE FAVEUR DE FOURNIER CHANTAL
        'virement-en-votre-faveur-de-fournier-sebastien' => '', // VIREMENT EN VOTRE FAVEUR DE FOURNIER SEBASTIEN
        'virement-en-votre-faveur-de-madame-fournier-chantal' => '', // VIREMENT EN VOTRE FAVEUR DE MADAME FOURNIER CHANTAL
        'virement-en-votre-faveur-de-monsieur-fournier-sebastien' => '', // VIREMENT EN VOTRE FAVEUR DE MONSIEUR FOURNIER SEBASTIEN
        'virement-en-votre-faveur-dgfip-finances-publiques-1p06700-remb-impot-revenus-222-74035683508789aci00419682-m-fournier-sebastien-1p067000074035683508789aci0041' => '', // VIREMENT EN VOTRE FAVEUR DGFIP FINANCES PUBLIQUES 1P06700 REMB IMPOT REVENUS 222 74035683508789ACI00419682 M FOURNIER SEBASTIEN 1P067000074035683508789ACI0041
        'salaire' => 'incomes-income-remuneration', // VIREMENT EN VOTRE FAVEUR FELIX MULTIMEDIA FELIX ANIMATION SALAIRE 032025331155972190000006
        'virement-en-votre-faveur-vir-inst-de-magali-belmonte-magali-belmonte' => '', // VIREMENT EN VOTRE FAVEUR VIR INST DE MAGALI BELMONTE MAGALI BELMONTE
        'virement-recu-franchise-interets-debiteurs' => '', // VIREMENT RECU FRANCHISE INTERETS DEBITEURS
        'vival-annecy' => 'expenses-life-food', // VIVAL ANNECY
        'www-planity-com-pari' => 'expenses-life-hairdressers', // WWW.PLANITY.COM PARI
    ];

    /**
     * Correspondance bénéficiaire normalisé => sous-catégorie.
     *
     * Clé : slug du nom de commerçant normalisé par la banque (dernier segment du
     * libellé Crédit Agricole), stable d'un export à l'autre contrairement au
     * libellé brut qui contient la référence de carte, la ville et la date.
     */
    private const MERCHANT_CORRESPONDENCE = [
        // Frais alimentaires
        'vival' => 'expenses-life-food',
        'monoprix' => 'expenses-life-food',
        'monop' => 'expenses-life-food',
        'picard' => 'expenses-life-food',
        'lidl' => 'expenses-life-food',
        'aldi' => 'expenses-life-food',
        'auchan' => 'expenses-life-food',
        'carrefour-city' => 'expenses-life-food',
        'carrefour-market' => 'expenses-life-food',
        'super-u' => 'expenses-life-food',
        'intermarche' => 'expenses-life-food',
        'e-leclerc' => 'expenses-life-food',
        'le-petit-casino' => 'expenses-life-food',
        'sherpa' => 'expenses-life-food', // Superette de station (Bourg-Saint-Maurice, Briancon)
        'biltoki-annecy' => 'expenses-life-food', // Halle gourmande du Haras
        'dab-distribution' => 'expenses-life-food', // Alimentation generale, Bourg-Saint-Maurice
        'boucherie-de-novel' => 'expenses-life-food',
        'le-comptoir-du-pain' => 'expenses-life-food',
        'au-pain-d-antan' => 'expenses-life-food',
        'fruit-augustin' => 'expenses-life-food',
        'maison-chevallier' => 'expenses-life-food',
        'sarl-dangreaux' => 'expenses-life-food',
        'frankos' => 'expenses-life-food',
        'la-toque-cuivree' => 'expenses-life-food',
        'maxicoffee' => 'expenses-life-food',

        // Tabac / presse
        '2m' => 'expenses-various-tobacco', // Bar-tabac-presse, 35 av de Cran Annecy
        'cafe-inn' => 'expenses-various-tobacco', // Cafe bar tabac, rue Carnot Annecy
        'casa' => 'expenses-various-tobacco', // CASA TABACPRESS
        'l-d-j-74' => 'expenses-various-tobacco', // SNC debit de tabac, Pringy
        'whip-et-vikky' => 'expenses-various-tobacco', // SNC debit de tabac, Annecy
        'mako' => 'expenses-various-tobacco', // Tabac presse de la Liberation, Bassens
        'le-melchristo' => 'expenses-various-tobacco',
        'tabac-le-central' => 'expenses-various-tobacco',
        'tabac-de-loverchy' => 'expenses-various-tobacco',
        'le-tabac-d-elya' => 'expenses-various-tobacco',
        'tabac-presse-des-clarines' => 'expenses-various-tobacco',
        'tabac-qi-helene' => 'expenses-various-tobacco',
        'la-civette-de-la-gare' => 'expenses-various-tobacco',
        'le-petit-vapoteur' => 'expenses-various-tobacco',
        'relais-h' => 'expenses-various-tobacco',
        'relay' => 'expenses-various-tobacco',

        // Restaurants
        'class-croute' => 'expenses-restaurants',
        'uber-eats' => 'expenses-restaurants',
        'mevlana-ii' => 'expenses-restaurants',
        'pizzeria-des-alpes' => 'expenses-restaurants',
        'sushi-lac' => 'expenses-restaurants',
        'la-symphonie-des-saveurs' => 'expenses-restaurants',
        'sainte-claire' => 'expenses-restaurants', // Le Sapaudia, pizzeria Annecy
        'le-repere-d-albigny' => 'expenses-restaurants', // SARL Le Pas Sage
        'oasis' => 'expenses-restaurants', // L'Oasis Sevrier
        'alpk' => 'expenses-restaurants', // Chalet de Roselend, Beaufort
        'neno' => 'expenses-restaurants', // Advena, Saint-Jean-de-Niost
        'le-chatillon' => 'expenses-restaurants',
        'restaurant-du-clocher' => 'expenses-restaurants',
        'o-galettes-de-sophie' => 'expenses-restaurants',
        'le-comptoir-du-palais' => 'expenses-restaurants',
        'brasserie-du-parc' => 'expenses-restaurants',
        'brasserie-bathieu' => 'expenses-restaurants',
        'au-bavarois' => 'expenses-restaurants',
        'le-batavia' => 'expenses-restaurants',
        'le-munich-annecy' => 'expenses-restaurants',
        'bar-des-halles' => 'expenses-restaurants',
        'eden-bar' => 'expenses-restaurants',
        'le-bivouac' => 'expenses-restaurants',
        'l-esplanade' => 'expenses-restaurants',
        'f-c-g-f' => 'expenses-restaurants', // Brasserie Le Bon Lieu, Annecy
        'les-terrasses-de-perouges' => 'expenses-restaurants',
        'hostellerie-de-perouges' => 'expenses-restaurants',
        'chez-jean-annecy-sncf' => 'expenses-restaurants',
        'newrest' => 'expenses-restaurants', // Newrest Wagons-Lits

        // Sorties
        'zihuatanejo' => 'expenses-leisure-activities-outings', // Le Cabanon, Annecy-le-Vieux
        'les-artistes' => 'expenses-leisure-activities-outings',
        'chez-pen' => 'expenses-leisure-activities-outings',
        'grass-royale' => 'expenses-leisure-activities-outings',
        'artmalte' => 'expenses-leisure-activities-outings', // Microbrasserie / bar a bieres
        'frerots' => 'expenses-leisure-activities-outings', // Society Bar, Annecy
        'wines-vibes' => 'expenses-leisure-activities-outings', // La Cave, Annecy
        'le-grand-cafe' => 'expenses-leisure-activities-outings',
        'soc-golf-miniature-imperial' => 'expenses-leisure-activities-outings',

        // Vacances / hebergement
        'garrigae' => 'expenses-leisure-activities-vacation', // Hotel 4* Caserne de Briancon
        'annecy-hostel' => 'expenses-leisure-activities-vacation',
        'hotel-amaya' => 'expenses-leisure-activities-vacation',
        'mih-belley' => 'expenses-leisure-activities-vacation', // MiHotel, hebergement touristique
        'l-angival' => 'expenses-leisure-activities-vacation', // Hotel-restaurant Bourg-Saint-Maurice
        'airbnb-hmjw9' => 'expenses-leisure-activities-vacation',
        'refuge-de-la-blanche' => 'expenses-leisure-activities-vacation',

        // Sport
        'decathlon' => 'expenses-leisure-activities-sport',
        'decathlon-lu' => 'expenses-leisure-activities-sport',
        'ekosport' => 'expenses-leisure-activities-sport',
        'njuko' => 'expenses-leisure-activities-sport', // Billetterie d'epreuves sportives
        'peyce' => 'expenses-leisure-activities-sport', // Miles Republic, inscriptions courses
        'zwift-luxemb' => 'expenses-leisure-activities-sport',
        'a-s-o' => 'expenses-leisure-activities-sport', // Amaury Sport Organisation
        'federation-francaise-d-athletisme' => 'expenses-leisure-activities-sport',
        'amer-sports-france' => 'expenses-leisure-activities-sport',
        'annecy-tenni' => 'expenses-leisure-activities-sport',
        'les-arcs-bourg-saint-maurice-tourisme' => 'expenses-leisure-activities-sport', // Centre nautique

        // Informatique
        'jetbrainssr' => 'expenses-leisure-activities-computer',
        'github-inc-l' => 'expenses-leisure-activities-computer',
        'microsoft-lu' => 'expenses-leisure-activities-computer',
        'ovh-luxembou' => 'expenses-leisure-activities-computer',
        'fnac' => 'expenses-leisure-activities-computer',

        // Abonnements / culture
        'netflix' => 'expenses-various-subscriptions',
        'canal' => 'expenses-various-subscriptions',
        'deezer-luxem' => 'expenses-various-subscriptions',
        'pathe' => 'expenses-cinema',
        'ass-musique-amplifiee-marquisats-annecy' => 'expenses-concerts', // Le Brise Glace
        'weezevent' => 'expenses-concerts',

        // Automobile
        'aprr' => 'expenses-motorway',
        'vinci-autoroutes' => 'expenses-motorway',
        'regie-bpnl' => 'expenses-motorway', // Peage peripherique nord de Lyon
        'vignette-suisse' => 'expenses-motorway',
        'ville-d-annecy' => 'expenses-parking', // Horodateurs
        'commune-de-bourg-saint-maurice' => 'expenses-parking', // Horodateurs
        'parking' => 'expenses-parking',
        'aig-caisse-auto' => 'expenses-parking', // Caisse automatique (terminal EP2, Suisse)
        'avia' => 'expenses-automobile-fuel',
        'auchan-carburant' => 'expenses-automobile-fuel',
        'carrefour-carburant' => 'expenses-automobile-fuel',
        'allopneus' => 'expenses-automobile-vehicle-maintenance',
        'lav-auto' => 'expenses-automobile-vehicle-maintenance',
        'thalia' => 'expenses-automobile-vehicle-maintenance', // Bubble Wash, lavage auto
        'sklamp' => 'expenses-automobile-vehicle-maintenance',

        // Deplacements
        'sncf' => 'expenses-various-travel',
        'navigo' => 'expenses-various-travel',
        'concessions-gares-france' => 'expenses-various-travel',
        'airserv-france' => 'expenses-various-travel',
        'sibra' => 'expenses-bus',

        // Sante / soins
        'pharmacie-chorus' => 'expenses-pharmacy',
        'pharmacie-carnot' => 'expenses-pharmacy',
        'planity' => 'expenses-life-hairdressers',
        'atmosphair' => 'expenses-life-hairdressers',

        // Logement
        'leroy-merlin' => 'expenses-housing-do-it-yourself',
        'engie-s-a' => 'expenses-housing-energy',
        'engie' => 'expenses-housing-energy',
        'nathalie-combepine' => 'expenses-housing-rent',

        // Telephonie
        'bouygues-telecom' => 'expenses-phone-phone-internet',

        // Divers
        'librairie-alpine-maison-heritier' => 'expenses-various-supplies',
        'leetchi' => 'expenses-various-gifts',
        'retrait-dab' => 'expenses-other-withdrawals',
        'offre-compte-a-composer' => 'expenses-various-banking',
        'cotisation-carte' => 'expenses-various-banking',
        'interets-debiteurs' => 'expenses-various-banking',
        'frais-carte-a-l-etranger-hors-ue' => 'expenses-various-banking',
        'frais-carte-etranger-hors-ue' => 'expenses-various-banking',

        // Revenus
        'felix-multimedia-felix-animation' => 'incomes-income-remuneration',
        'fournier-sebastien' => 'incomes-other-deposits',
        'remise-de-cheque' => 'incomes-other-deposits',
        'fournier-chantal' => 'incomes-refunds-miscellaneous-refunds',
        'voiture-madame-fournier-chanta' => 'incomes-refunds-miscellaneous-refunds',
        // Tiers apparaissant dans les deux sens : virements entre particuliers.
        'magali-belmonte' => [
            'expenses' => 'expenses-other',
            'incomes' => 'incomes-refunds-miscellaneous-refunds',
        ],
        'fournier-sebast' => [
            'expenses' => 'expenses-other',
            'incomes' => 'incomes-other-deposits',
        ],
        'dgfip-finances-publiques-1p' => 'incomes-refunds-miscellaneous-refunds',
        'interets-crediteurs' => 'incomes-income-financial-income',
        'franchise-interets-debiteurs' => 'incomes-income-financial-income',
    ];

    /**
     * Alias de beneficiaires : libelles que la banque n'a pas normalises
     * (segment « nom du commercant » absent) et qui designent un tiers deja connu.
     */
    private const MERCHANT_ALIASES = [
        'carrefourcitysco-bri' => 'Carrefour City',
        'canal-plus-fr-issy-l' => 'CANAL+',
        'bouyguestel' => 'Bouygues Telecom',
        'peage-autoroute-npv' => 'APRR',
        'pk-bo-c-auto-sc-74-a' => 'Parking',
        'aig-caisse-auto-ep2' => 'AIG Caisse Auto',
        'x9322-aig-caisse-auto-ep2-p' => 'Frais carte etranger hors UE',
        'gare-du-nord-v36-par' => 'Concessions Gares France',
        'airservfrance-an' => 'AirServ France',
        'fruit-augustin-annec' => 'Fruit Augustin',
        'artmalte-petite-anne' => 'Artmalte',
        'amer-sports-france-v' => 'Amer Sports France',
        'tabac-qi-helene-berc' => 'Tabac QI Helene',
        'mih-01300-belley' => 'MIH Belley',
        'vignetteswitzerl' => 'Vignette Suisse',
        'ls-la-cave-annecy' => 'Wines & Vibes',
        'la-cave-annecy' => 'Wines & Vibes',
        'brasserie-bathieu-la' => 'Brasserie Bathieu',
        'sklamp-annecy' => 'Sklamp',
        'la-symphonie-annecy' => 'La Symphonie Des Saveurs',
        'musiques-amplifi-ann' => 'Ass Musique Amplifiee Marquisats Annecy',
        'chevallier' => 'Maison Chevallier',
        'le-fournil-des-pommaries-au-pain-d-antan' => 'Au Pain D Antan',
        'paiement-4x' => 'PayPal Paiement 4X',
        'les-arcs-bourg-saint-maurice-tourisme-abt' => 'Les Arcs Bourg Saint Maurice Tourisme',
        'fourniture-d-une-carte-de-debit-international-a-debit-immediat' => 'Cotisation carte',
        'nathalie-combepine-loyer' => 'Nathalie COMBEPINE',
        'magali-belmonte-vacances' => 'Magali BELMONTE',
    ];

    /**
     * Deduction par mot-cle sur le libelle complet, appliquee lorsque ni le libelle
     * brut ni le beneficiaire normalise ne sont connus. L'ordre compte : le premier
     * motif rencontre gagne.
     */
    private const KEYWORD_CORRESPONDENCE = [
        'assurance-automobile' => 'expenses-automobile-car-insurance-and-taxes',
        'assurance-habitation' => 'expenses-housing-home-insurance',
        'horodateur' => 'expenses-parking',
        'peage' => 'expenses-motorway',
        'autoroute' => 'expenses-motorway',
        'pharmacie' => 'expenses-pharmacy',
        'civette' => 'expenses-various-tobacco',
        'tabac' => 'expenses-various-tobacco',
        'boulangerie' => 'expenses-life-food',
        'patisserie' => 'expenses-life-food',
        'fournil' => 'expenses-life-food',
        'boucherie' => 'expenses-life-food',
        'epicerie' => 'expenses-life-food',
        'alimentation' => 'expenses-life-food',
        'superette' => 'expenses-life-food',
        'supermarche' => 'expenses-life-food',
        'restaurant' => 'expenses-restaurants',
        'pizzeria' => 'expenses-restaurants',
        'brasserie' => 'expenses-restaurants',
        'auberge' => 'expenses-restaurants',
        'hostellerie' => 'expenses-restaurants',
        'hotel' => 'expenses-leisure-activities-vacation',
        'hostel' => 'expenses-leisure-activities-vacation',
        'camping' => 'expenses-leisure-activities-vacation',
        'coiffure' => 'expenses-life-hairdressers',
        'coiffeur' => 'expenses-life-hairdressers',
        'parking' => 'expenses-parking',
        'carburant' => 'expenses-automobile-fuel',
        'station-service' => 'expenses-automobile-fuel',
        'lavage' => 'expenses-automobile-vehicle-maintenance',
        'wash' => 'expenses-automobile-vehicle-maintenance',
        'cinema' => 'expenses-cinema',
        'librairie' => 'expenses-various-supplies',
        'sncf' => 'expenses-various-travel',
        'interets-debiteurs' => 'expenses-various-banking',
        'cotisation-carte' => 'expenses-various-banking',
        'frais-carte' => 'expenses-various-banking',
    ];

    /**
     * Sous-categorie par defaut selon la nature de l'operation, dernier recours.
     */
    private const NATURE_CORRESPONDENCE = [
        'withdrawal' => 'expenses-other-withdrawals',
        'bank_fee' => 'expenses-various-banking',
        'interest' => 'incomes-income-financial-income',
        'cheque_in' => 'incomes-other-deposits',
        'cheque_out' => 'expenses-other',
        'refund' => 'incomes-refunds-miscellaneous-refunds',
    ];

    private const DEFAULT_EXPENSE = 'expenses-other';
    private const DEFAULT_INCOME = 'incomes-other-income-to-be-categorized';

    /** Tolerance de comparaison sur les montants, en euros. */
    private const EPSILON = 0.005;

    /**
     * OperationManager constructor.
     */
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Importe un relevé de compte au format XLSX (export Crédit Agricole).
     *
     * L'import est idempotent : une opération déjà présente en base n'est pas
     * recréée, mais les doublons légitimes du relevé (même date, même libellé,
     * même montant) sont conservés — un rapprochement par comptage, et non par
     * simple existence, est effectué.
     *
     * @return array rapport d'exécution
     */
    public function import(?string $filename = null, ?Wallet $wallet = null, bool $dryRun = false): array
    {
        $report = [
            'file' => null,
            'wallet' => null,
            'rows' => 0,
            'parsed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'ignored' => 0,
            'outsiders' => 0,
            'categorized' => 0,
            'uncategorized' => [],
            'targetBalance' => null,
            'operationsSum' => null,
            'initialAmount' => null,
            'balance' => null,
            'errors' => [],
        ];

        $em = $this->coreLocator->em();
        $wallet = $wallet ?: $em->getRepository(Wallet::class)->findOneBy(['slug' => 'main-wallet']);

        if (!$wallet instanceof Wallet) {
            $report['errors'][] = 'Aucun compte cible : le wallet "main-wallet" est introuvable.';

            return $report;
        }

        $report['wallet'] = $wallet->getAdminName();
        $filename = $filename ?: $this->coreLocator->projectDir().'/bin/data/import/operations.xlsx';
        $filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filename);

        if (!is_file($filename)) {
            $report['errors'][] = sprintf('Fichier introuvable : %s', $filename);

            return $report;
        }

        $report['file'] = $filename;
        $rows = IOFactory::load($filename)->getActiveSheet()->toArray(null, true, true, true);

        $headerIndex = null;
        $targetBalance = null;
        foreach ($rows as $index => $row) {
            $columnA = trim((string) ($row['A'] ?? ''));
            $columnB = trim((string) ($row['B'] ?? ''));
            if (null === $targetBalance && preg_match('/^solde\s+au/iu', $columnB) && '' !== trim((string) ($row['C'] ?? ''))) {
                $targetBalance = $this->cleanAmount($row['C']);
            }
            if (null === $headerIndex && 'date' === mb_strtolower($columnA, 'UTF-8')
                && str_starts_with(mb_strtolower($columnB, 'UTF-8'), 'libell')) {
                $headerIndex = $index;
            }
        }

        if (null === $headerIndex) {
            $report['errors'][] = 'En-tête "Date / Libellé / Débit / Crédit" introuvable dans le fichier.';

            return $report;
        }

        $report['targetBalance'] = $targetBalance;
        $entries = $this->readEntries($rows, $headerIndex, $report);

        if ([] === $entries) {
            $report['errors'][] = 'Aucune opération exploitable dans le fichier.';

            return $report;
        }

        $operationTypeRepository = $em->getRepository(OperationType::class);
        $expensesType = $operationTypeRepository->findOneBy(['type' => 'expenses']);
        $incomesType = $operationTypeRepository->findOneBy(['type' => 'incomes']);
        $user = $this->coreLocator->user();

        $outsiders = [];
        foreach ($em->getRepository(Outsider::class)->findAll() as $outsider) {
            $outsiders[$outsider->getSlug()] = $outsider;
        }

        $subCategories = [];
        foreach ($em->getRepository(SubCategory::class)->findAll() as $subCategory) {
            $subCategories[$subCategory->getSlug()] = $subCategory;
        }

        $existing = $this->existingOperationCounts($wallet);
        $position = count($outsiders);
        $seen = [];

        foreach ($entries as $entry) {
            $key = $entry['key'];
            $seen[$key] = ($seen[$key] ?? 0) + 1;

            if ($seen[$key] <= ($existing[$key] ?? 0)) {
                ++$report['skipped'];
                continue;
            }

            $outsiderSlug = Urlizer::urlize($entry['merchant']);
            if (!isset($outsiders[$outsiderSlug])) {
                $outsider = new Outsider();
                $outsider->setAdminName($entry['merchant']);
                $outsider->setSlug($outsiderSlug);
                $outsider->setPosition(++$position);
                if ($user) {
                    $outsider->setCreatedBy($user);
                }
                $em->persist($outsider);
                $outsiders[$outsiderSlug] = $outsider;
                ++$report['outsiders'];
            }

            $operation = new Operation();
            $operation->setWallet($wallet);
            $operation->setDate($entry['date']);
            $operation->setAmount($entry['amount']);
            $operation->setAdminName($entry['label']);
            $operation->setSlug(Urlizer::urlize($entry['date']->format('Ymd').'-'.$entry['merchant']));
            $operation->setOperationType($entry['expense'] ? $expensesType : $incomesType);
            $operation->setOutsider($outsiders[$outsiderSlug]);
            if ($user) {
                $operation->setCreatedBy($user);
            }

            $subCategorySlug = $entry['subCategory'];
            if ($subCategorySlug && isset($subCategories[$subCategorySlug])) {
                $operation->setSubCategory($subCategories[$subCategorySlug]);
            }

            // Une affectation par défaut n'est pas une catégorisation : elle est
            // remontée dans le rapport pour être arbitrée à la main.
            if (in_array($subCategorySlug, [self::DEFAULT_EXPENSE, self::DEFAULT_INCOME], true)) {
                $report['uncategorized'][$entry['merchant']] = ($report['uncategorized'][$entry['merchant']] ?? 0) + 1;
            } else {
                ++$report['categorized'];
            }

            $em->persist($operation);
            ++$report['imported'];
        }

        if ($dryRun) {
            $em->clear();

            return $report;
        }

        $em->flush();

        $operationRepository = $em->getRepository(Operation::class);
        $operationsSum = $operationRepository->sumOperations($wallet);
        $report['operationsSum'] = round($operationsSum, 2);

        if (null !== $targetBalance) {
            $initialAmount = round($targetBalance - $operationsSum, 2);
            if (abs($initialAmount - (float) ($wallet->getInitialAmount() ?? 0.0)) >= self::EPSILON) {
                $wallet->setInitialAmount($initialAmount);
                $em->persist($wallet);
                $em->flush();
            }
        }

        $report['initialAmount'] = round((float) ($wallet->getInitialAmount() ?? 0.0), 2);
        $report['balance'] = round($operationRepository->sumBalance($wallet), 2);

        return $report;
    }

    /**
     * Lit et normalise les lignes d'opérations situées sous l'en-tête.
     */
    private function readEntries(array $rows, int|string $headerIndex, array &$report): array
    {
        $entries = [];
        $started = false;

        foreach ($rows as $index => $row) {
            if ($index === $headerIndex) {
                $started = true;
                continue;
            }
            if (!$started) {
                continue;
            }

            ++$report['rows'];
            $rawDate = $row['A'] ?? null;
            $label = trim(preg_replace('/\s+/u', ' ', (string) ($row['B'] ?? '')));

            if ('' === $label || null === $rawDate || '' === trim((string) $rawDate)) {
                ++$report['ignored'];
                continue;
            }

            $debit = $this->cleanAmount($row['C'] ?? null);
            $credit = $this->cleanAmount($row['D'] ?? null);

            if ($debit <= 0 && $credit <= 0) {
                ++$report['ignored'];
                continue;
            }

            $date = $this->parseDate($rawDate);
            if (!$date instanceof \DateTimeInterface) {
                ++$report['ignored'];
                $report['errors'][] = sprintf('Ligne %s : date illisible (%s).', (string) $index, (string) $rawDate);
                continue;
            }

            $isExpense = $debit > 0;
            $amount = round($isExpense ? $debit : $credit, 2);
            $parsed = $this->parseLabel($label);

            $entries[] = [
                'date' => $date,
                'label' => $label,
                'amount' => $amount,
                'expense' => $isExpense,
                'merchant' => $parsed['merchant'],
                'subCategory' => $this->resolveSubCategorySlug($label, $parsed, $isExpense),
                'key' => $this->operationKey($date, $amount, $isExpense, $label),
            ];
            ++$report['parsed'];
        }

        return $entries;
    }

    /**
     * Compte les opérations déjà présentes, par clé date/montant/sens/libellé.
     *
     * @return array<string, int>
     */
    private function existingOperationCounts(Wallet $wallet): array
    {
        $results = $this->coreLocator->em()->getRepository(Operation::class)
            ->createQueryBuilder('o')
            ->select('o.date AS date', 'o.amount AS amount', 'o.adminName AS adminName', 'ot.type AS type')
            ->leftJoin('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($results as $result) {
            if (!$result['date'] instanceof \DateTimeInterface) {
                continue;
            }
            $key = $this->operationKey(
                $result['date'],
                (float) $result['amount'],
                'expenses' === $result['type'],
                (string) $result['adminName']
            );
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Clé de rapprochement d'une opération.
     */
    private function operationKey(\DateTimeInterface $date, float $amount, bool $isExpense, string $label): string
    {
        return implode('|', [
            $date->format('Y-m-d'),
            number_format($amount, 2, '.', ''),
            $isExpense ? 'D' : 'C',
            mb_strtolower(trim(preg_replace('/\s+/u', ' ', $label)), 'UTF-8'),
        ]);
    }

    /**
     * Décompose un libellé Crédit Agricole en nature d'opération et bénéficiaire.
     *
     * Structure observée : « Nature - libellé brut [- nom normalisé du commerçant] ».
     * Le dernier segment, lorsqu'il existe, est le nom normalisé par la banque : il
     * est stable d'un relevé à l'autre, contrairement au libellé brut.
     *
     * @return array{nature: string, merchant: string, middle: string}
     */
    public function parseLabel(string $rawLabel): array
    {
        $label = trim(preg_replace('/\s+/u', ' ', $rawLabel));
        $parts = array_values(array_filter(
            array_map('trim', preg_split('/\s+-\s+/u', $label)),
            static fn (string $part): bool => '' !== $part
        ));

        $nature = $parts[0] ?? '';
        $key = mb_strtolower($nature, 'UTF-8');
        $count = count($parts);
        $middle = $this->stripLabelNoise($parts[1] ?? '');

        if (str_starts_with($key, 'paiement par carte')) {
            $last = $count >= 4 ? implode(' ', array_slice($parts, 2)) : ($parts[2] ?? '');
            if ('' !== $last && preg_match('/^paypal$/iu', $last) && str_contains($middle, '*')) {
                // Agrégateur de paiement : le commerçant réel est derrière l'astérisque.
                $merchant = trim(substr($middle, strpos($middle, '*') + 1));
            } elseif ('' !== $last) {
                $merchant = $last;
            } else {
                $merchant = str_contains($middle, '*')
                    ? trim(substr($middle, strpos($middle, '*') + 1))
                    : $middle;
            }

            return $this->finalizeParsed('card', $merchant, $middle);
        }

        if (str_starts_with($key, 'prelevement') || str_starts_with($key, 'prélèvement')) {
            $merchant = $parts[1] ?? '';
            if (preg_match('/^\d/', $merchant) && isset($parts[2])) {
                $merchant = $parts[2];
            }
            $merchant = trim(preg_replace('/\s*-?ECHEANCE.*$/ui', '', $merchant));
            $merchant = trim(preg_replace('/\s*\d{4,}.*$/u', '', $merchant));

            return $this->finalizeParsed('debit', '' !== $merchant ? $merchant : ($parts[1] ?? 'Prélèvement'), $middle);
        }

        if (str_starts_with($key, 'virement')) {
            $merchant = preg_replace('/^(WEB|VIR INST)\s+/ui', '', $middle);
            $merchant = preg_replace('/^(DE|VERS|POUR)\s+/ui', '', (string) $merchant);
            $merchant = preg_replace('/^(MONSIEUR|MADAME|MR|MME)\s+/ui', '', (string) $merchant);
            $merchant = trim(preg_replace('/\s*\d{5,}.*$/u', '', (string) $merchant));
            $nature = str_contains($key, 'emis') || str_contains($key, 'émis') ? 'transfer_out' : 'transfer_in';

            return $this->finalizeParsed($nature, $merchant, $middle);
        }

        if (str_starts_with($key, 'retrait')) {
            return $this->finalizeParsed('withdrawal', 'Retrait DAB', $middle);
        }

        if (str_starts_with($key, 'cheque emis') || str_starts_with($key, 'chèque emis')) {
            return $this->finalizeParsed('cheque_out', 'Chèque émis', $middle);
        }

        if (str_starts_with($key, 'remise de cheque') || str_starts_with($key, 'remise de chèque')) {
            return $this->finalizeParsed('cheque_in', 'Remise de chèque', $middle);
        }

        if (str_starts_with($key, 'cotisation')) {
            return $this->finalizeParsed('bank_fee', $parts[1] ?? 'Cotisation', $middle);
        }

        if (str_starts_with($key, 'avoir')) {
            $merchant = trim(preg_replace('/^CARTE\s+X?\d*\s*/ui', '', $middle));

            return $this->finalizeParsed('refund', $merchant, $middle);
        }

        if (str_starts_with($key, 'interets') || str_starts_with($key, 'intérêts')) {
            return $this->finalizeParsed('interest', $nature, $middle);
        }

        return $this->finalizeParsed('other', '' !== $middle ? $middle : $nature, $middle);
    }

    /**
     * Applique les alias de bénéficiaires et garantit un nom non vide.
     *
     * @return array{nature: string, merchant: string, middle: string}
     */
    private function finalizeParsed(string $nature, string $merchant, string $middle): array
    {
        $merchant = trim(preg_replace('/\s+/u', ' ', $merchant));
        $slug = (string) Urlizer::urlize($merchant);

        if (isset(self::MERCHANT_ALIASES[$slug])) {
            $merchant = self::MERCHANT_ALIASES[$slug];
        }

        if ('' === trim($merchant)) {
            $merchant = 'Non identifié';
        }

        return ['nature' => $nature, 'merchant' => $merchant, 'middle' => $middle];
    }

    /**
     * Retire d'un segment de libellé la référence de carte, la date et l'heure.
     */
    private function stripLabelNoise(string $value): string
    {
        $value = preg_replace('/^X\d{3,}\s*/u', '', $value);
        $value = preg_replace('/\s*\d{2}\/\d{2}\s*/u', ' ', (string) $value);
        $value = preg_replace('/\s*\d{1,2}H\d{2}\s*/ui', ' ', (string) $value);

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * Détermine la sous-catégorie d'une opération.
     *
     * Ordre de résolution : libellé brut connu, bénéficiaire normalisé connu,
     * correspondance partielle, déduction par mot-clé, défaut par nature. Le sens
     * de l'opération (débit/crédit) fait ensuite foi : une sous-catégorie de type
     * opposé est écartée.
     *
     * @param array{nature: string, merchant: string, middle: string} $parsed
     */
    public function resolveSubCategorySlug(string $rawLabel, array $parsed, bool $isExpense): ?string
    {
        $middleSlug = (string) Urlizer::urlize($parsed['middle']);
        $merchantSlug = (string) Urlizer::urlize($parsed['merchant']);
        $labelSlug = (string) Urlizer::urlize($rawLabel);
        $generic = [self::DEFAULT_EXPENSE, self::DEFAULT_INCOME];

        $byMerchant = $this->directedCorrespondence(self::MERCHANT_CORRESPONDENCE[$merchantSlug] ?? null, $isExpense);
        $byLabel = self::CORRESPONDENCE[$middleSlug] ?? null;
        $resolved = null;

        // Le libellé brut fait foi, sauf lorsqu'il ne porte qu'un classement
        // générique : une correspondance par bénéficiaire est alors plus précise.
        if (!empty($byLabel) && (!in_array($byLabel, $generic, true) || !$byMerchant)) {
            $resolved = $byLabel;
        } elseif ($byMerchant) {
            $resolved = $byMerchant;
        } elseif (!empty(self::CORRESPONDENCE[$merchantSlug])) {
            $resolved = self::CORRESPONDENCE[$merchantSlug];
        }

        if (!$resolved) {
            foreach (self::PARTIAL_MATCH_KEYS as $partialKey) {
                if (empty(self::CORRESPONDENCE[$partialKey])) {
                    continue;
                }
                if (str_contains($middleSlug, $partialKey) || str_contains($merchantSlug, $partialKey)) {
                    $resolved = self::CORRESPONDENCE[$partialKey];
                    break;
                }
            }
        }

        if (!$resolved) {
            foreach (self::KEYWORD_CORRESPONDENCE as $keyword => $subCategorySlug) {
                if (str_contains($labelSlug, $keyword)) {
                    $resolved = $subCategorySlug;
                    break;
                }
            }
        }

        // Le sens de l'opération prime : une sous-catégorie de type opposé fausserait
        // les statistiques par catégorie.
        $expected = $isExpense ? 'expenses-' : 'incomes-';

        if (!$resolved || !str_starts_with($resolved, $expected)) {
            $resolved = self::NATURE_CORRESPONDENCE[$parsed['nature']] ?? null;
        }

        if (!$resolved || !str_starts_with($resolved, $expected)) {
            $resolved = $isExpense ? self::DEFAULT_EXPENSE : self::DEFAULT_INCOME;
        }

        return $resolved;
    }

    /**
     * Résout une correspondance pouvant être définie par sens d'opération.
     *
     * Un même tiers peut apparaître au débit comme au crédit (virements entre
     * particuliers) : la table accepte alors ['expenses' => ..., 'incomes' => ...].
     */
    private function directedCorrespondence(string|array|null $correspondence, bool $isExpense): ?string
    {
        if (is_array($correspondence)) {
            $correspondence = $correspondence[$isExpense ? 'expenses' : 'incomes'] ?? null;
        }

        return !empty($correspondence) ? (string) $correspondence : null;
    }

    /**
     * Convertit la date d'une cellule en objet DateTime.
     *
     * Le format des exports bancaires français est jj/mm/aaaa : il est appliqué
     * strictement, sans heuristique sur la valeur du premier groupe.
     */
    private function parseDate(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return (new \DateTime($value->format('Y-m-d')))->setTime(0, 0);
        }

        if (is_numeric($value)) {
            // Numéro de série Excel.
            $timestamp = ((float) $value - 25569) * 86400;

            return (new \DateTime('@'.(int) $timestamp))
                ->setTimezone(new \DateTimeZone('Europe/Paris'))
                ->setTime(0, 0);
        }

        $raw = trim((string) $value);

        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $raw);
            $errors = \DateTime::getLastErrors();
            $hasError = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
            if ($date instanceof \DateTime && !$hasError) {
                return $date->setTime(0, 0);
            }
        }

        return null;
    }

    /**
     * Applique le bénéficiaire et la sous-catégorie déduits lors d'une saisie manuelle.
     */
    public function execute(Operation $operation, FormInterface $form): void
    {
        $adminName = $form->get('adminName')->getData();

        if ($adminName) {
            $parsed = $this->parseLabel((string) $adminName);
            $outsiderSlug = (string) Urlizer::urlize($parsed['merchant']);
            $outsiderRepository = $this->coreLocator->em()->getRepository(Outsider::class);
            $outsider = $outsiderRepository->findOneBy(['slug' => $outsiderSlug]);

            if (!$outsider) {
                $outsider = new Outsider();
                $outsider->setAdminName($parsed['merchant']);
                $outsider->setSlug($outsiderSlug);
                $outsider->setCreatedBy($this->coreLocator->user());
                $outsider->setPosition(count($outsiderRepository->findAll()) + 1);
                $this->coreLocator->em()->persist($outsider);
            }

            $operation->setOutsider($outsider);

            if (!$operation->getSubCategory()) {
                $isExpense = !$operation->getOperationType()
                    || 'expenses' === $operation->getOperationType()->getType();
                $subCategorySlug = $this->resolveSubCategorySlug((string) $adminName, $parsed, $isExpense);
                $subCategory = $subCategorySlug
                    ? $this->coreLocator->em()->getRepository(SubCategory::class)->findOneBy(['slug' => $subCategorySlug])
                    : null;
                if ($subCategory) {
                    $operation->setSubCategory($subCategory);
                }
            }
        }

        if ($operation->getSubCategory() && !$operation->getOperationType()) {
            $operationType = $this->coreLocator->em()->getRepository(OperationType::class)->findOneBy([
                'type' => $operation->getSubCategory()->getType(),
            ]);
            if ($operationType) {
                $operation->setOperationType($operationType);
            }
        }
    }

    /**
     * Convertit une valeur de cellule en montant.
     *
     * Les valeurs numériques natives sont retournées telles quelles ; l'analyse des
     * séparateurs n'est appliquée qu'aux chaînes, où elle est nécessaire.
     */
    private function cleanAmount(mixed $amount): float
    {
        if (null === $amount || '' === $amount) {
            return 0.0;
        }

        if (is_int($amount) || is_float($amount)) {
            return (float) $amount;
        }

        $value = preg_replace('/\s+|€|\x{00A0}|\x{202F}/u', '', (string) $amount);

        if (null === $value || '' === $value) {
            return 0.0;
        }

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            // Le dernier séparateur rencontré est le séparateur décimal.
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');
            $decimalSeparator = (false !== $lastDot && $lastDot > (int) $lastComma) ? '.' : ',';
            $value = str_replace('.' === $decimalSeparator ? ',' : '.', '', $value);
            if (',' === $decimalSeparator) {
                $value = str_replace(',', '.', $value);
            }
        } elseif ($hasComma) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif ($hasDot && substr_count($value, '.') > 1) {
            $value = str_replace('.', '', $value);
        }

        return (float) $value;
    }
}
