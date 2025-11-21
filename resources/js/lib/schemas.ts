import { z } from 'zod'

// Meter data validation schema
export const MeterDataSchema = z.object({
  id: z.number(),
  location: z.string().nullable(),
  meter_name: z.string(),
  reading_datetime: z.string().or(z.date()),
  vrms_a: z.number().nullable(),
  vrms_b: z.number().nullable(),
  vrms_c: z.number().nullable(),
  irms_a: z.number().nullable(),
  irms_b: z.number().nullable(),
  irms_c: z.number().nullable(),
  frequency: z.number().nullable(),
  power_factor: z.number().nullable(),
  watt: z.number().nullable(),
  va: z.number().nullable(),
  var: z.number().nullable(),
  wh_delivered: z.number().nullable(),
  wh_received: z.number().nullable(),
  wh_net: z.number().nullable(),
  wh_total: z.number().nullable(),
  varh_negative: z.number().nullable(),
  varh_positive: z.number().nullable(),
  varh_net: z.number().nullable(),
  varh_total: z.number().nullable(),
  vah_total: z.number().nullable(),
  max_rec_kw_demand: z.number().nullable(),
  max_del_kw_demand: z.number().nullable(),
  max_pos_kvar_demand: z.number().nullable(),
  max_neg_kvar_demand: z.number().nullable(),
})

export type MeterData = z.infer<typeof MeterDataSchema>

// Energy summary validation schema
export const EnergySummarySchema = z.object({
  total_delivered: z.number().nullable(),
  avg_power: z.number().nullable(),
  peak_power: z.number().nullable(),
  period_days: z.number().nullable(),
})

export type EnergySummary = z.infer<typeof EnergySummarySchema>

// Helper function to safely parse and log validation errors
export function parseAndValidate<T>(schema: z.ZodSchema<T>, data: unknown, context: string): T {
  try {
    return schema.parse(data)
  } catch (error) {
    if (error instanceof z.ZodError) {
      console.error(`Validation error in ${context}:`, error.errors)
      console.error('Invalid data:', data)
      // Return data as-is after logging - prevents app crash
      return data as T
    }
    throw error
  }
}
