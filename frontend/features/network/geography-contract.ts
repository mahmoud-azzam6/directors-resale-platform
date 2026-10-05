import type { GeographicAncestry, GeographicLocation } from '@/lib/api/network';

export const geographyLevels = ['COUNTRY', 'GOVERNORATE', 'CITY', 'AREA', 'DISTRICT'];
export const geographyLabels = ['الدولة', 'المحافظة', 'المدينة', 'المنطقة', 'الحي'];

export function canonicalPath(ancestry: GeographicAncestry | undefined): GeographicLocation[] {
  if (!ancestry || ancestry.stop_reason !== 'root' || !ancestry.locations.length) return [];
  const path = [...ancestry.locations].reverse();
  const visited = new Set<number>();
  let rank = -1;
  for (let index = 0; index < path.length; index += 1) {
    const row = path[index];
    const nextRank = geographyLevels.indexOf(row.location_type);
    if (row.status !== 'active' || nextRank <= rank || visited.has(row.id)) return [];
    if (index === 0 ? row.location_type !== 'COUNTRY' || row.parent_id !== null : row.parent_id !== path[index - 1].id) return [];
    visited.add(row.id);
    rank = nextRank;
  }
  return path;
}

export function activeChildren(rows: GeographicLocation[], parent: GeographicLocation | null): GeographicLocation[] {
  const parentRank = parent ? geographyLevels.indexOf(parent.location_type) : -1;
  return rows.filter((row) => row.status === 'active' && row.parent_id === (parent?.id ?? null)
    && (parent ? geographyLevels.indexOf(row.location_type) > parentRank : row.location_type === 'COUNTRY'));
}
