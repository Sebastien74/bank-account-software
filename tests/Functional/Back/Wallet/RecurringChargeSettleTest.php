<?php

declare(strict_types=1);

namespace App\Tests\Functional\Back\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Tests\Functional\Back\BackOfficeWebTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Encaissement d'une échéance à venir depuis `back_operation_index`.
 *
 * Un même tiers peut porter deux contrats prélevés le même jour : la ligne
 * mobile et la box Bouygues. L'écriture créée doit se rattacher à son seul
 * contrat — sans quoi l'échéance restait annoncée à venir et chaque clic
 * l'encaissait une fois de plus — sans pour autant éteindre l'autre.
 */
final class RecurringChargeSettleTest extends BackOfficeWebTestCase
{
    private const string MOBILE = 'bouygues-telecom-06xxxxx891';
    private const string BOX = 'bouygues-telecom-09xxxxx800';

    private string $lastToken = '';

    #[Test]
    public function settling_one_contract_leaves_the_other_pending_and_cannot_be_repeated(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->createBouyguesHistory();

        $this->settle($wallet, self::MOBILE);

        self::assertSame([self::BOX], $this->pendingKeys($wallet));
        self::assertSame([10.0, 41.98], $this->amountsThisMonth());

        // Un second envoi du même formulaire ne doit rien écrire de plus.
        $this->client->request('POST', $this->router->generate('back_recurring_settle', [
            'wallet' => $wallet->getId(),
            'charge' => self::MOBILE,
        ]), ['_token' => $this->lastToken]);
        self::assertSame([10.0, 41.98], $this->amountsThisMonth());
    }

    #[Test]
    public function both_contracts_of_the_same_outsider_are_settled_separately(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->createBouyguesHistory();

        $this->settle($wallet, self::MOBILE);
        $this->settle($wallet, self::BOX);

        self::assertSame([], $this->pendingKeys($wallet));
        self::assertSame([10.0, 41.98, 52.99], $this->amountsThisMonth());
    }

    /**
     * Douze mois de prélèvements des deux contrats, libellés comme la banque.
     */
    private function createBouyguesHistory(): Wallet
    {
        $wallet = $this->createWallet('Compte courant', 1000.0);
        $subCategory = $this->createSubCategory('Téléphone / Internet');
        $subCategory->setSlug('expenses-phone-phone-internet');
        // L'encaissement cherche le type par son slug ; la détection, elle,
        // écarte toute écriture sans type des dépenses.
        $expenses = $subCategory->getCategory()->getOperationType();
        $expenses->setSlug('expenses');
        $this->em->flush();
        $outsider = $this->createOutsider('Bouygues Telecom');

        $month = new \DateTimeImmutable('first day of this month', new \DateTimeZone('Europe/Paris'));
        for ($i = 1; $i <= 12; ++$i) {
            $date = \DateTime::createFromImmutable($month->modify(sprintf('-%d month', $i))->modify('+13 days'));
            foreach (['06xxxxx891' => 41.98, '09xxxxx800' => 52.99] as $reference => $amount) {
                $operation = $this->createOperation($wallet, $date, $amount, $subCategory, $outsider);
                $operation->setAdminName('Prélèvement - Bouygues Telecom - '.$reference.' BTAW0P4J');
                $operation->setOperationType($expenses);
            }
        }
        // Le tableau du mois, et les échéances qu'il annonce, ne s'affichent
        // qu'à partir d'une première écriture.
        $this->createOperation($wallet, \DateTime::createFromImmutable($month), 10.0)->setOperationType($expenses);
        $this->em->flush();

        return $wallet;
    }

    private function settle(Wallet $wallet, string $key): void
    {
        $crawler = $this->client->request('GET', $this->router->generate('back_operation_index', ['wallet' => $wallet->getId()]));
        self::assertResponseIsSuccessful();

        $form = $crawler->filter(sprintf('form[action$="/%s"]', $key));
        self::assertCount(1, $form, sprintf('Échéance « %s » absente des opérations à venir.', $key));
        $this->lastToken = (string) $form->filter('input[name="_token"]')->attr('value');

        $this->client->submit($form->form());
        self::assertResponseRedirects();
    }

    /**
     * @return array<int, string>
     */
    private function pendingKeys(Wallet $wallet): array
    {
        $crawler = $this->client->request('GET', $this->router->generate('back_operation_index', ['wallet' => $wallet->getId()]));
        self::assertResponseIsSuccessful();

        $keys = $crawler->filter('form[action*="/settle/"]')->each(
            static fn ($form): string => basename((string) $form->attr('action'))
        );
        sort($keys);

        return $keys;
    }

    /**
     * @return array<int, float>
     */
    private function amountsThisMonth(): array
    {
        $this->em->clear();
        $start = new \DateTime('first day of this month 00:00:00', new \DateTimeZone('Europe/Paris'));
        $amounts = array_map(
            static fn (Operation $operation): float => (float) $operation->getAmount(),
            array_filter(
                $this->em->getRepository(Operation::class)->findAll(),
                static fn (Operation $operation): bool => $operation->getDate() >= $start
            )
        );
        sort($amounts);

        return array_values($amounts);
    }
}
