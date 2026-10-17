<?php

namespace App\Services;

use App\Models\ElectricalEquipment;
use App\Models\SolarProjectSystemDesign;
use Illuminate\Database\Eloquent\Builder;

class SolarProjectProtectionRecommender
{
    private const VOLTAGE_MARGIN = 1.15;

    private const PANEL_CURRENT_MARGIN = 1.20;

    private const DPS_MIN_CURRENT_A = 20000;

    private const AC_CURRENT_TOLERANCE_A = 5;

    /**
     * @return list<array{title: string, quantity: int, recommendation: string, item: ?ElectricalEquipment}>
     */
    public function recommend(SolarProjectSystemDesign $design): array
    {
        $design->loadMissing('solarPanel.solarPanelSpec', 'inverter.inverterSpec');
        $panel = $design->solarPanel->solarPanelSpec;
        $inverter = $design->inverter->inverterSpec;
        $strings = (int) $design->inverter_count;
        $inverters = (int) $design->inverter_count;

        $stringVoltage = (float) $panel->open_circuit_voltage_v * (int) $design->panels_per_string;
        $dcVoltage = round($stringVoltage * self::VOLTAGE_MARGIN, 2);
        $dcCurrent = round((float) $panel->short_circuit_current_a * self::PANEL_CURRENT_MARGIN, 2);
        $outputCurrent = (float) $inverter->max_output_current_a;
        $format = fn (float $value): string => number_format($value, 2, ',', '.');

        $dcBase = sprintf(
            'voltaje de operación de al menos %s V (Voc de %s V × %d paneles = %s V, más 15%%)',
            $format($dcVoltage),
            $format((float) $panel->open_circuit_voltage_v),
            $design->panels_per_string,
            $format($stringVoltage),
        );

        $acRecommendation = function (string $side, float $voltage) use ($inverters, $outputCurrent, $format): array {
            $poles = $voltage <= 110 ? 1 : 2;

            return [
                $poles,
                sprintf(
                    'Se recomienda %d breaker(s) AC %s, uno por inversor, de %d %s, voltaje de operación de al menos %s V y corriente cercana a %s A (±%d A, la corriente máxima de salida del inversor).',
                    $inverters,
                    $side,
                    $poles,
                    $poles === 1 ? 'polo (unipolar)' : 'polos (bipolar)',
                    $format($voltage),
                    $format($outputCurrent),
                    self::AC_CURRENT_TOLERANCE_A,
                ),
            ];
        };

        [$inputPoles, $inputText] = $acRecommendation('de entrada', (float) $inverter->ac_input_voltage_v);
        [$outputPoles, $outputText] = $acRecommendation('de salida', (float) $inverter->ac_output_voltage_v);

        return [
            [
                'title' => 'Breakers DC para strings',
                'quantity' => $strings,
                'recommendation' => sprintf('Se recomienda %d breaker(s) DC de 2 polos (positivo y negativo), uno por string, con %s y corriente de al menos %s A (Isc del panel de %s A, más 20%%).', $strings, $dcBase, $format($dcCurrent), $format((float) $panel->short_circuit_current_a)),
                'item' => $this->findDcBreaker($dcVoltage, $dcCurrent, false),
            ],
            [
                'title' => 'Breakers AC de entrada',
                'quantity' => $inverters,
                'recommendation' => $inputText,
                'item' => $this->findAcBreaker($inputPoles, (float) $inverter->ac_input_voltage_v, $outputCurrent),
            ],
            [
                'title' => 'Breakers AC de salida',
                'quantity' => $inverters,
                'recommendation' => $outputText,
                'item' => $this->findAcBreaker($outputPoles, (float) $inverter->ac_output_voltage_v, $outputCurrent),
            ],
            [
                'title' => 'DPS (protección contra sobretensiones DC)',
                'quantity' => $strings,
                'recommendation' => sprintf('Se recomienda %d DPS DC de 2 polos, uno por string, con %s y corriente de descarga de al menos %s A.', $strings, $dcBase, number_format(self::DPS_MIN_CURRENT_A, 0, ',', '.')),
                'item' => $this->findDcBreaker($dcVoltage, self::DPS_MIN_CURRENT_A, true),
            ],
        ];
    }

    private function findDcBreaker(float $voltage, float $current, bool $isSurgeProtector): ?ElectricalEquipment
    {
        return ElectricalEquipment::active()
            ->whereHas('category', fn (Builder $query) => $query->where('slug', 'breakers-dc'))
            ->whereHas('dcBreakerSpec', function (Builder $query) use ($voltage, $current, $isSurgeProtector): void {
                $query->where('poles', 2)
                    ->where('max_operating_voltage_v', '>=', $voltage)
                    ->where('rated_current_a', '>=', $current);

                // Corrientes de 20.000 A o más identifican a los DPS y no a los breakers de string.
                if (! $isSurgeProtector) {
                    $query->where('rated_current_a', '<', self::DPS_MIN_CURRENT_A);
                }
            })
            ->with('dcBreakerSpec')
            ->get()
            ->sortBy([fn ($a, $b) => (float) $a->dcBreakerSpec->rated_current_a <=> (float) $b->dcBreakerSpec->rated_current_a, fn ($a, $b) => (float) $a->price <=> (float) $b->price])
            ->first();
    }

    private function findAcBreaker(int $poles, float $voltage, float $current): ?ElectricalEquipment
    {
        return ElectricalEquipment::active()
            ->whereHas('category', fn (Builder $query) => $query->where('slug', 'breakers-ac'))
            ->whereHas('acBreakerSpec', fn (Builder $query) => $query->where('poles', $poles)
                ->where('operating_voltage_v', '>=', $voltage)
                ->whereBetween('rated_current_a', [$current - self::AC_CURRENT_TOLERANCE_A, $current + self::AC_CURRENT_TOLERANCE_A]))
            ->with('acBreakerSpec')
            ->get()
            ->sortBy([fn ($a, $b) => abs((float) $a->acBreakerSpec->rated_current_a - $current) <=> abs((float) $b->acBreakerSpec->rated_current_a - $current), fn ($a, $b) => (float) $a->price <=> (float) $b->price])
            ->first();
    }
}
