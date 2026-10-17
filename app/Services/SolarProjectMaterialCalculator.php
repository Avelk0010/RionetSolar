<?php

namespace App\Services;

use App\Enums\InstallationMaterialCategory;
use App\Models\InstallationMaterial;
use App\Models\SolarProject;
use App\Models\SolarProjectSystemDesign;
use Illuminate\Support\Facades\DB;

class SolarProjectMaterialCalculator
{
    private const PANELS_PER_RAIL = 4;

    private const CATEGORIES = [
        'rails' => InstallationMaterialCategory::RielEstructuraSolar,
        'rail_unions' => InstallationMaterialCategory::UnionRiel,
        'ground_connectors' => InstallationMaterialCategory::ConectorTierra,
        'mc4_connectors' => InstallationMaterialCategory::ConectorMc4,
        'end_clamps' => InstallationMaterialCategory::EndClamp,
        'mid_clamps' => InstallationMaterialCategory::MidClamp,
    ];

    /**
     * Material activo del catálogo para cada ítem calculado, o null si la categoría no tiene uno.
     *
     * @return array<string, ?InstallationMaterial>
     */
    public function catalogItems(): array
    {
        $materials = InstallationMaterial::active()
            ->whereIn('category', array_map(fn (InstallationMaterialCategory $category) => $category->value, self::CATEGORIES))
            ->get()
            ->keyBy(fn (InstallationMaterial $material) => $material->category->value);

        return array_map(fn (InstallationMaterialCategory $category) => $materials->get($category->value), self::CATEGORIES);
    }

    /**
     * Filas guardadas por string; sin ellas, cada string va en una sola fila.
     *
     * @return array<int, list<int>>
     */
    public function layout(SolarProject $project, SolarProjectSystemDesign $design): array
    {
        $saved = $project->stringRows()->orderBy('string_number')->orderBy('row_number')->get()->groupBy('string_number');
        $layout = [];

        for ($string = 1; $string <= (int) $design->inverter_count; $string++) {
            $rows = $saved->get($string)?->pluck('panel_count')->map(fn ($count) => (int) $count)->all();

            $layout[$string] = $rows !== null && array_sum($rows) === (int) $design->panels_per_string
                ? $rows
                : [(int) $design->panels_per_string];
        }

        return $layout;
    }

    /**
     * @param  array<int, list<int>>  $rowsByString
     */
    public function saveLayout(SolarProject $project, array $rowsByString): void
    {
        DB::transaction(function () use ($project, $rowsByString): void {
            $project->stringRows()->delete();

            foreach ($rowsByString as $stringNumber => $rows) {
                foreach (array_values($rows) as $index => $panelCount) {
                    $project->stringRows()->create([
                        'string_number' => $stringNumber,
                        'row_number' => $index + 1,
                        'panel_count' => $panelCount,
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, list<int>>  $layout
     * @return array{rows: int, rails: int, rail_leftover_quarters: int, rail_unions: int, ground_connectors: int, mc4_connectors: int, end_clamps: int, mid_clamps: int}
     */
    public function calculate(array $layout): array
    {
        $rows = 0;
        $fullRails = 0;
        $railUnions = 0;
        $midClamps = 0;
        $partialSegments = [];

        foreach ($layout as $stringRows) {
            foreach ($stringRows as $panels) {
                $rows++;
                $blocks = (int) ceil($panels / self::PANELS_PER_RAIL);
                $remainder = $panels % self::PANELS_PER_RAIL;
                // Cada fila lleva un riel arriba y otro abajo.
                $fullRails += 2 * intdiv($panels, self::PANELS_PER_RAIL);
                $railUnions += 2 * ($blocks - 1);
                $midClamps += 2 * ($panels - 1);

                if ($remainder > 0) {
                    array_push($partialSegments, $remainder, $remainder);
                }
            }
        }

        [$cutRails, $leftovers] = $this->cutPartialSegments($partialSegments);

        return [
            'rows' => $rows,
            'rails' => $fullRails + $cutRails,
            'rail_leftover_quarters' => array_sum($leftovers),
            'rail_unions' => $railUnions,
            'ground_connectors' => 2 * $rows,
            'mc4_connectors' => 2 * $rows,
            'end_clamps' => 4 * $rows,
            'mid_clamps' => $midClamps,
        ];
    }

    /**
     * Un tramo de 1 a 3 paneles usa primero el sobrante más pequeño que alcance; si no hay, corta un riel nuevo.
     *
     * @param  list<int>  $segments  cuartos de riel necesarios por tramo
     * @return array{0: int, 1: list<int>}
     */
    private function cutPartialSegments(array $segments): array
    {
        rsort($segments);
        $cutRails = 0;
        $leftovers = [];

        foreach ($segments as $needed) {
            $best = null;

            foreach ($leftovers as $index => $available) {
                if ($available >= $needed && ($best === null || $available < $leftovers[$best])) {
                    $best = $index;
                }
            }

            if ($best === null) {
                $cutRails++;
                $remaining = self::PANELS_PER_RAIL - $needed;
            } else {
                $remaining = $leftovers[$best] - $needed;
                unset($leftovers[$best]);
            }

            if ($remaining > 0) {
                $leftovers[] = $remaining;
            }
        }

        return [$cutRails, array_values($leftovers)];
    }
}
