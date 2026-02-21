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
        'prelevement-ca-des-savoie-credit-agricole-assurance-habitation' => 'expenses-housing-home-insurance', // PRELEVEMENT CA DES SAVOIE CREDIT AGRICOLE ASSURANCE HABITATION
        'prelevement-bouygues-telecom' => 'expenses-phone-phone-internet', // PRELEVEMENT BOUYGUES TELECOM
        'prelevement-engie' => 'expenses-housing-energy', // PRELEVEMENT ENGIE S.A. MANDAT 00S017296083 00S017296083 FR03SYM002381 50004591297700051361141520250422
        'prelevement-frais-carte-etranger-hors-ue' => 'expenses-leisure-activities-vacation', // PRELEVEMENT FRAIS CARTE ETRANGER HORS UE
        'prelevement-frais-carte-ue-hors-zone-euro' => 'expenses-leisure-activities-vacation', // PRELEVEMENT FRAIS CARTE UE HORS ZONE EURO
        'prelevement-interets-debiteurs' => 'expenses-various-banking', // PRELEVEMENT INTERETS DEBITEURS
        'prelevement-izi-prlvfrx-ywfpb2jt514q5dmd-be39zzzcitd000000037-str16natep51ro5hsapphran1mamtj6nms' => 'expenses-other', // PRELEVEMENT IZI PRLVFRX YWFPB2JT514Q5DMD BE39ZZZCITD000000037 STR16NATEP51RO5HSAPPHRAN1MAMTJ6NMS
        'prelevement-pathe-cinepass-pathe-cinepass' => 'expenses-various-subscriptions', // PRELEVEMENT PATHE CINEPASS PATHE CINEPASS
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
        'virement-emis-vir-inst-vers-nathalie-combepine' => 'expenses-housing-rent', // VIREMENT EMIS VIR INST VERS NATHALIE COMBEPINE
        'virement-emis-vir-inst-vers-nathalie-combepine-loyer' => 'expenses-housing-rent', // VIREMENT EMIS VIR INST VERS NATHALIE COMBEPINE LOYER
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
     * OperationManager constructor.
     */
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    public function import(): void
    {
        $wallet = $this->coreLocator->em()->getRepository(Wallet::class)->findOneBy(['slug' => 'main-wallet']);
        if (!$wallet) {
            return;
        }

        $file = $this->coreLocator->projectDir() . '/bin/data/import/operations.xlsx';
        if (!file_exists($file)) {
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        $em = $this->coreLocator->em();
        $operationRepository = $em->getRepository(Operation::class);
        $outsiderRepository = $em->getRepository(Outsider::class);
        $operationTypeRepository = $em->getRepository(OperationType::class);
        $user = $this->coreLocator->user();

        $expensesType = $operationTypeRepository->findOneBy(['type' => 'expenses']);
        $incomesType = $operationTypeRepository->findOneBy(['type' => 'incomes']);

        $subCategoryRepository = $em->getRepository(SubCategory::class);
        $subCategories = [];
        foreach (self::CORRESPONDENCE as $outsiderSlug => $subCategorySlug) {
            if ($subCategorySlug) {
                $subCategory = $subCategoryRepository->findOneBy(['slug' => $subCategorySlug]);
                if ($subCategory) {
                    $subCategories[$outsiderSlug] = $subCategory;
                }
            }
        }

        $targetBalance = null;
        if (!empty($rows[7]['C'])) {
            $targetBalanceStr = $rows[7]['C'];
            $targetBalanceStr = str_replace([' ', '€', "\u{00A0}"], '', $targetBalanceStr);
            $targetBalanceStr = str_replace(',', '.', $targetBalanceStr);
            $targetBalance = (float) $targetBalanceStr;
        }

        $outsiders = [];
        foreach ($outsiderRepository->findBy(['createdBy' => $user]) as $outsider) {
            $outsiders[$outsider->getAdminName()] = $outsider;
        }

        $hasChanges = false;
        foreach ($rows as $index => $row) {
            if ($index < 11 || empty($row['A']) || empty($row['B'])) {
                continue;
            }

            $dateStr = $row['A'];
            $adminName = trim($row['B']);
            $cleanAdminName = $this->cleanOutsiderName($adminName);
            $debit = !empty($row['C']) ? (float) str_replace(',', '.', (string) $row['C']) : 0.0;
            $credit = !empty($row['D']) ? (float) str_replace(',', '.', (string) $row['D']) : 0.0;
            $amount = $debit > 0 ? $debit : $credit;
            $operationType = $debit > 0 ? $expensesType : $incomesType;

            try {
                $date = \DateTime::createFromFormat('d/m/Y', $dateStr);
                if (!$date) {
                    continue;
                }
                $date->setTime(0, 0, 0);
            } catch (\Exception) {
                continue;
            }

            $existing = $operationRepository->findOneBy([
                'wallet' => $wallet,
                'date' => $date,
                'amount' => $amount,
                'adminName' => $adminName
            ]);

            if (!$existing) {
                $operation = new Operation();
                $operation->setWallet($wallet);
                $operation->setDate($date);
                $operation->setAmount($amount);
                $operation->setAdminName($adminName);
                $operation->setCreatedBy($user);
                $operation->setOperationType($operationType);

                if (!isset($outsiders[$cleanAdminName])) {
                    $outsider = $outsiderRepository->findOneBy(['adminName' => $cleanAdminName, 'createdBy' => $user]);
                    if (!$outsider) {
                        $outsider = new Outsider();
                        $outsider->setAdminName($cleanAdminName);
                        $outsider->setSlug(Urlizer::urlize($cleanAdminName));
                        $outsider->setCreatedBy($user);
                        $outsider->setPosition(count($outsiders) + 1);
                        $em->persist($outsider);
                    }
                    $outsiders[$cleanAdminName] = $outsider;
                }
                $operation->setOutsider($outsiders[$cleanAdminName]);

                $outsiderSlug = $operation->getOutsider()->getSlug();
                if (isset($subCategories[$outsiderSlug])) {
                    $operation->setSubCategory($subCategories[$outsiderSlug]);
                }

                $em->persist($operation);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $em->flush();
        }

        if (null !== $targetBalance) {
            $initial = $wallet->getInitialAmount() ?? 0.0;
            $currentBalance = $operationRepository->sumBalance($wallet);
            if ($currentBalance !== $targetBalance) {
                $diff = $targetBalance - $currentBalance;
                $wallet->setInitialAmount($initial + $diff);
                $em->persist($wallet);
                $em->flush();
            }
        }
    }

    public function execute(Operation $operation, FormInterface $form): void
    {
        $adminName = $form->get('adminName')->getData();
        if ($adminName) {
            $cleanAdminName = $this->cleanOutsiderName($adminName);
            $position = count($this->coreLocator->em()->getRepository(Outsider::class)->findBy([
                'createdBy' => $this->coreLocator->user()
            ])) + 1;
            $outsider = $this->coreLocator->em()->getRepository(Outsider::class)->findOneBy([
                'createdBy' => $this->coreLocator->user(),
                'adminName' => $cleanAdminName,
            ]);
            if (!$outsider) {
                $outsider = new Outsider();
                $outsider->setAdminName($cleanAdminName);
                $outsider->setSlug(Urlizer::urlize($cleanAdminName));
                $outsider->setCreatedBy($this->coreLocator->user());
                $outsider->setPosition($position);
                $this->coreLocator->em()->persist($outsider);
            }
            $operation->setOutsider($outsider);

            if (!$operation->getSubCategory()) {
                $outsiderSlug = $outsider->getSlug();
                if (isset(self::CORRESPONDENCE[$outsiderSlug]) && self::CORRESPONDENCE[$outsiderSlug]) {
                    $subCategory = $this->coreLocator->em()->getRepository(SubCategory::class)->findOneBy([
                        'slug' => self::CORRESPONDENCE[$outsiderSlug]
                    ]);
                    if ($subCategory) {
                        $operation->setSubCategory($subCategory);
                    }
                }
            }
        }

        if ($operation->getSubCategory() && !$operation->getOperationType()) {
            $operationType = $this->coreLocator->em()->getRepository(OperationType::class)->findOneBy([
                'type' => $operation->getSubCategory()->getType()
            ]);
            if ($operationType) {
                $operation->setOperationType($operationType);
            }
        }
    }

    /**
     * Nettoie le nom du tiers pour éviter les doublons.
     */
    private function cleanOutsiderName(string $adminName): string
    {
        $adminName = mb_strtoupper($adminName, 'UTF-8');

        // Suppression des préfixes de paiement courants
        $adminName = str_replace(['PAIEMENT PAR CARTE', 'PAIEMENT PAR', 'VIREMENT DE', 'VIREMENT POUR'], '', $adminName);

        // Suppression des codes de transaction (ex: X9322)
        $adminName = preg_replace('/X\d{4,}/', '', $adminName);

        // Suppression des dates (ex: 19/02)
        $adminName = preg_replace('/\d{2}\/\d{2}/', '', $adminName);

        // Nettoyage des espaces multiples et trim
        return trim(preg_replace('/\s+/', ' ', $adminName));
    }
}
