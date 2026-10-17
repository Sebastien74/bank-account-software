<?php

declare(strict_types=1);

namespace App\Tests\Unit\Wallet;

use App\Entity\Wallet\RecurringCharge;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Terme d'un échéancier à durée déterminée.
 *
 * Un paiement en quatre fois démarré en octobre court jusqu'en janvier : il
 * franchit décembre, et c'est pour cela que l'année compte. Jugé sur le seul
 * numéro de mois, un tel échéancier reviendrait tous les ans.
 */
final class RecurringChargeScheduleTest extends TestCase
{
    #[Test]
    public function four_instalments_run_from_october_to_january(): void
    {
        $charge = $this->instalments(4, 2026, 10);

        self::assertSame(1, $charge->instalmentNumber(2026, 10));
        self::assertSame(2, $charge->instalmentNumber(2026, 11));
        self::assertSame(3, $charge->instalmentNumber(2026, 12));
        self::assertSame(4, $charge->instalmentNumber(2027, 1));
    }

    #[Test]
    public function the_schedule_stops_of_itself_at_its_term(): void
    {
        $charge = $this->instalments(4, 2026, 10);

        self::assertTrue($charge->isDueOn(2027, 1), 'La dernière échéance est encore attendue.');
        self::assertFalse($charge->isDueOn(2027, 2), "Au-delà du terme, plus rien n'est attendu.");
        self::assertNull($charge->instalmentNumber(2027, 2));
    }

    #[Test]
    public function nothing_is_expected_before_the_first_instalment(): void
    {
        $charge = $this->instalments(4, 2026, 10);

        self::assertFalse($charge->isDueOn(2026, 9));
        self::assertNull($charge->instalmentNumber(2026, 9));
    }

    #[Test]
    public function the_same_month_of_the_next_year_is_not_an_instalment(): void
    {
        $charge = $this->instalments(4, 2026, 10);

        self::assertFalse($charge->isDueOn(2027, 10), "L'échéancier ne revient pas d'une année sur l'autre.");
    }

    #[Test]
    public function a_disabled_schedule_is_never_expected(): void
    {
        $charge = $this->instalments(4, 2026, 10)->setActive(false);

        self::assertFalse($charge->isDueOn(2026, 10));
    }

    #[Test]
    public function a_monthly_charge_keeps_no_instalment_rank(): void
    {
        $charge = $this->instalments(4, 2026, 10)->setFrequency(RecurringCharge::MONTHLY);

        self::assertNull($charge->instalmentNumber(2026, 10));
        self::assertTrue($charge->isDueOn(2027, 5), 'Une charge mensuelle reste attendue tous les mois.');
    }

    #[Test]
    public function a_yearly_charge_is_expected_on_its_month_only(): void
    {
        $charge = (new RecurringCharge())
            ->setFrequency(RecurringCharge::YEARLY)
            ->setDueMonth(9);

        self::assertTrue($charge->isDueOn(2026, 9));
        self::assertTrue($charge->isDueOn(2027, 9));
        self::assertFalse($charge->isDueOn(2026, 10));
    }

    private function instalments(int $count, int $year, int $month): RecurringCharge
    {
        return (new RecurringCharge())
            ->setFrequency(RecurringCharge::INSTALMENTS)
            ->setInstalments($count)
            ->setStartsOn(new \DateTime(sprintf('%04d-%02d-01 00:00:00', $year, $month)));
    }
}
