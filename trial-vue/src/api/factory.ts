import { getJson, patchJson, postJson } from './client'
import type { OrderItem } from './orders'

// Internal endpoints for running the factory floor, under /api/factory.

export const UNIT_STATUSES = ['waiting', 'picked', 'consolidated', 'packed'] as const

export type UnitStatus = (typeof UNIT_STATUSES)[number]

export interface Operative {
  id: number
  name: string
  created_at: string
  updated_at: string
}

export interface Location {
  id: number
  aisle: string
  bay: string
  shelf: string
  // Aisle, bay and shelf joined, e.g. "A-03-2".
  code: string
}

export interface Unit {
  id: number
  order_item_id: number
  status: UnitStatus
  location_id: number | null
  pick_list_id: number | null
  created_at: string
  updated_at: string
}

// A unit as it appears on a pick list: with where to find it and what it is.
export interface PickListUnit extends Unit {
  location: Location
  order_item: OrderItem
}

export interface PickList {
  id: number
  // The operative picking this list, once someone has taken it.
  operative_id: number | null
  completed_at: string | null
  // In the order the picker walks the warehouse.
  units: PickListUnit[]
  created_at: string
  updated_at: string
}

// A pick list as the API returns it, from both the index and creating one:
// also includes who took it and how many units it holds.
export interface ListedPickList extends PickList {
  operative: Operative | null
  units_count: number
}

export interface PickListFilters {
  completed?: boolean
  operativeId?: number
}

// Newest first.
export function fetchPickLists(filters: PickListFilters = {}): Promise<ListedPickList[]> {
  const params = new URLSearchParams()

  if (filters.completed !== undefined) {
    params.set('completed', String(filters.completed))
  }

  if (filters.operativeId !== undefined) {
    params.set('operative_id', String(filters.operativeId))
  }

  const query = params.toString()

  return getJson<ListedPickList[]>(`/api/factory/pick-lists${query ? `?${query}` : ''}`)
}

// Alphabetical by name.
export function fetchOperatives(): Promise<Operative[]> {
  return getJson<Operative[]>('/api/factory/operatives')
}

/**
 * Batch the oldest waiting units into a new pick list, optionally for an operative.
 * Resolves to null when there's nothing waiting to be picked.
 */
export async function generatePickList(operativeId: number | null = null): Promise<ListedPickList | null> {
  return (await postJson<ListedPickList | undefined>('/api/factory/pick-lists', { operative_id: operativeId })) ?? null
}

export function updateUnitStatus(id: number, status: UnitStatus): Promise<Unit> {
  return patchJson<Unit>(`/api/factory/units/${id}`, { status })
}
