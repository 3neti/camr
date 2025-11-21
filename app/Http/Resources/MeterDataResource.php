<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeterDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'meter_name' => $this->meter_name,
            'reading_datetime' => $this->reading_datetime,
            // Voltage readings - ensure float
            'vrms_a' => $this->vrms_a !== null ? (float) $this->vrms_a : null,
            'vrms_b' => $this->vrms_b !== null ? (float) $this->vrms_b : null,
            'vrms_c' => $this->vrms_c !== null ? (float) $this->vrms_c : null,
            // Current readings - ensure float
            'irms_a' => $this->irms_a !== null ? (float) $this->irms_a : null,
            'irms_b' => $this->irms_b !== null ? (float) $this->irms_b : null,
            'irms_c' => $this->irms_c !== null ? (float) $this->irms_c : null,
            // Power values - ensure float
            'frequency' => $this->frequency !== null ? (float) $this->frequency : null,
            'power_factor' => $this->power_factor !== null ? (float) $this->power_factor : null,
            'watt' => $this->watt !== null ? (float) $this->watt : null,
            'va' => $this->va !== null ? (float) $this->va : null,
            'var' => $this->var !== null ? (float) $this->var : null,
            // Energy values - ensure float
            'wh_delivered' => $this->wh_delivered !== null ? (float) $this->wh_delivered : null,
            'wh_received' => $this->wh_received !== null ? (float) $this->wh_received : null,
            'wh_net' => $this->wh_net !== null ? (float) $this->wh_net : null,
            'wh_total' => $this->wh_total !== null ? (float) $this->wh_total : null,
            // Reactive energy values - ensure float
            'varh_negative' => $this->varh_negative !== null ? (float) $this->varh_negative : null,
            'varh_positive' => $this->varh_positive !== null ? (float) $this->varh_positive : null,
            'varh_net' => $this->varh_net !== null ? (float) $this->varh_net : null,
            'varh_total' => $this->varh_total !== null ? (float) $this->varh_total : null,
            'vah_total' => $this->vah_total !== null ? (float) $this->vah_total : null,
            // Demand values - ensure float
            'max_rec_kw_demand' => $this->max_rec_kw_demand !== null ? (float) $this->max_rec_kw_demand : null,
            'max_del_kw_demand' => $this->max_del_kw_demand !== null ? (float) $this->max_del_kw_demand : null,
            'max_pos_kvar_demand' => $this->max_pos_kvar_demand !== null ? (float) $this->max_pos_kvar_demand : null,
            'max_neg_kvar_demand' => $this->max_neg_kvar_demand !== null ? (float) $this->max_neg_kvar_demand : null,
            // Phase angles - ensure float
            'v_phase_angle_a' => $this->v_phase_angle_a !== null ? (float) $this->v_phase_angle_a : null,
            'v_phase_angle_b' => $this->v_phase_angle_b !== null ? (float) $this->v_phase_angle_b : null,
            'v_phase_angle_c' => $this->v_phase_angle_c !== null ? (float) $this->v_phase_angle_c : null,
            'i_phase_angle_a' => $this->i_phase_angle_a !== null ? (float) $this->i_phase_angle_a : null,
            'i_phase_angle_b' => $this->i_phase_angle_b !== null ? (float) $this->i_phase_angle_b : null,
            'i_phase_angle_c' => $this->i_phase_angle_c !== null ? (float) $this->i_phase_angle_c : null,
        ];
    }
}
